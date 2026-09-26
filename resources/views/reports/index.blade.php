@extends('layouts.app')

@section('content')

<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">

        <div>

            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">

                <i class="fas fa-file-lines text-red-500"></i>

                Admin Reports

            </h1>


            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">

                Business and employee transaction reports.

            </p>

        </div>


        <a
            href="{{ route('reports.print', [
                'scope' => $scope,
                'employee_id' => $selectedEmployee?->id,
                'period' => $period,
                'month' => $month,
                'year' => $year
            ]) }}"
            target="_blank"
            class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center justify-center gap-2"
        >

            <i class="fas fa-print"></i>

            <span>
                Print Report
            </span>

        </a>

    </div>


    <!-- FILTER -->
    <form
        method="GET"
        action="{{ route('reports.index') }}"
        class="glass-card rounded-2xl p-5 border"
    >

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">


            <!-- REPORT SCOPE -->
            <div>

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Report Type

                </label>


                <select
                    name="scope"
                    id="adminReportScope"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                >

                    <option
                        value="business"
                        {{ $scope === 'business' ? 'selected' : '' }}
                    >
                        Business Report
                    </option>


                    <option
                        value="employee"
                        {{ $scope === 'employee' ? 'selected' : '' }}
                    >
                        Employee Report
                    </option>

                </select>

            </div>


            <!-- EMPLOYEE -->
            <div id="adminEmployeeContainer">

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Employee

                </label>


                <select
                    name="employee_id"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                >

                    <option value="">
                        Select Employee
                    </option>


                    @foreach($employees as $employee)

                    <option
                        value="{{ $employee->id }}"
                        {{ ($selectedEmployee && $selectedEmployee->id == $employee->id) ? 'selected' : '' }}
                    >

                        {{ $employee->name }}
                        ({{ $employee->role ?? 'Employee' }})

                    </option>

                    @endforeach

                </select>

            </div>


            <!-- PERIOD -->
            <div>

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Period

                </label>


                <select
                    name="period"
                    id="adminReportPeriod"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                >

                    <option
                        value="monthly"
                        {{ $period === 'monthly' ? 'selected' : '' }}
                    >
                        Monthly
                    </option>


                    <option
                        value="yearly"
                        {{ $period === 'yearly' ? 'selected' : '' }}
                    >
                        Yearly
                    </option>


                    <option
                        value="overall"
                        {{ $period === 'overall' ? 'selected' : '' }}
                    >
                        Overall
                    </option>

                </select>

            </div>


            <!-- MONTH -->
            <div id="adminMonthContainer">

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Month

                </label>


                <input
                    type="month"
                    name="month"
                    value="{{ $month }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                >

            </div>


            <!-- YEAR -->
            <div id="adminYearContainer">

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Year

                </label>


                <input
                    type="number"
                    name="year"
                    value="{{ $year }}"
                    min="2000"
                    max="2100"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                >

            </div>

        </div>


        <div class="flex justify-end mt-4">

            <button
                type="submit"
                class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm"
            >

                <i class="fas fa-filter mr-1"></i>

                Generate Report

            </button>

        </div>

    </form>


    <!-- CURRENT REPORT -->
    <div class="glass-card rounded-2xl p-4 border">

        <div class="flex flex-wrap gap-x-6 gap-y-2">

            <div>

                <span class="text-xs uppercase font-bold text-slate-400">

                    Report:

                </span>

                <strong class="ml-1 text-red-500">

                    {{ $reportTitle }}

                </strong>

            </div>


            <div>

                <span class="text-xs uppercase font-bold text-slate-400">

                    Period:

                </span>

                <strong class="ml-1">

                    {{ $periodLabel }}

                </strong>

            </div>

        </div>

    </div>


    <!-- SALES SUMMARY -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">
                Total Sales
            </div>

            <div class="text-2xl font-black text-emerald-500 mt-2">

                ₱{{ number_format($totalSales, 2) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">
                Transactions
            </div>

            <div class="text-2xl font-black mt-2">

                {{ number_format($totalTransactions) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">
                Cash Sales
            </div>

            <div class="text-2xl font-black mt-2">

                ₱{{ number_format($cashSales, 2) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">
                GCash Sales
            </div>

            <div class="text-2xl font-black mt-2">

                ₱{{ number_format($gcashSales, 2) }}

            </div>

        </div>

    </div>


    @if($scope === 'business')

    <!-- STOCK SUMMARY -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Stock-In Batches

            </div>

            <div class="text-2xl font-black mt-2">

                {{ number_format($stockInBatches) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Units Received

            </div>

            <div class="text-2xl font-black mt-2">

                {{ number_format($unitsReceived, 0) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Stock-In Cost

            </div>

            <div class="text-2xl font-black mt-2">

                ₱{{ number_format($stockInCost, 2) }}

            </div>

        </div>

    </div>


    <!-- INVENTORY SUMMARY -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Products

            </div>

            <div class="text-2xl font-black mt-2">

                {{ number_format($totalProducts) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Current Units

            </div>

            <div class="text-2xl font-black mt-2">

                {{ number_format($totalStockUnits, 0) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Low Stock

            </div>

            <div class="text-2xl font-black text-amber-500 mt-2">

                {{ $lowStockCount }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs uppercase font-bold text-slate-400">

                Out of Stock

            </div>

            <div class="text-2xl font-black text-rose-500 mt-2">

                {{ $outOfStockCount }}

            </div>

        </div>

    </div>

    @endif


    <!-- SALES -->
    <div class="glass-card rounded-2xl border overflow-hidden">

        <div class="p-5 border-b border-slate-200 dark:border-slate-800">

            <h3 class="font-display font-black text-lg">

                Sales Transactions

            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>

                    <tr class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">

                        <th class="px-4 py-3 text-left">
                            Transaction
                        </th>

                        <th class="px-4 py-3 text-left">
                            Date
                        </th>

                        <th class="px-4 py-3 text-left">
                            Employee
                        </th>

                        <th class="px-4 py-3 text-center">
                            Items
                        </th>

                        <th class="px-4 py-3 text-left">
                            Payment
                        </th>

                        <th class="px-4 py-3 text-right">
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($sales as $sale)

                    <tr class="border-b border-slate-100 dark:border-slate-800">

                        <td class="px-4 py-3 font-bold text-red-500">

                            INV-{{ $sale->ID }}

                        </td>


                        <td class="px-4 py-3">

                            {{ $sale->Date
                                ? $sale->Date->format('M d, Y h:i A')
                                : '-'
                            }}

                        </td>


                        <td class="px-4 py-3">

                            {{ $sale->user->name ?? 'Unknown' }}

                        </td>


                        <td class="px-4 py-3 text-center">

                            {{ number_format(
                                $sale->soldItems->sum('Quantity'),
                                0
                            ) }}

                        </td>


                        <td class="px-4 py-3">

                            {{ $sale->paymentMethod->Name ?? '-' }}

                        </td>


                        <td class="px-4 py-3 text-right font-black text-emerald-500">

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
                            class="px-4 py-10 text-center text-slate-400"
                        >

                            No transactions found.

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="p-4">

            {{ $sales->links() }}

        </div>

    </div>


    @if($scope === 'business')

    <!-- STOCK-IN RECORDS -->
    <div class="glass-card rounded-2xl border overflow-hidden">

        <div class="p-5 border-b border-slate-200 dark:border-slate-800">

            <h3 class="font-display font-black text-lg">

                Stock-In Records

            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>

                    <tr class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">

                        <th class="px-4 py-3 text-left">
                            Batch
                        </th>

                        <th class="px-4 py-3 text-left">
                            Product
                        </th>

                        <th class="px-4 py-3 text-right">
                            Quantity
                        </th>

                        <th class="px-4 py-3 text-right">
                            Cost
                        </th>

                        <th class="px-4 py-3 text-right">
                            Retail
                        </th>

                        <th class="px-4 py-3 text-left">
                            Condition
                        </th>

                        <th class="px-4 py-3 text-left">
                            Expiration
                        </th>

                        <th class="px-4 py-3 text-left">
                            Received
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($stockIns as $stock)

                    <tr class="border-b border-slate-100 dark:border-slate-800">

                        <td class="px-4 py-3 font-bold">

                            SI-{{ $stock->ID }}

                        </td>


                        <td class="px-4 py-3">

                            {{ $stock->product->Name ?? '-' }}

                        </td>


                        <td class="px-4 py-3 text-right">

                            {{ number_format(
                                $stock->Quantity,
                                0
                            ) }}

                        </td>


                        <td class="px-4 py-3 text-right">

                            ₱{{ number_format(
                                $stock->Cost_Price,
                                2
                            ) }}

                        </td>


                        <td class="px-4 py-3 text-right">

                            ₱{{ number_format(
                                $stock->Retail_Price,
                                2
                            ) }}

                        </td>


                        <td class="px-4 py-3">

                            {{ $stock->Condition ?? 'Good' }}

                        </td>


                        <td class="px-4 py-3">

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


                        <td class="px-4 py-3">

                            {{ $stock->created_at
                                ? $stock->created_at->format('M d, Y')
                                : '-'
                            }}

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td
                            colspan="8"
                            class="px-4 py-10 text-center text-slate-400"
                        >

                            No stock-in records found.

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="p-4">

            {{ $stockIns->links() }}

        </div>

    </div>

    @endif

</div>


<script>

const adminReportScope =
    document.getElementById(
        'adminReportScope'
    );


const adminEmployeeContainer =
    document.getElementById(
        'adminEmployeeContainer'
    );


const adminReportPeriod =
    document.getElementById(
        'adminReportPeriod'
    );


const adminMonthContainer =
    document.getElementById(
        'adminMonthContainer'
    );


const adminYearContainer =
    document.getElementById(
        'adminYearContainer'
    );


function updateAdminScope() {

    adminEmployeeContainer
        .classList
        .toggle(
            'hidden',
            adminReportScope.value !==
            'employee'
        );

}


function updateAdminPeriod() {

    const period =
        adminReportPeriod.value;


    if (
        period ===
        'monthly'
    ) {

        adminMonthContainer
            .classList
            .remove('hidden');

        adminYearContainer
            .classList
            .add('hidden');

    } else if (
        period ===
        'yearly'
    ) {

        adminMonthContainer
            .classList
            .add('hidden');

        adminYearContainer
            .classList
            .remove('hidden');

    } else {

        adminMonthContainer
            .classList
            .add('hidden');

        adminYearContainer
            .classList
            .add('hidden');

    }

}


adminReportScope.addEventListener(
    'change',
    updateAdminScope
);


adminReportPeriod.addEventListener(
    'change',
    updateAdminPeriod
);


updateAdminScope();

updateAdminPeriod();

</script>

@endsection
