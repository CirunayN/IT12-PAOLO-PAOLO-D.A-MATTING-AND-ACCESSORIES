<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
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
        $data = $this->generateReport(
            $request,
            true
        );


        return view(
            'reports.index',
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN PRINT REPORT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $data = $this->generateReport(
            $request,
            false
        );


        return view(
            'reports.print',
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE REPORT
    |--------------------------------------------------------------------------
    */

    private function generateReport(
        Request $request,
        bool $paginate
    ): array {

        /*
        |--------------------------------------------------------------------------
        | REPORT SCOPE
        |--------------------------------------------------------------------------
        |
        | business = entire business
        | employee = one selected employee
        |
        */

        $scope =
            $request->get(
                'scope',
                'business'
            );


        if (
            !in_array(
                $scope,
                [
                    'business',
                    'employee'
                ],
                true
            )
        ) {
            $scope =
                'business';
        }


        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */

        $selectedEmployee =
            null;


        $employeeId =
            $request->get(
                'employee_id'
            );


        if (
            $scope === 'employee' &&
            $employeeId
        ) {

            $selectedEmployee =
                User::find(
                    $employeeId
                );


            /*
             * Invalid employee means fall back
             * to business report.
             */
            if (
                !$selectedEmployee
            ) {

                $scope =
                    'business';

            }

        } elseif (
            $scope === 'employee'
        ) {

            $scope =
                'business';

        }


        /*
        |--------------------------------------------------------------------------
        | PERIOD
        |--------------------------------------------------------------------------
        */

        $period =
            $request->get(
                'period',
                'monthly'
            );


        if (
            !in_array(
                $period,
                [
                    'monthly',
                    'yearly',
                    'overall'
                ],
                true
            )
        ) {

            $period =
                'monthly';

        }


        $month =
            $request->get(
                'month',
                now()->format('Y-m')
            );


        $year =
            (int) $request->get(
                'year',
                now()->year
            );


        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE LIST
        |--------------------------------------------------------------------------
        */

        $employees =
            User::whereHas(
                'sales'
            )
            ->orderBy(
                'name'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        */

        $salesQuery =
            Sale::query();


        /*
         * Employee-specific Admin report.
         */
        if (
            $scope === 'employee' &&
            $selectedEmployee
        ) {

            $salesQuery->where(
                'User_ID',
                $selectedEmployee->id
            );

        }


        $this->applySalePeriod(
            $salesQuery,
            $period,
            $month,
            $year
        );


        /*
        |--------------------------------------------------------------------------
        | SALES SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalTransactions =
            (clone $salesQuery)
                ->count();


        $totalSales =
            (float) (clone $salesQuery)
                ->sum('Total');


        $cashSales =
            (float) (clone $salesQuery)
                ->whereHas(
                    'paymentMethod',
                    function ($query) {

                        $query->where(
                            'Name',
                            'Cash'
                        );

                    }
                )
                ->sum('Total');


        $gcashSales =
            (float) (clone $salesQuery)
                ->whereHas(
                    'paymentMethod',
                    function ($query) {

                        $query->where(
                            'Name',
                            'GCash'
                        );

                    }
                )
                ->sum('Total');


        /*
        |--------------------------------------------------------------------------
        | SALES TABLE
        |--------------------------------------------------------------------------
        */

        $salesQuery
            ->with([
                'user',
                'paymentMethod',
                'soldItems.product'
            ])
            ->orderBy(
                'Date',
                'desc'
            );


        if ($paginate) {

            $sales =
                $salesQuery
                    ->paginate(
                        15,
                        ['*'],
                        'sales_page'
                    )
                    ->withQueryString();

        } else {

            $sales =
                $salesQuery
                    ->get();

        }


        /*
        |--------------------------------------------------------------------------
        | BUSINESS-ONLY VARIABLES
        |--------------------------------------------------------------------------
        */

        $stockIns =
            collect();


        $products =
            collect();


        $stockInBatches =
            0;


        $unitsReceived =
            0;


        $stockInCost =
            0;


        $totalProducts =
            0;


        $totalStockUnits =
            0;


        $inventoryCostValue =
            0;


        $inventoryRetailValue =
            0;


        $lowStockCount =
            0;


        $outOfStockCount =
            0;


        /*
        |--------------------------------------------------------------------------
        | BUSINESS REPORT
        |--------------------------------------------------------------------------
        |
        | These sections are only included when Admin selects
        | Business Report.
        |
        */

        if (
            $scope ===
            'business'
        ) {

            /*
            |--------------------------------------------------------------------------
            | STOCK-IN QUERY
            |--------------------------------------------------------------------------
            */

            $stockQuery =
                StockIn::query();


            $this->applyStockPeriod(
                $stockQuery,
                $period,
                $month,
                $year
            );


            /*
            |--------------------------------------------------------------------------
            | STOCK-IN SUMMARY
            |--------------------------------------------------------------------------
            */

            $stockInBatches =
                (clone $stockQuery)
                    ->count();


            $unitsReceived =
                (float) (clone $stockQuery)
                    ->sum(
                        'Quantity'
                    );


            $stockInCost =
                (float) (
                    (clone $stockQuery)
                        ->selectRaw(
                            'SUM(Quantity * Cost_Price) AS total_stock_cost'
                        )
                        ->value(
                            'total_stock_cost'
                        )
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | STOCK-IN TABLE
            |--------------------------------------------------------------------------
            */

            $stockQuery
                ->with([
                    'product.category',
                    'user'
                ])
                ->orderBy(
                    'created_at',
                    'desc'
                );


            if ($paginate) {

                $stockIns =
                    $stockQuery
                        ->paginate(
                            15,
                            ['*'],
                            'stock_page'
                        )
                        ->withQueryString();

            } else {

                $stockIns =
                    $stockQuery
                        ->get();

            }


            /*
            |--------------------------------------------------------------------------
            | CURRENT INVENTORY
            |--------------------------------------------------------------------------
            |
            | This is intentionally a CURRENT snapshot.
            |
            | Monthly/yearly filters only apply to transactions
            | and stock-in records.
            |
            */

            $products =
                Product::with([
                    'category',
                    'stockIns',
                    'soldItems'
                ])
                ->orderBy(
                    'Name'
                )
                ->get();


            $totalProducts =
                $products->count();


            foreach (
                $products as $product
            ) {

                $quantity =
                    (float)
                    $product->stock_quantity;


                $cost =
                    (float)
                    $product->cost_price;


                $retail =
                    (float)
                    $product->retail_price;


                $totalStockUnits +=
                    $quantity;


                $inventoryCostValue +=
                    $quantity *
                    $cost;


                $inventoryRetailValue +=
                    $quantity *
                    $retail;


                if (
                    $quantity <= 0
                ) {

                    $outOfStockCount++;

                } elseif (
                    $quantity <= 5
                ) {

                    $lowStockCount++;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | REPORT LABEL
        |--------------------------------------------------------------------------
        */

        $periodLabel =
            $this->getPeriodLabel(
                $period,
                $month,
                $year
            );


        if (
            $scope === 'business'
        ) {

            $reportTitle =
                'Business Report';

        } else {

            $reportTitle =
                ($selectedEmployee->name ?? 'Employee')
                . ' - Employee Report';

        }


        return [

            /*
            | Scope
            */

            'scope' =>
                $scope,

            'selectedEmployee' =>
                $selectedEmployee,

            'employees' =>
                $employees,

            'reportTitle' =>
                $reportTitle,


            /*
            | Period
            */

            'period' =>
                $period,

            'month' =>
                $month,

            'year' =>
                $year,

            'periodLabel' =>
                $periodLabel,


            /*
            | Sales
            */

            'sales' =>
                $sales,

            'totalTransactions' =>
                $totalTransactions,

            'totalSales' =>
                $totalSales,

            'cashSales' =>
                $cashSales,

            'gcashSales' =>
                $gcashSales,


            /*
            | Business only
            */

            'stockIns' =>
                $stockIns,

            'stockInBatches' =>
                $stockInBatches,

            'unitsReceived' =>
                $unitsReceived,

            'stockInCost' =>
                $stockInCost,

            'products' =>
                $products,

            'totalProducts' =>
                $totalProducts,

            'totalStockUnits' =>
                $totalStockUnits,

            'inventoryCostValue' =>
                $inventoryCostValue,

            'inventoryRetailValue' =>
                $inventoryRetailValue,

            'lowStockCount' =>
                $lowStockCount,

            'outOfStockCount' =>
                $outOfStockCount,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SALES PERIOD
    |--------------------------------------------------------------------------
    */

    private function applySalePeriod(
        Builder $query,
        string $period,
        string $month,
        int $year
    ): void {

        if (
            $period ===
            'monthly'
        ) {

            $selectedMonth =
                $this->parseMonth(
                    $month
                );


            $query->whereBetween(
                'Date',
                [
                    $selectedMonth
                        ->copy()
                        ->startOfMonth(),

                    $selectedMonth
                        ->copy()
                        ->endOfMonth()
                ]
            );

        }


        if (
            $period ===
            'yearly'
        ) {

            $query->whereYear(
                'Date',
                $year
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | STOCK-IN PERIOD
    |--------------------------------------------------------------------------
    */

    private function applyStockPeriod(
        Builder $query,
        string $period,
        string $month,
        int $year
    ): void {

        if (
            $period ===
            'monthly'
        ) {

            $selectedMonth =
                $this->parseMonth(
                    $month
                );


            $query->whereBetween(
                'created_at',
                [
                    $selectedMonth
                        ->copy()
                        ->startOfMonth(),

                    $selectedMonth
                        ->copy()
                        ->endOfMonth()
                ]
            );

        }


        if (
            $period ===
            'yearly'
        ) {

            $query->whereYear(
                'created_at',
                $year
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PERIOD LABEL
    |--------------------------------------------------------------------------
    */

    private function getPeriodLabel(
        string $period,
        string $month,
        int $year
    ): string {

        if (
            $period ===
            'overall'
        ) {

            return 'Overall';

        }


        if (
            $period ===
            'yearly'
        ) {

            return (string)
                $year;

        }


        return $this
            ->parseMonth(
                $month
            )
            ->format(
                'F Y'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SAFE MONTH PARSER
    |--------------------------------------------------------------------------
    */

    private function parseMonth(
        string $month
    ): Carbon {

        try {

            return Carbon::createFromFormat(
                'Y-m-d',
                $month . '-01'
            );

        } catch (\Throwable $e) {

            return now()
                ->startOfMonth();

        }
    }
}
