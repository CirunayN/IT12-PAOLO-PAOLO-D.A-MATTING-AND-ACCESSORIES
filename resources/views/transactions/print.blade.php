<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Transaction Report - {{ $user->name }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            font-size: 12px;
            background: #fff;
        }


        .report-page {
            width: 100%;
            margin: 0 auto;
        }


        .header {
            text-align: center;
            margin-bottom: 25px;
        }


        .header h1 {
            font-size: 20px;
            margin: 0;
        }


        .header h2 {
            font-size: 15px;
            margin: 5px 0;
        }


        .header p {
            margin: 3px;
        }


        .information {
            margin-bottom: 20px;
        }


        .information p {
            margin: 4px 0;
        }


        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
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
            page-break-inside: auto;
        }


        .report-table thead {
            display: table-header-group;
        }


        .report-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }


        .report-table th,
        .report-table td {
            border: 1px solid #aaa;
            padding: 7px;
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


        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }


        .signature {
            width: 220px;
            padding-top: 5px;
            border-top: 1px solid #111;
            text-align: center;
        }


        @media print {

            .no-print {
                display: none !important;
            }


            html,
            body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
            }


            .report-page {
                width: 100%;
                margin: 0;
            }


            .summary,
            .report-table {
                width: 100%;
            }


            .header,
            .information,
            .summary {
                page-break-inside: avoid;
            }


            .signature-area {
                page-break-inside: avoid;
            }


            @page {
                size: A4 portrait;
                margin: 15mm;
            }

        }

    </style>

@if($downloadPdf ?? false)<style>@page {margin:0;} body {width:auto!important;padding:15mm!important;} body,table {font-family:'DejaVu Sans',sans-serif;}.report-page{width:100%;padding:0;} .information,.signature-area{display:block;}</style>@endif
</head>


<body>

<div class="report-page">


@include('shared.print-actions')



<div class="header">

    <h1>
        PAOLO PAOLO D.A. MATTING &amp; ACCESSORIES
    </h1>


    <h2>
        EMPLOYEE TRANSACTION REPORT
    </h2>


    <p>
        Reporting Period:
        <strong>
            {{ $periodLabel }}
        </strong>
    </p>

</div>


<div class="information">

    <p>
        <strong>
            Employee:
        </strong>

        {{ $user->name }}
    </p>


    <p>
        <strong>
            Role:
        </strong>

        {{ $user->role ?? 'Employee' }}
    </p>


    <p>
        <strong>
            Generated:
        </strong>

        {{ now()->timezone('Asia/Manila')->format('F d, Y h:i A') }}
    </p>

</div>


<table class="summary">

    <tr>

        <th>
            Total Transactions
        </th>

        <td>
            {{ $totalTransactions }}
        </td>


        <th>
            Total Sales
        </th>

        <td>
            ₱{{ number_format($totalSales, 2) }}
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
                colspan="5"
                class="center"
            >

                No transactions found.

            </td>

        </tr>

        @endforelse

    </tbody>

</table>


<div class="signature-area">

    <div class="signature">

        {{ $user->name }}

        <br>

        Employee

    </div>


    <div class="signature">

        Owner / Administrator

    </div>

</div>



</div>



</body>

</html>
