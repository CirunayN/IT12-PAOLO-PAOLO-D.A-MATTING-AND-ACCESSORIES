<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::all();

        // POS must only show Cash and GCash.
        $paymentMethods = PaymentMethod::whereIn('Name', [
            'Cash',
            'GCash',
        ])->orderBy('ID')->get();

        // Exclude archived/disabled products from POS catalog.
        $archivedStatus = Status::where('Name', 'Archived')->first();

        $query = Product::with([
            'category',
            'status',
            'stockIns',
            'soldItems'
        ]);

        if ($archivedStatus) {
            $query->where(
                'Status_ID',
                '!=',
                $archivedStatus->ID
            );
        }

        $products = $query->get();

        return view(
            'pos.index',
            compact(
                'categories',
                'paymentMethods',
                'products'
            )
        );
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => [
                'required',
                'exists:tbl_payment_method,ID',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:tbl_product,ID',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:1',
            ],

            'amount_tendered' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        /*
         * Even if someone manually modifies the request,
         * only Cash and GCash are allowed.
         */
        $paymentMethod = PaymentMethod::findOrFail(
            $validated['payment_method_id']
        );

        if (! in_array(
            strtolower($paymentMethod->Name),
            ['cash', 'gcash']
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only Cash and GCash are allowed as payment methods.',
            ], 422);
        }

        return DB::transaction(
            function () use (
                $validated,
                $paymentMethod
            ) {
                $total = 0;
                $itemsToSave = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail(
                        $item['product_id']
                    );

                    $qty = (float) $item['quantity'];

                    /*
                     * Do not allow decimal, zero, negative,
                     * or quantities exceeding stock.
                     */
                    if ($qty < 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Product quantity must be at least 1.',
                        ], 422);
                    }

                    if ($product->stock_quantity < $qty) {
                        return response()->json([
                            'success' => false,
                            'message' =>
                                "Insufficient stock for {$product->Name}. " .
                                "Only {$product->stock_quantity} available.",
                        ], 422);
                    }

                    $price = (float) $product->retail_price;

                    $lineTotal = round(
                        $price * $qty,
                        2
                    );

                    $total += $lineTotal;

                    $itemsToSave[] = [
                        'product_id' => $product->ID,
                        'name' => $product->Name,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total' => $lineTotal,
                    ];
                }

                $total = round($total, 2);

                $amountTendered = round(
                    (float) $validated['amount_tendered'],
                    2
                );

                /*
                 * Payment must be equal to or greater
                 * than the transaction total.
                 */
                if ($amountTendered < $total) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Amount received must be equal to or greater than the total amount.',
                    ], 422);
                }

                $change = round(
                    $amountTendered - $total,
                    2
                );

                /*
                 * The cashier is ALWAYS the currently
                 * logged-in user.
                 */
                $cashier = auth()->user();
                $cashierId = auth()->id();

                if (! $cashier || ! $cashierId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your login session has expired. Please log in again.',
                    ], 401);
                }

                $sale = Sale::create([
                    'Date' => Carbon::now(),
                    'Total' => $total,
                    'Amount_Received' => $amountTendered,
                    'Change_Amount' => $change,
                    'User_ID' => $cashierId,
                    'Payment_Method_ID' => $paymentMethod->ID,
                ]);

                foreach ($itemsToSave as $item) {
                    SoldItem::create([
                        'Product_ID' => $item['product_id'],
                        'Quantity' => $item['quantity'],
                        'Total' => $item['total'],
                        'Sale_ID' => $sale->ID,
                    ]);
                }

                $cashierName =
                    $cashier->name ?? 'Staff';

                $cashierRole =
                    $cashier->role ?? 'Staff';

                Log::info(
                    "Sale #INV-{$sale->ID}: " .
                    "Processed total ₱" .
                    number_format($total, 2) .
                    " by {$cashierName} ({$cashierRole})"
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Sale completed successfully!',

                    'sale_id' => $sale->ID,

                    'total' => $total,

                    'tendered' => $amountTendered,

                    'change' => $change,

                    'date' => $sale->Date->format(
                        'M d, Y h:i A'
                    ),

                    'cashier' => $cashierName,

                    'cashier_role' => $cashierRole,

                    'payment_method' =>
                        $paymentMethod->Name,

                    'items' => $itemsToSave,
                ]);
            }
        );
    }

    public function receipt($id)
    {
        $sale = Sale::with([
            'user',
            'paymentMethod',
            'soldItems.product',
        ])->findOrFail($id);

        return view(
            'pos.receipt',
            compact('sale')
        );
    }
}
