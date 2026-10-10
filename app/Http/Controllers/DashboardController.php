<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->get('tab') === 'reports') {
            return view('reports.index', app(ReportController::class)->generateReport($request, true));
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);
        $startDate = $validated['start_date'] ?? now('Asia/Manila')->toDateString();
        $endDate = $validated['end_date'] ?? $startDate;
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        if ($end->lessThan($start)) {
            return redirect()->route('dashboard')->withErrors(['end_date' => 'End date must be on or after start date.']);
        }
        $periodLabel = $start->isSameDay($end) ? $start->format('M d, Y') : $start->format('M d, Y').' – '.$end->format('M d, Y');
        $salesQuery = Sale::where('Date', '>=', $start)->where('Date', '<', $end->copy()->startOfDay()->addDay());
        $todaySalesTotal = (clone $salesQuery)->sum('Total');
        $todaySalesCount = (clone $salesQuery)->count();
        $totalSalesAllTime = Sale::sum('Total');
        $totalTransactions = Sale::count();
        $recentSales = (clone $salesQuery)->with(['user', 'paymentMethod', 'soldItems.product'])
            ->orderBy('Date', 'desc')->limit(8)->get();

        $products = Product::with(['category', 'status', 'stockIns'])
            ->whereHas('status', fn ($query) => $query->where('Name', '!=', 'Archived'))->get();
        $productSales = SoldItem::select('Product_ID')
            ->selectRaw('SUM(Quantity) AS units_sold, SUM(Total) AS sales_total')
            ->whereHas('sale', fn ($query) => $query
                ->where('Date', '>=', $start)
                ->where('Date', '<', $end->copy()->startOfDay()->addDay()))
            ->groupBy('Product_ID')->get()->keyBy('Product_ID');
        $totalProductsCount = $products->count();
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $totalStockUnits = 0;
        $inventoryValue = 0;
        foreach ($products as $product) {
            $sales = $productSales->get($product->ID);
            $product->setAttribute('dashboard_units_sold', (float) ($sales?->units_sold ?? 0));
            $product->setAttribute('dashboard_sales_total', (float) ($sales?->sales_total ?? 0));
            $quantity = $product->stock_quantity;
            $product->setAttribute('dashboard_stock', $quantity);
            $totalStockUnits += $quantity;
            $inventoryValue += $quantity * $product->retail_price;
            if ($quantity <= 0) $outOfStockCount++;
            elseif ($quantity <= 5) $lowStockCount++;
        }
        $stockAlerts = $products->filter(fn ($product) => $product->dashboard_stock <= 5)
            ->sortBy('dashboard_stock')->values();
        $bestSellingProducts = $products->filter(fn ($product) => $product->dashboard_units_sold > 0)
            ->sortBy([['dashboard_units_sold', 'desc'], ['Name', 'asc'], ['ID', 'asc']])
            ->take(5)->values();
        $leastSellingProducts = $products
            ->sortBy([['dashboard_units_sold', 'asc'], ['Name', 'asc'], ['ID', 'asc']])
            ->take(5)->values();
        return view('dashboard', compact(
            'startDate', 'endDate', 'periodLabel', 'todaySalesTotal', 'todaySalesCount',
            'totalSalesAllTime', 'totalTransactions', 'totalProductsCount', 'lowStockCount',
            'outOfStockCount', 'totalStockUnits', 'inventoryValue', 'recentSales', 'stockAlerts',
            'bestSellingProducts', 'leastSellingProducts'
        ));
    }
}
