<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::all();

        $paymentMethods = PaymentMethod::whereIn('Name', [
            'Cash',
            'GCash',
        ])->orderBy('ID')->get();

        $archivedStatus = Status::where('Name', 'Archived')->first();

        $query = Product::with([
            'category',
            'status',
            'stockIns',
            'soldItems',
        ]);

        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        // Hide zero-stock products from the POS catalog.
        // A product is shown only when it has at least one sellable batch:
        // remaining > 0, Good condition, and not expired.
        $query->whereHas('stockIns', function ($stockQuery) {
            $stockQuery
                ->where('Remaining_Quantity', '>', 0)
                ->where('Condition', 'Good')
                ->where(function ($expirationQuery) {
                    $expirationQuery
                        ->where('Has_Expiration', false)
                        ->orWhereNull('Expiration_Date')
                        ->orWhereDate('Expiration_Date', '>=', today()->toDateString());
                });
        });

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
            'gcash_reference' => [
                'nullable',
                'string',
                'max:100',
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
        ]);

        if (!empty($validated['user_id']) && (int) $validated['user_id'] !== (int) auth()->id()) {
            abort_unless(auth()->user()?->isAdmin(), 403);
        }

        $paymentMethod = PaymentMethod::findOrFail(
            $validated['payment_method_id']
        );

        $paymentMethodName = strtolower(trim($paymentMethod->Name));

        if (!in_array(
            $paymentMethodName,
            ['cash', 'gcash'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only Cash and GCash are allowed as payment methods.',
            ], 422);
        }

        $gcashReference = null;

        if ($paymentMethodName === 'gcash') {
            $gcashReference = trim((string) ($validated['gcash_reference'] ?? ''));

            if ($gcashReference === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'GCash reference number is required for GCash payments.',
                    'errors' => [
                        'gcash_reference' => [
                            'GCash reference number is required for GCash payments.',
                        ],
                    ],
                ], 422);
            }
        }

        return DB::transaction(function () use (
            $validated,
            $paymentMethod,
            $paymentMethodName,
            $gcashReference
        ) {
            $total = 0;
            $itemsToSave = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];

                $batches = StockIn::where('Product_ID', $product->ID)
                    ->where('Remaining_Quantity', '>', 0)
                    ->where('Condition', 'Good')
                    ->where(function ($query) {
                        $query
                            ->where('Has_Expiration', false)
                            ->orWhereNull('Expiration_Date')
                            ->orWhereDate(
                                'Expiration_Date',
                                '>=',
                                today()->toDateString()
                            );
                    })
                    ->orderBy('ID', 'asc')
                    ->lockForUpdate()
                    ->get();

                $available = (float) $batches->sum('Remaining_Quantity');

                if ($available < $qty) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            "Insufficient sellable stock for {$product->Name}. " .
                            "Only {$available} available.",
                    ], 422);
                }

                $price = (float) $product->retail_price;
                $lineTotal = round($price * $qty, 2);
                $total += $lineTotal;

                $remainingToAllocate = $qty;
                $fifoAllocations = [];

                foreach ($batches as $batch) {
                    if ($remainingToAllocate <= 0) {
                        break;
                    }

                    $availableInBatch = (float) $batch->Remaining_Quantity;
                    $take = min($availableInBatch, $remainingToAllocate);

                    if ($take > 0) {
                        $fifoAllocations[] = [
                            'batch' => $batch,
                            'quantity' => $take,
                        ];

                        $remainingToAllocate -= $take;
                    }
                }

                $itemsToSave[] = [
                    'product_id' => $product->ID,
                    'name' => $product->Name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => $lineTotal,
                    'fifo_allocations' => $fifoAllocations,
                ];
            }

            $total = round($total, 2);
            $amountTendered = round((float) $validated['amount_tendered'], 2);

            if ($paymentMethodName === 'gcash') {
                if (abs($amountTendered - $total) > 0.009) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GCash payment amount must match the sale total exactly.',
                    ], 422);
                }
            } elseif ($amountTendered < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount received must be equal to or greater than the total amount.',
                ], 422);
            }

            $change = $paymentMethodName === 'gcash'
                ? 0
                : round($amountTendered - $total, 2);

            $cashier = auth()->user();
            if (!empty($validated['user_id'])) {
                $overrideUser = \App\Models\User::find($validated['user_id']);
                if ($overrideUser) {
                    $cashier = $overrideUser;
                }
            }
            $cashierId = $cashier?->id ?? auth()->id();

            if (!$cashier || !$cashierId) {
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
                'GCash_Reference_Number' => $gcashReference,
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

                foreach ($item['fifo_allocations'] as $allocation) {
                    $batch = $allocation['batch'];
                    $quantityUsed = (float) $allocation['quantity'];

                    $batch->Remaining_Quantity = max(
                        0,
                        (float) $batch->Remaining_Quantity - $quantityUsed
                    );

                    $batch->save();
                }
            }

            $cashierName = $cashier->name ?? 'Staff';
            $cashierRole = $cashier->role ?? 'Staff';

            Log::info(
                "Sale #INV-{$sale->ID}: Processed total ₱" .
                number_format($total, 2) .
                " by {$cashierName} ({$cashierRole}) using FIFO inventory deduction"
            );

            $responseItems = array_map(function ($item) {
                unset($item['fifo_allocations']);
                return $item;
            }, $itemsToSave);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!',
                'sale_id' => $sale->ID,
                'total' => $total,
                'tendered' => $amountTendered,
                'change' => $change,
                'date' => $sale->Date->format('M d, Y h:i A'),
                'cashier' => $cashierName,
                'cashier_role' => $cashierRole,
                'payment_method' => $paymentMethod->Name,
                'gcash_reference' => $gcashReference,
                'items' => $responseItems,
            ]);
        });
    }

    public function receipt($id)
    {
        $sale = Sale::with([
            'user',
            'paymentMethod',
            'soldItems.product',
        ])->findOrFail($id);

        $user = auth()->user();

        if (
            !$user->isAdmin() &&
            (int) $sale->User_ID !== (int) $user->id
        ) {
            abort(403, 'You can only view receipts for your own transactions.');
        }

        return view(
            'pos.receipt',
            compact('sale')
        );
    }
}
