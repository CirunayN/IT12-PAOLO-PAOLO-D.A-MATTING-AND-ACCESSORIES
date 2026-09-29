<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $query = StockIn::with(['product.category', 'user']);

        if ($request->filled('product_id')) {
            $query->where('Product_ID', $request->product_id);
        }

        if ($request->filled('user_id')) {
            $query->where('User_ID', $request->user_id);
        }

        $stockIns = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
        $products = Product::orderBy('Name', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();

        return view('stock_in.index', compact('stockIns', 'products', 'users'));
    }

    public function create()
    {
        $archivedStatus = Status::where('Name', 'Archived')->first();

        $query = Product::with(['category', 'status', 'stockIns', 'soldItems']);

        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        $products = $query->orderBy('Name', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('Name', 'asc')->get();

        return view('stock_in.create', compact('products', 'users', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_mode' => 'required|in:existing,new',
            'Product_ID' => 'required_if:product_mode,existing|nullable|exists:tbl_product,ID',
            'New_Product_Name' => 'required_if:product_mode,new|nullable|string|max:255|unique:tbl_product,Name',
            'New_Product_Description' => 'nullable|string|max:2000',
            'New_Category_ID' => 'required_if:product_mode,new|nullable|exists:tbl_category,ID',
            'User_ID' => 'nullable|exists:users,id',
            'Quantity' => 'required|numeric|min:1',
            'Cost_Price' => 'required|numeric|min:0',
            'Retail_Price' => 'required|numeric|min:0',
            'Has_Expiration' => 'nullable|boolean',
            'Expiration_Date' => 'nullable|required_if:Has_Expiration,1|date|after_or_equal:today',
            'Condition' => 'nullable|in:Good,Damaged,Defective',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $processorId = !empty($validated['User_ID'])
                ? (int) $validated['User_ID']
                : (auth()->id() ?: 1);

            if ($validated['product_mode'] === 'new') {
                $activeStatus = Status::firstOrCreate(['Name' => 'Active']);

                $product = Product::create([
                    'Name' => $validated['New_Product_Name'],
                    'Description' => $validated['New_Product_Description'] ?? null,
                    'Category_ID' => $validated['New_Category_ID'],
                    'Status_ID' => $activeStatus->ID,
                ]);
            } else {
                $product = Product::findOrFail($validated['Product_ID']);
            }

            $hasExpiration = $request->boolean('Has_Expiration');

            $stockIn = StockIn::create([
                'Product_ID' => $product->ID,
                'User_ID' => $processorId,
                'Quantity' => $validated['Quantity'],
                'Remaining_Quantity' => $validated['Quantity'],
                'Cost_Price' => $validated['Cost_Price'],
                'Retail_Price' => $validated['Retail_Price'],
                'Has_Expiration' => $hasExpiration,
                'Expiration_Date' => $hasExpiration
                    ? ($validated['Expiration_Date'] ?? null)
                    : null,
                'Condition' => $validated['Condition'] ?? 'Good',
            ]);

            $processor = User::find($processorId);
            $processorName = $processor->name ?? 'Staff';
            $processorRole = $processor->role ?? 'Staff';

            Log::info(
                "Stock-In #{$stockIn->ID}: Received {$stockIn->Quantity} units of " .
                "'{$product->Name}' processed by {$processorName} ({$processorRole})"
            );

            return redirect()->route('stock-in.index')->with(
                'success',
                "Stock-In batch #SI-{$stockIn->ID} recorded successfully " .
                "(+{$stockIn->Quantity} units of {$product->Name})!"
            );
        });
    }
}
