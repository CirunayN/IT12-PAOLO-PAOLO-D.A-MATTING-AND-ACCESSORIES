<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        $todaySalesTotal = Sale::whereDate('Date', $today)->sum('Total');
        $todaySalesCount = Sale::whereDate('Date', $today)->count();
        $totalSalesAllTime = Sale::sum('Total');
        $totalTransactions = Sale::count();
        
        // Products and stock
        $products = Product::with(['category', 'status', 'stockIns', 'soldItems'])->get();
        $totalProductsCount = $products->count();
        
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $totalStockUnits = 0;
        $inventoryValue = 0;
        
        foreach ($products as $product) {
            $qty = $product->stock_quantity;
            $price = $product->retail_price;
            $totalStockUnits += $qty;
            $inventoryValue += ($qty * $price);
            
            if ($qty <= 0) {
                $outOfStockCount++;
            } elseif ($qty <= 5) {
                $lowStockCount++;
            }
        }
        
        // Recent sales with cashier & payment method
        $recentSales = Sale::with(['user', 'paymentMethod', 'soldItems.product'])
            ->orderBy('ID', 'desc')
            ->limit(8)
            ->get();
            
        // Recent stock-ins
        $recentStockIns = StockIn::with('product')
            ->orderBy('ID', 'desc')
            ->limit(6)
            ->get();
            
        // Category distribution
        $categories = Category::withCount('products')->get();
        
        return view('dashboard', compact(
            'todaySalesTotal',
            'todaySalesCount',
            'totalSalesAllTime',
            'totalTransactions',
            'totalProductsCount',
            'lowStockCount',
            'outOfStockCount',
            'totalStockUnits',
            'inventoryValue',
            'recentSales',
            'recentStockIns',
            'categories'
        ));
    }
}