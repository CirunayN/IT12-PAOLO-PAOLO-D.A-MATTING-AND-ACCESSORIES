<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $query = StockIn::with('product');

        if ($request->filled('product_id')) {
            $query->where('Product_ID', $request->product_id);
        }

        $stockIns = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
        $products = Product::orderBy('Name', 'asc')->get();

        return view('stock_in.index', compact('stockIns', 'products'));
    }

    public function create()
    {
        $products = Product::orderBy('Name', 'asc')->get();
        return view('stock_in.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Product_ID' => 'required|exists:tbl_product,ID',
            'Quantity' => 'required|numeric|min:1',
            'Cost_Price' => 'required|numeric|min:0',
            'Retail_Price' => 'required|numeric|min:0',
        ]);

        StockIn::create($validated);

        return redirect()->route('stock-in.index')->with('success', 'Stock-In batch recorded successfully!');
    }
}