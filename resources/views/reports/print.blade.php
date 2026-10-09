<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }} - {{ $periodLabel }} (PDF)</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f8fafc;
        }

        .report-page {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        /* Top Action Bar (Hidden when printed or exported to PDF) */
        .action-bar {
            max-width: 1100px;
            margin: 0 auto 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .action-bar .info-text {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #cbd5e1;
        }

        .action-bar .info-badge {
            background: #1e293b;
            color: #38bdf8;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
        }

        .action-bar .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: #dc2626;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: #b91c1c;
        }

        .btn-close {
            background: #334155;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-close:hover {
            background: #475569;
        }

        /* Official Document Header */
        .doc-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .doc-header .company-brand h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .doc-header .company-brand p {
            margin: 2px 0 0 0;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .doc-header .report-meta {
            text-align: right;
        }

        .doc-header .report-meta h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .doc-header .report-meta .period-tag {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            background: #e2e8f0;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10px;
            color: #334155;
        }

        .meta-strip {
            display: flex;
            justify-content: space-between;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 10px;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .section-heading {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin: 20px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        /* Summary KPI Cards */
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .summary-grid th,
        .summary-grid td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .summary-grid th {
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }

        .summary-grid td {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Data Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 10.5px;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .report-table th {
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            text-align: left;
        }

        .report-table tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; font-weight: 700; }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-good { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-low { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-out { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Document Footer & Sign-off */
        .doc-footer {
            margin-top: 36px;
            padding-top: 14px;
            border-top: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }

        .security-notice {
            font-size: 9px;
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-strip {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .signature-box {
            width: 240px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            margin-bottom: 4px;
        }

        .signature-title {
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
        }

        .signature-subtitle {
            font-size: 9px;
            color: #64748b;
        }

        /* Print Media Styles */
        @media print {
            .no-print,
            .action-bar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .report-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
@if($downloadPdf ?? false)
<style>@page {margin:10mm;} body, table, .font-mono { font-family:'DejaVu Sans', sans-serif; } .doc-header,.meta-strip,.footer-signatures {display:block;width:100%;} .company-brand,.report-meta,.signature-box {display:block;width:100%;margin-bottom:12px;}</style>
@endif
</head>
<body>

    @include('shared.print-actions')

    <!-- MAIN REPORT PAGE -->
    <div class="report-page">

        <!-- HEADER -->
        <div class="doc-header">
            <div class="company-brand">
                <h1>PAOLO PAOLO</h1>
                <p>D.A Matting &amp; Accessories &bull; Official Store Audit</p>
            </div>
            <div class="report-meta">
                <h2>{{ $reportTitle }}</h2>
                <div class="period-tag">{{ $periodLabel }}</div>
            </div>
        </div>

        <!-- META INFORMATION STRIP -->
        <div class="meta-strip">
            <div>
                <strong>Generated By:</strong> {{ auth()->user()->name ?? 'Administrator' }} ({{ auth()->user()->role ?? 'Admin' }})
            </div>
            <div>
                <strong>Generated On:</strong> {{ now()->timezone('Asia/Manila')->format('F d, Y h:i:s A') }}
            </div>
            <div>
                <strong>Security ID:</strong> PP-REP-{{ strtoupper(substr(md5(now()->toDateTimeString()), 0, 8)) }}
            </div>
        </div>

        @if($scope !== 'inventory')
        <!-- SALES SUMMARY -->
        <div class="section-heading">Sales Summary</div>
        <table class="summary-grid">
            <thead>
                <tr>
                    <th>Total Sales Revenue</th>
                    <th>Completed Transactions</th>
                    <th>Cash Payments</th>
                    <th>GCash Payments</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>₱{{ number_format($totalSales, 2) }}</td>
                    <td>{{ number_format($totalTransactions) }}</td>
                    <td>₱{{ number_format($cashSales, 2) }}</td>
                    <td>₱{{ number_format($gcashSales, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- SALES TRANSACTIONS TABLE -->
        <div class="section-heading">Sales Transactions Detail ({{ $sales->count() }} records)</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Invoice #</th>
                    <th style="width: 20%;">Date &amp; Time</th>
                    <th style="width: 22%;">Cashier</th>
                    <th class="text-center" style="width: 10%;">Items</th>
                    <th style="width: 16%;">Payment</th>
                    <th class="text-right" style="width: 18%;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td class="font-mono">INV-{{ $sale->ID }}</td>
                    <td>{{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : '-' }}</td>
                    <td>{{ $sale->user->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $sale->soldItems->sum('Quantity') }}</td>
                    <td>{{ $sale->paymentMethod->Name ?? 'Cash' }}</td>
                    <td class="text-right font-mono">₱{{ number_format($sale->Total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No transactions recorded for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        @if($scope === 'inventory')
        <!-- INVENTORY SUMMARY KPIS -->
        <div class="section-heading">Inventory &amp; Valuation Summary</div>
        <table class="summary-grid">
            <thead>
                <tr>
                    <th>Catalog Products</th>
                    <th>Total Stock Units</th>
                    <th>Cost Valuation</th>
                    <th>Retail Valuation</th>
                    <th>Est. Gross Margin</th>
                    <th>Low Stock</th>
                    <th>Out of Stock</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($totalProducts) }}</td>
                    <td>{{ number_format($totalStockUnits, 0) }}</td>
                    <td>₱{{ number_format($inventoryCostValue, 2) }}</td>
                    <td>₱{{ number_format($inventoryRetailValue, 2) }}</td>
                    <td>₱{{ number_format($potentialProfit, 2) }}</td>
                    <td>{{ $lowStockCount }}</td>
                    <td>{{ $outOfStockCount }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        @if($scope === 'inventory' || $scope === 'business')
        <!-- INVENTORY VALUATION TABLE -->
        <div class="section-heading">Current Stock Valuation ({{ $products->count() }} products)</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Product Name</th>
                    <th style="width: 17%;">Category</th>
                    <th class="text-right" style="width: 10%;">Units</th>
                    <th class="text-right" style="width: 12%;">Unit Cost</th>
                    <th class="text-right" style="width: 12%;">Unit Retail</th>
                    <th class="text-right" style="width: 12%;">Total Cost</th>
                    <th class="text-right" style="width: 12%;">Total Retail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $prod)
                @php
                    $qty = (float) $prod->stock_quantity;
                    $cPrice = (float) $prod->cost_price;
                    $rPrice = (float) $prod->retail_price;
                @endphp
                <tr>
                    <td><strong>{{ $prod->Name }}</strong></td>
                    <td>{{ $prod->category->Name ?? 'N/A' }}</td>
                    <td class="text-right font-mono">{{ number_format($qty, 0) }}</td>
                    <td class="text-right">₱{{ number_format($cPrice, 2) }}</td>
                    <td class="text-right">₱{{ number_format($rPrice, 2) }}</td>
                    <td class="text-right font-mono">₱{{ number_format($qty * $cPrice, 2) }}</td>
                    <td class="text-right font-mono">₱{{ number_format($qty * $rPrice, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No products found.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="font-weight: 800; background: #f1f5f9;">
                    <td colspan="2">TOTAL INVENTORY SUMMARY</td>
                    <td class="text-right font-mono">{{ number_format($totalStockUnits, 0) }}</td>
                    <td colspan="2"></td>
                    <td class="text-right font-mono">₱{{ number_format($inventoryCostValue, 2) }}</td>
                    <td class="text-right font-mono">₱{{ number_format($inventoryRetailValue, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- STOCK-IN DELIVERIES TABLE -->
        <div class="section-heading">Stock-In Deliveries Received ({{ $periodLabel }})</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Batch ID</th>
                    <th style="width: 25%;">Product</th>
                    <th style="width: 16%;">Date Received</th>
                    <th style="width: 15%;">Received By</th>
                    <th class="text-right" style="width: 10%;">Quantity</th>
                    <th class="text-right" style="width: 10%;">Unit Cost</th>
                    <th class="text-right" style="width: 12%;">Batch Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockIns as $si)
                <tr>
                    <td class="font-mono">#SI-{{ $si->ID }}</td>
                    <td>{{ $si->product->Name ?? '-' }}</td>
                    <td>{{ $si->created_at ? $si->created_at->format('M d, Y h:i A') : '-' }}</td>
                    <td>{{ $si->user->name ?? 'System' }}</td>
                    <td class="text-right font-mono">+{{ number_format($si->Quantity, 0) }}</td>
                    <td class="text-right">₱{{ number_format($si->Cost_Price, 2) }}</td>
                    <td class="text-right font-mono">₱{{ number_format($si->Quantity * $si->Cost_Price, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No stock-in records found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        <!-- OFFICIAL SIGN-OFF FOOTER -->
        <div class="doc-footer">
            <div class="security-notice">
                Official System-Generated Audit Report &bull; Tamper-Evident &bull; Alterations Invalidate This Document
            </div>

            <div class="signature-strip">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">{{ auth()->user()->name ?? 'Administrator' }}</div>
                    <div class="signature-subtitle">Prepared by (Staff / Admin)</div>
                </div>

                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">PAOLO PAOLO MANAGEMENT</div>
                    <div class="signature-subtitle">Verified &amp; Approved by (Owner)</div>
                </div>
            </div>
        </div>

    </div>


</body>
</html>
