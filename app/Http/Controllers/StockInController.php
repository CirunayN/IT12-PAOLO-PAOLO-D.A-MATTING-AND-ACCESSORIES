<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
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

        return view('stock_in.create', compact('products', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Product_ID' => 'required|exists:tbl_product,ID',
            'User_ID' => 'nullable|exists:users,id',
            'Quantity' => 'required|numeric|min:1',
            'Cost_Price' => 'required|numeric|min:0',
            'Retail_Price' => 'required|numeric|min:0',
        ]);

        $processorId = !empty($validated['User_ID']) ? (int) $validated['User_ID'] : (auth()->id() ?: 1);
        $validated['User_ID'] = $processorId;

        $stockIn = StockIn::create($validated);
        $processor = User::find($processorId);
        $product = Product::find($validated['Product_ID']);

        $processorName = $processor->name ?? 'Staff';
        $processorRole = $processor->role ?? 'Staff';
        $productName = $product->Name ?? "Product #{$stockIn->Product_ID}";

        Log::info("Stock-In #{$stockIn->ID}: Received {$stockIn->Quantity} units of '{$productName}' processed by {$processorName} ({$processorRole})");

        return redirect()->route('stock-in.index')->with(
            'success',
            "Stock-In batch #SI-{$stockIn->ID} recorded successfully (+{$stockIn->Quantity} units by {$processorName})!"
        );
    }
}