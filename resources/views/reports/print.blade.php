<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $reportTitle }} - {{ $periodLabel }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
        }


        .header {
            text-align: center;
            margin-bottom: 25px;
        }


        .header h1 {
            margin: 0;
            font-size: 20px;
        }


        .header h2 {
            margin: 5px 0;
            font-size: 15px;
        }


        .header p {
            margin: 3px 0;
        }


        .section-title {
            margin-top: 25px;
            margin-bottom: 8px;
            font-size: 14px;
        }


        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }


        .summary th,
        .summary td {
            border: 1px solid #aaa;
            padding: 8px;
        }


        .summary th {
            background: #eee;
            text-align: left;
        }


        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }


        .report-table th,
        .report-table td {
            border: 1px solid #aaa;
            padding: 6px;
            vertical-align: top;
        }


        .report-table th {
            background: #eee;
        }


        .right {
            text-align: right;
        }


        .center {
            text-align: center;
        }


        .print-button {
            padding: 10px 18px;
            margin-bottom: 20px;
            cursor: pointer;
        }


        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }


        .signature {
            width: 220px;
            border-top: 1px solid #111;
            padding-top: 5px;
            text-align: center;
        }


        @media print {

            .no-print {
                display: none;
            }


            body {
                padding: 0;
            }


            @page {
                size: A4 landscape;
                margin: 12mm;
            }

        }

    </style>

</head>


<body>


<button
    class="print-button no-print"
    onclick="window.print()"
>
    Print Report
</button>


<div class="header">

    <h1>
        PAOLO PAOLO D.A. MATTING &amp; ACCESSORIES
    </h1>


    <h2>
        {{ strtoupper($reportTitle) }}
    </h2>


    @if($scope === 'employee' && $selectedEmployee)

        <p>

            Employee:
            <strong>
                {{ $selectedEmployee->name }}
            </strong>

        </p>

    @endif


    <p>

        Reporting Period:
        <strong>
            {{ $periodLabel }}
        </strong>

    </p>


    <p>

        Generated:
        {{ now()->format('F d, Y h:i A') }}

    </p>

</div>


<!-- SALES SUMMARY -->
<h3 class="section-title">

    Sales Summary

</h3>


<table class="summary">

    <tr>

        <th>
            Total Sales
        </th>

        <td>
            ₱{{ number_format($totalSales, 2) }}
        </td>


        <th>
            Transactions
        </th>

        <td>
            {{ number_format($totalTransactions) }}
        </td>

    </tr>


    <tr>

        <th>
            Cash Sales
        </th>

        <td>
            ₱{{ number_format($cashSales, 2) }}
        </td>


        <th>
            GCash Sales
        </th>

        <td>
            ₱{{ number_format($gcashSales, 2) }}
        </td>

    </tr>

</table>


@if($scope === 'business')

<!-- STOCK SUMMARY -->
<h3 class="section-title">

    Stock-In Summary

</h3>


<table class="summary">

    <tr>

        <th>
            Stock-In Batches
        </th>

        <td>
            {{ number_format($stockInBatches) }}
        </td>


        <th>
            Units Received
        </th>

        <td>
            {{ number_format($unitsReceived, 0) }}
        </td>

    </tr>


    <tr>

        <th>
            Stock-In Cost
        </th>

        <td>
            ₱{{ number_format($stockInCost, 2) }}
        </td>


        <th>
            Current Products
        </th>

        <td>
            {{ number_format($totalProducts) }}
        </td>

    </tr>

</table>


<!-- INVENTORY SUMMARY -->
<h3 class="section-title">

    Current Inventory Summary

</h3>


<table class="summary">

    <tr>

        <th>
            Current Units
        </th>

        <td>
            {{ number_format($totalStockUnits, 0) }}
        </td>


        <th>
            Low Stock Products
        </th>

        <td>
            {{ $lowStockCount }}
        </td>

    </tr>


    <tr>

        <th>
            Inventory Cost Value
        </th>

        <td>
            ₱{{ number_format($inventoryCostValue, 2) }}
        </td>


        <th>
            Inventory Retail Value
        </th>

        <td>
            ₱{{ number_format($inventoryRetailValue, 2) }}
        </td>

    </tr>


    <tr>

        <th>
            Out of Stock Products
        </th>

        <td colspan="3">
            {{ $outOfStockCount }}
        </td>

    </tr>

</table>

@endif


<!-- SALES TRANSACTIONS -->
<h3 class="section-title">

    Sales Transactions

</h3>


<table class="report-table">

    <thead>

        <tr>

            <th>
                Transaction
            </th>

            <th>
                Date
            </th>

            <th>
                Employee
            </th>

            <th>
                Payment
            </th>

            <th>
                Items
            </th>

            <th class="right">
                Total
            </th>

        </tr>

    </thead>


    <tbody>

        @forelse($sales as $sale)

        <tr>

            <td>

                INV-{{ $sale->ID }}

            </td>


            <td>

                {{ $sale->Date
                    ? $sale->Date->format('M d, Y h:i A')
                    : '-'
                }}

            </td>


            <td>

                {{ $sale->user->name ?? 'Unknown' }}

            </td>


            <td>

                {{ $sale->paymentMethod->Name ?? '-' }}

            </td>


            <td class="center">

                {{ number_format(
                    $sale->soldItems->sum('Quantity'),
                    0
                ) }}

            </td>


            <td class="right">

                ₱{{ number_format(
                    $sale->Total,
                    2
                ) }}

            </td>

        </tr>

        @empty

        <tr>

            <td
                colspan="6"
                class="center"
            >

                No transactions found.

            </td>

        </tr>

        @endforelse

    </tbody>

</table>


@if($scope === 'business')

<!-- STOCK-IN -->
<h3 class="section-title">

    Stock-In Records

</h3>


<table class="report-table">

    <thead>

        <tr>

            <th>
                Batch
            </th>

            <th>
                Date
            </th>

            <th>
                Product
            </th>

            <th>
                Received By
            </th>

            <th>
                Qty
            </th>

            <th>
                Cost
            </th>

            <th>
                Retail
            </th>

            <th>
                Condition
            </th>

            <th>
                Expiration
            </th>

        </tr>

    </thead>


    <tbody>

        @forelse($stockIns as $stock)

        <tr>

            <td>

                SI-{{ $stock->ID }}

            </td>


            <td>

                {{ $stock->created_at
                    ? $stock->created_at->format('M d, Y')
                    : '-'
                }}

            </td>


            <td>

                {{ $stock->product->Name ?? '-' }}

            </td>


            <td>

                {{ $stock->user->name ?? '-' }}

            </td>


            <td class="right">

                {{ number_format(
                    $stock->Quantity,
                    0
                ) }}

            </td>


            <td class="right">

                ₱{{ number_format(
                    $stock->Cost_Price,
                    2
                ) }}

            </td>


            <td class="right">

                ₱{{ number_format(
                    $stock->Retail_Price,
                    2
                ) }}

            </td>


            <td>

                {{ $stock->Condition ?? 'Good' }}

            </td>


            <td>

                @if(
                    $stock->Has_Expiration &&
                    $stock->Expiration_Date
                )

                    {{ \Illuminate\Support\Carbon::parse(
                        $stock->Expiration_Date
                    )->format('M d, Y') }}

                @else

                    N/A

                @endif

            </td>

        </tr>

        @empty

        <tr>

            <td
                colspan="9"
                class="center"
            >

                No stock-in records found.

            </td>

        </tr>

        @endforelse

    </tbody>

</table>


<!-- CURRENT INVENTORY -->
<h3 class="section-title">

    Current Inventory

</h3>


<table class="report-table">

    <thead>

        <tr>

            <th>
                Product
            </th>

            <th>
                Category
            </th>

            <th>
                Description
            </th>

            <th>
                Current Stock
            </th>

            <th>
                Cost
            </th>

            <th>
                Retail
            </th>

        </tr>

    </thead>


    <tbody>

        @forelse($products as $product)

        <tr>

            <td>

                {{ $product->Name }}

            </td>


            <td>

                {{ $product->category->Name ?? '-' }}

            </td>


            <td>

                {{ $product->Description ?? '-' }}

            </td>


            <td class="right">

                {{ number_format(
                    $product->stock_quantity,
                    0
                ) }}

            </td>


            <td class="right">

                ₱{{ number_format(
                    $product->cost_price,
                    2
                ) }}

            </td>


            <td class="right">

                ₱{{ number_format(
                    $product->retail_price,
                    2
                ) }}

            </td>

        </tr>

        @empty

        <tr>

            <td
                colspan="6"
                class="center"
            >

                No products found.

            </td>

        </tr>

        @endforelse

    </tbody>

</table>

@endif


<div class="footer">

    <div>

        Generated by:

        <strong>
            {{ auth()->user()->name ?? 'Administrator' }}
        </strong>

    </div>


    <div class="signature">

        Owner / Administrator

    </div>

</div>


</body>

</html>
