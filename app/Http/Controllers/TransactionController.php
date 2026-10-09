<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TRANSACTIONS INDEX
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        $query = $this->buildQuery($request, $filters);

        // Summaries
        $totalTransactions = (clone $query)->count();
        $totalSales = (float) (clone $query)->sum('Total');

        $cashSales = (float) (clone $query)->whereHas('paymentMethod', function ($paymentQuery) {
            $paymentQuery->where('Name', 'Cash');
        })->sum('Total');

        $gcashSales = (float) (clone $query)->whereHas('paymentMethod', function ($paymentQuery) {
            $paymentQuery->where('Name', 'GCash');
        })->sum('Total');

        // Paginated list with eager-loaded relations
        $sales = $query->with([
            'user',
            'paymentMethod',
            'soldItems.product'
        ])
        ->orderBy('Date', 'desc')
        ->paginate(15)
        ->withQueryString();

        $cashiers = (auth()->user() && auth()->user()->isAdmin())
            ? User::orderBy('name')->get()
            : collect();

        return view('transactions.index', [
            'sales' => $sales,
            'totalTransactions' => $totalTransactions,
            'totalSales' => $totalSales,
            'cashSales' => $cashSales,
            'gcashSales' => $gcashSales,
            'period' => $filters['period'],
            'month' => $filters['month'],
            'year' => $filters['year'],
            'periodLabel' => $this->getPeriodLabel($filters),
            'cashiers' => $cashiers,
            'filters' => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT REPORT
    |--------------------------------------------------------------------------
    */
    public function print(Request $request)
    {
        $filters = $this->getFilters($request);
        $query = $this->buildQuery($request, $filters);

        $sales = $query->with([
            'user',
            'paymentMethod',
            'soldItems.product'
        ])
        ->orderBy('Date', 'asc')
        ->get();

        $totalTransactions = $sales->count();
        $totalSales = (float) $sales->sum('Total');
        $cashSales = (float) $sales->filter(fn ($s) => strtolower($s->paymentMethod->Name ?? '') === 'cash')->sum('Total');
        $gcashSales = (float) $sales->filter(fn ($s) => strtolower($s->paymentMethod->Name ?? '') === 'gcash')->sum('Total');

        return view('transactions.print', [
            'user' => auth()->user(),
            'sales' => $sales,
            'totalTransactions' => $totalTransactions,
            'totalSales' => $totalSales,
            'cashSales' => $cashSales,
            'gcashSales' => $gcashSales,
            'periodLabel' => $this->getPeriodLabel($filters),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT CSV
    |--------------------------------------------------------------------------
    */
    public function exportCsv(Request $request)
    {
        $filters = $this->getFilters($request);
        $query = $this->buildQuery($request, $filters);

        $sales = $query->with([
            'user',
            'paymentMethod',
            'soldItems.product'
        ])
        ->orderBy('Date', 'desc')
        ->get();

        $fileName = 'transactions_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice #',
                'Date & Time',
                'Cashier',
                'Payment Method',
                'GCash Ref #',
                'Items Count',
                'Amount Received (PHP)',
                'Change (PHP)',
                'Total Amount (PHP)',
            ]);

            foreach ($sales as $sale) {
                $totalQty = $sale->soldItems->sum('Quantity');
                fputcsv($handle, [
                    'INV-' . $sale->ID,
                    $sale->Date ? $sale->Date->format('Y-m-d H:i') : '-',
                    $sale->user->name ?? 'Staff',
                    $sale->paymentMethod->Name ?? 'N/A',
                    $sale->GCash_Reference_Number ?? '-',
                    $totalQty,
                    number_format((float) $sale->Amount_Received, 2, '.', ''),
                    number_format((float) $sale->Change_Amount, 2, '.', ''),
                    number_format((float) $sale->Total, 2, '.', ''),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BUILDER WITH FILTERS
    |--------------------------------------------------------------------------
    */
    private function buildQuery(Request $request, array $filters): Builder
    {
        $query = Sale::query();

        // Cashier filter (Admin can view all or filter by staff; Employee is restricted to own sales)
        if (auth()->user() && auth()->user()->isAdmin()) {
            if (!empty($filters['cashier_id']) && $filters['cashier_id'] !== 'all') {
                $query->where('User_ID', $filters['cashier_id']);
            }
        } else {
            $query->where('User_ID', auth()->id());
        }

        // Apply Date / Period
        $this->applyPeriod($query, $filters);

        // Search by Invoice # or GCash reference
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $numOnly = preg_replace('/[^0-9]/', '', $term);

            $query->where(function ($q) use ($term, $numOnly) {
                if ($numOnly !== '') {
                    $q->where('ID', (int) $numOnly);
                }
                $q->orWhere('GCash_Reference_Number', 'like', "%{$term}%");
            });
        }

        // Filter by Payment Method
        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->whereHas('paymentMethod', function ($q) use ($filters) {
                $q->where('Name', $filters['payment_method']);
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | GET FILTERS FROM REQUEST
    |--------------------------------------------------------------------------
    */
    private function getFilters(Request $request): array
    {
        $period = $request->get('period', 'monthly');
        if (!in_array($period, ['today', 'monthly', 'yearly', 'overall'], true)) {
            $period = 'monthly';
        }

        return [
            'period' => $period,
            'month' => $request->get('month', now()->format('Y-m')),
            'year' => (int) $request->get('year', now()->year),
            'search' => $request->get('search', ''),
            'payment_method' => $request->get('payment_method', ''),
            'cashier_id' => $request->get('cashier_id', ''),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY DATE FILTER
    |--------------------------------------------------------------------------
    */
    private function applyPeriod(Builder $query, array $filters): void
    {
        if ($filters['period'] === 'today') {
            $query->whereDate('Date', today());
        } elseif ($filters['period'] === 'monthly') {
            $month = $this->parseMonth($filters['month']);
            $query->whereBetween('Date', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);
        } elseif ($filters['period'] === 'yearly') {
            $query->whereYear('Date', $filters['year']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERIOD LABEL
    |--------------------------------------------------------------------------
    */
    private function getPeriodLabel(array $filters): string
    {
        if ($filters['period'] === 'today') {
            return 'Today (' . now()->format('F d, Y') . ')';
        }
        if ($filters['period'] === 'overall') {
            return 'Overall (All-Time)';
        }
        if ($filters['period'] === 'yearly') {
            return 'Year ' . (string) $filters['year'];
        }

        return $this->parseMonth($filters['month'])->format('F Y');
    }

    /*
    |--------------------------------------------------------------------------
    | SAFE MONTH PARSER
    |--------------------------------------------------------------------------
    */
    private function parseMonth(string $month): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $month . '-01');
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }
}
