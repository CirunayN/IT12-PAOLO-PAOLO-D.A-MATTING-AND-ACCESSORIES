<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | MY TRANSACTIONS PAGE
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $filters = $this->getFilters(
            $request
        );


        /*
         * IMPORTANT:
         *
         * Always use the currently logged-in user.
         *
         * No user ID is accepted from the browser.
         */
        $query = Sale::query()
            ->where(
                'User_ID',
                auth()->id()
            );


        $this->applyPeriod(
            $query,
            $filters
        );


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalTransactions =
            (clone $query)
                ->count();


        $totalSales =
            (float) (clone $query)
                ->sum('Total');


        $cashSales =
            (float) (clone $query)
                ->whereHas(
                    'paymentMethod',
                    function ($paymentQuery) {

                        $paymentQuery->where(
                            'Name',
                            'Cash'
                        );

                    }
                )
                ->sum('Total');


        $gcashSales =
            (float) (clone $query)
                ->whereHas(
                    'paymentMethod',
                    function ($paymentQuery) {

                        $paymentQuery->where(
                            'Name',
                            'GCash'
                        );

                    }
                )
                ->sum('Total');


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION LIST
        |--------------------------------------------------------------------------
        */

        $sales = $query
            ->with([
                'paymentMethod',
                'soldItems.product'
            ])
            ->orderBy(
                'Date',
                'desc'
            )
            ->paginate(15)
            ->withQueryString();


        return view(
            'transactions.index',
            [
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

                'period' =>
                    $filters['period'],

                'month' =>
                    $filters['month'],

                'year' =>
                    $filters['year'],

                'periodLabel' =>
                    $this->getPeriodLabel(
                        $filters
                    ),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRINT MY REPORT
    |--------------------------------------------------------------------------
    */

    public function print(Request $request)
    {
        $filters = $this->getFilters(
            $request
        );


        /*
         * Again, only the current user's transactions.
         */
        $query = Sale::query()
            ->where(
                'User_ID',
                auth()->id()
            );


        $this->applyPeriod(
            $query,
            $filters
        );


        $sales = $query
            ->with([
                'paymentMethod',
                'soldItems.product'
            ])
            ->orderBy(
                'Date',
                'asc'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalTransactions =
            $sales->count();


        $totalSales =
            (float) $sales->sum(
                'Total'
            );


        $cashSales =
            (float) $sales
                ->filter(
                    function ($sale) {

                        return strtolower(
                            $sale
                                ->paymentMethod
                                ->Name
                                ?? ''
                        ) === 'cash';

                    }
                )
                ->sum('Total');


        $gcashSales =
            (float) $sales
                ->filter(
                    function ($sale) {

                        return strtolower(
                            $sale
                                ->paymentMethod
                                ->Name
                                ?? ''
                        ) === 'gcash';

                    }
                )
                ->sum('Total');


        return view(
            'transactions.print',
            [
                'user' =>
                    auth()->user(),

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

                'periodLabel' =>
                    $this->getPeriodLabel(
                        $filters
                    ),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET FILTERS
    |--------------------------------------------------------------------------
    */

    private function getFilters(
        Request $request
    ): array {

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


        return [
            'period' =>
                $period,

            'month' =>
                $month,

            'year' =>
                $year,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | APPLY DATE FILTER
    |--------------------------------------------------------------------------
    */

    private function applyPeriod(
        Builder $query,
        array $filters
    ): void {

        /*
        |--------------------------------------------------------------------------
        | MONTHLY
        |--------------------------------------------------------------------------
        */

        if (
            $filters['period'] ===
            'monthly'
        ) {

            $month =
                $this->parseMonth(
                    $filters['month']
                );


            $query->whereBetween(
                'Date',
                [
                    $month
                        ->copy()
                        ->startOfMonth(),

                    $month
                        ->copy()
                        ->endOfMonth()
                ]
            );

        }


        /*
        |--------------------------------------------------------------------------
        | YEARLY
        |--------------------------------------------------------------------------
        */

        if (
            $filters['period'] ===
            'yearly'
        ) {

            $query->whereYear(
                'Date',
                $filters['year']
            );

        }


        /*
         * Overall:
         * no WHERE date filter.
         */
    }


    /*
    |--------------------------------------------------------------------------
    | PERIOD LABEL
    |--------------------------------------------------------------------------
    */

    private function getPeriodLabel(
        array $filters
    ): string {

        if (
            $filters['period'] ===
            'overall'
        ) {
            return 'Overall';
        }


        if (
            $filters['period'] ===
            'yearly'
        ) {
            return (string)
                $filters['year'];
        }


        return $this
            ->parseMonth(
                $filters['month']
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
