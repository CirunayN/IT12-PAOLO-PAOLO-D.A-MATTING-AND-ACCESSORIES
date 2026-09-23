<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $paymentMethods = PaymentMethod::all();
        $users = User::orderBy('name', 'asc')->get();
        
        // Exclude archived/disabled products from POS catalog
        $archivedStatus = Status::where('Name', 'Archived')->first();
        $query = Product::with(['category', 'status', 'stockIns', 'soldItems']);
        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }
        $products = $query->get();

        return view('pos.index', compact('categories', 'paymentMethods', 'products', 'users'));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|exists:tbl_payment_method,ID',
            'user_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:tbl_product,ID',
            'items.*.quantity' => 'required|numeric|min:1',
            'amount_tendered' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $total = 0;
            $itemsToSave = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                
                if ($product->stock_quantity < $qty) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->Name}. Only {$product->stock_quantity} available."
                    ], 422);
                }

                $price = (float) $product->retail_price;
                $lineTotal = round($price * $qty, 2);
                $total += $lineTotal;

                $itemsToSave[] = [
                    'product_id' => $product->ID,
                    'name' => $product->Name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => $lineTotal,
                ];
            }

            $cashierId = !empty($validated['user_id']) ? (int) $validated['user_id'] : (auth()->id() ?: 1);
            $cashier = User::find($cashierId) ?? auth()->user();

            $sale = Sale::create([
                'Date' => Carbon::now(),
                'Total' => $total,
                'User_ID' => $cashierId,
                'Payment_Method_ID' => $validated['payment_method_id'],
            ]);

            foreach ($itemsToSave as $item) {
                SoldItem::create([
                    'Product_ID' => $item['product_id'],
                    'Quantity' => $item['quantity'],
                    'Total' => $item['total'],
                    'Sale_ID' => $sale->ID,
                ]);
            }

            $amountTendered = (float) ($validated['amount_tendered'] ?? $total);
            $change = max(0, $amountTendered - $total);

            $cashierName = $cashier->name ?? 'Staff';
            $cashierRole = $cashier->role ?? 'Staff';

            Log::info("Sale #INV-{$sale->ID}: Processed total ₱" . number_format($total, 2) . " by {$cashierName} ({$cashierRole})");

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
                'payment_method' => PaymentMethod::find($validated['payment_method_id'])->Name ?? 'Cash',
                'items' => $itemsToSave,
            ]);
        });
    }

    public function receipt($id)
    {
        $sale = Sale::with(['user', 'paymentMethod', 'soldItems.product'])->findOrFail($id);
        return view('pos.receipt', compact('sale'));
    }
}