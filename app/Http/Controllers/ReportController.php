<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN REPORT PAGE
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $data = $this->generateReport($request, true);

        return view('reports.index', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN PRINT REPORT
    |--------------------------------------------------------------------------
    */
    public function print(Request $request)
    {
        $data = $this->generateReport($request, false);

        return view('reports.print', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE REPORT DATA
    |--------------------------------------------------------------------------
    */
    public function generateReport(Request $request, bool $paginate): array
    {
        /*
        |--------------------------------------------------------------------------
        | REPORT SCOPE
        |--------------------------------------------------------------------------
        | business  = entire business (sales, stock-in, inventory)
        | inventory = dedicated stock & valuation report
        | employee  = one selected employee's sales
        */
        $scope = $request->get('scope', 'business');
        if (!in_array($scope, ['business', 'inventory', 'employee'], true)) {
            $scope = 'business';
        }

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE (FOR EMPLOYEE SCOPE)
        |--------------------------------------------------------------------------
        */
        $selectedEmployee = null;
        $employeeId = $request->get('employee_id');

        if ($scope === 'employee' && $employeeId) {
            $selectedEmployee = User::find($employeeId);
            if (!$selectedEmployee) {
                $scope = 'business';
            }
        } elseif ($scope === 'employee') {
            $scope = 'business';
        }

        /*
        |--------------------------------------------------------------------------
        | PERIOD & DATE FILTERS
        |--------------------------------------------------------------------------
        */
        $period = $request->get('period', 'monthly');
        if (!in_array($period, ['weekly', 'monthly', 'yearly', 'overall'], true)) {
            $period = 'monthly';
        }

        $week = $request->get('week', now()->format('Y-\WW'));
        $month = $request->get('month', now()->format('Y-m'));
        $year = (int) $request->get('year', now()->year);

        $employees = User::whereHas('sales')
            ->orderBy('name')
            ->get();

        $categories = Category::orderBy('Name')->get();
        $selectedCategoryId = $request->get('category_id');
        $selectedStockStatus = $request->get('stock_status');

        /*
        |--------------------------------------------------------------------------
        | SALES DATA (FOR BUSINESS & EMPLOYEE SCOPES)
        |--------------------------------------------------------------------------
        */
        $sales = collect();
        $totalTransactions = 0;
        $totalSales = 0.0;
        $cashSales = 0.0;
        $gcashSales = 0.0;

        if ($scope !== 'inventory') {
            $salesQuery = Sale::query();

            if ($scope === 'employee' && $selectedEmployee) {
                $salesQuery->where('User_ID', $selectedEmployee->id);
            }

            $this->applySalePeriod($salesQuery, $period, $month, $year, $week);

            $totalTransactions = (clone $salesQuery)->count();
            $totalSales = (float) (clone $salesQuery)->sum('Total');

            $cashSales = (float) (clone $salesQuery)
                ->whereHas('paymentMethod', fn($q) => $q->where('Name', 'Cash'))
                ->sum('Total');

            $gcashSales = (float) (clone $salesQuery)
                ->whereHas('paymentMethod', fn($q) => $q->where('Name', 'GCash'))
                ->sum('Total');

            $salesQuery->with(['user', 'paymentMethod', 'soldItems.product'])
                ->orderBy('Date', 'desc');

            if ($paginate) {
                $sales = $salesQuery->paginate(15, ['*'], 'sales_page')->withQueryString();
            } else {
                $sales = $salesQuery->get();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK-IN & INVENTORY DATA (FOR BUSINESS & INVENTORY SCOPES)
        |--------------------------------------------------------------------------
        */
        $stockIns = collect();
        $products = collect();
        $stockInBatches = 0;
        $unitsReceived = 0.0;
        $stockInCost = 0.0;
        $totalProducts = 0;
        $totalStockUnits = 0.0;
        $inventoryCostValue = 0.0;
        $inventoryRetailValue = 0.0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        if ($scope === 'business' || $scope === 'inventory') {
            // Stock-In Records for the selected period
            $stockQuery = StockIn::query();
            $this->applyStockPeriod($stockQuery, $period, $month, $year, $week);

            if ($scope === 'inventory' && $selectedCategoryId) {
                $stockQuery->whereHas('product', fn($q) => $q->where('Category_ID', $selectedCategoryId));
            }

            $stockInBatches = (clone $stockQuery)->count();
            $unitsReceived = (float) (clone $stockQuery)->sum('Quantity');
            $stockInCost = (float) ((clone $stockQuery)
                ->selectRaw('SUM(Quantity * Cost_Price) AS total_stock_cost')
                ->value('total_stock_cost') ?? 0);

            $stockQuery->with(['product.category', 'user'])
                ->orderBy('created_at', 'desc');

            if ($paginate && $scope !== 'inventory') {
                $stockIns = $stockQuery->paginate(15, ['*'], 'stock_page')->withQueryString();
            } else {
                $stockIns = $stockQuery->get();
            }

            // Current Inventory Snapshot
            $archivedStatus = Status::firstOrCreate(['Name' => 'Archived']);
            $productQuery = Product::with(['category', 'stockIns', 'soldItems'])
                ->where('Status_ID', '!=', $archivedStatus->ID);

            if ($selectedCategoryId) {
                $productQuery->where('Category_ID', $selectedCategoryId);
            }

            $allCatalogProducts = $productQuery->orderBy('Name')->get();

            // Filter by Stock Status if requested
            if ($selectedStockStatus) {
                $products = $allCatalogProducts->filter(function ($p) use ($selectedStockStatus) {
                    $qty = $p->stock_quantity;
                    if ($selectedStockStatus === 'available') return $qty > 5;
                    if ($selectedStockStatus === 'low') return $qty > 0 && $qty <= 5;
                    if ($selectedStockStatus === 'out') return $qty <= 0;
                    return true;
                })->values();
            } else {
                $products = $allCatalogProducts;
            }

            $totalProducts = $products->count();

            foreach ($products as $product) {
                $quantity = (float) $product->stock_quantity;
                $cost = (float) $product->cost_price;
                $retail = (float) $product->retail_price;

                $totalStockUnits += $quantity;
                $inventoryCostValue += ($quantity * $cost);
                $inventoryRetailValue += ($quantity * $retail);

                if ($quantity <= 0) {
                    $outOfStockCount++;
                } elseif ($quantity <= 5) {
                    $lowStockCount++;
                }
            }
        }

        $potentialProfit = $inventoryRetailValue - $inventoryCostValue;
        $periodLabel = $this->getPeriodLabel($period, $month, $year, $week);

        if ($scope === 'inventory') {
            $reportTitle = 'Inventory & Stock Valuation Report';
        } elseif ($scope === 'business') {
            $reportTitle = 'Business Performance Report';
        } else {
            $reportTitle = ($selectedEmployee->name ?? 'Employee') . ' - Employee Sales Report';
        }

        return [
            // Scope & Meta
            'scope'                => $scope,
            'selectedEmployee'     => $selectedEmployee,
            'employees'            => $employees,
            'reportTitle'          => $reportTitle,
            'period'               => $period,
            'week'                 => $week,
            'month'                => $month,
            'year'                 => $year,
            'periodLabel'          => $periodLabel,
            'categories'           => $categories,
            'selectedCategoryId'   => $selectedCategoryId,
            'selectedStockStatus'  => $selectedStockStatus,

            // Sales Metrics
            'sales'                => $sales,
            'totalTransactions'    => $totalTransactions,
            'totalSales'           => $totalSales,
            'cashSales'            => $cashSales,
            'gcashSales'           => $gcashSales,

            // Inventory & Stock-in Metrics
            'stockIns'             => $stockIns,
            'stockInBatches'       => $stockInBatches,
            'unitsReceived'        => $unitsReceived,
            'stockInCost'          => $stockInCost,
            'products'             => $products,
            'totalProducts'        => $totalProducts,
            'totalStockUnits'      => $totalStockUnits,
            'inventoryCostValue'   => $inventoryCostValue,
            'inventoryRetailValue' => $inventoryRetailValue,
            'potentialProfit'      => $potentialProfit,
            'lowStockCount'        => $lowStockCount,
            'outOfStockCount'      => $outOfStockCount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PERIOD FILTER HELPERS
    |--------------------------------------------------------------------------
    */
    private function applySalePeriod(
        Builder $query,
        string $period,
        string $month,
        int $year,
        string $week = ''
    ): void {
        if ($period === 'weekly') {
            [$start, $end] = $this->parseWeek($week);
            $query->whereBetween('Date', [$start, $end]);
        } elseif ($period === 'monthly') {
            $selectedMonth = $this->parseMonth($month);
            $query->whereBetween('Date', [
                $selectedMonth->copy()->startOfMonth(),
                $selectedMonth->copy()->endOfMonth(),
            ]);
        } elseif ($period === 'yearly') {
            $query->whereYear('Date', $year);
        }
    }

    private function applyStockPeriod(
        Builder $query,
        string $period,
        string $month,
        int $year,
        string $week = ''
    ): void {
        if ($period === 'weekly') {
            [$start, $end] = $this->parseWeek($week);
            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($period === 'monthly') {
            $selectedMonth = $this->parseMonth($month);
            $query->whereBetween('created_at', [
                $selectedMonth->copy()->startOfMonth(),
                $selectedMonth->copy()->endOfMonth(),
            ]);
        } elseif ($period === 'yearly') {
            $query->whereYear('created_at', $year);
        }
    }

    private function getPeriodLabel(
        string $period,
        string $month,
        int $year,
        string $week = ''
    ): string {
        if ($period === 'overall') {
            return 'Overall History';
        }

        if ($period === 'weekly') {
            [$start, $end] = $this->parseWeek($week);
            return 'Week ' . $start->isoWeek() . ' (' . $start->format('M d') . ' - ' . $end->format('M d, Y') . ')';
        }

        if ($period === 'yearly') {
            return 'Year ' . $year;
        }

        return $this->parseMonth($month)->format('F Y');
    }

    private function parseMonth(string $month): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $month . '-01');
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }

    private function parseWeek(string $week): array
    {
        try {
            if (preg_match('/^(\d{4})-W(\d{1,2})$/', $week, $matches)) {
                $year = (int) $matches[1];
                $weekNum = (int) $matches[2];
                $start = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
                $end = Carbon::now()->setISODate($year, $weekNum)->endOfWeek();

                return [$start, $end];
            }
        } catch (\Throwable $e) {}

        return [now()->startOfWeek(), now()->endOfWeek()];
    }
}
