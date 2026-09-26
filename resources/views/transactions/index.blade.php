@extends('layouts.app')

@section('content')

<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">

        <div>

            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">

                <i class="fas fa-receipt text-red-500"></i>

                My Transactions

            </h1>


            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">

                Transaction history processed by
                <strong>
                    {{ auth()->user()->name }}
                </strong>.

            </p>

        </div>


        <a
            href="{{ route('transactions.print', [
                'period' => $period,
                'month' => $month,
                'year' => $year
            ]) }}"
            target="_blank"
            class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center justify-center gap-2"
        >

            <i class="fas fa-print"></i>

            <span>
                Print My Report
            </span>

        </a>

    </div>


    <!-- FILTER -->
    <form
        method="GET"
        action="{{ route('transactions.index') }}"
        class="glass-card rounded-2xl p-5 border"
    >

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

            <!-- PERIOD -->
            <div>

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Report Period

                </label>


                <select
                    name="period"
                    id="personalReportPeriod"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white"
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
            <div id="personalMonthContainer">

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Month

                </label>


                <input
                    type="month"
                    name="month"
                    value="{{ $month }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white"
                >

            </div>


            <!-- YEAR -->
            <div id="personalYearContainer">

                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">

                    Year

                </label>


                <input
                    type="number"
                    name="year"
                    value="{{ $year }}"
                    min="2000"
                    max="2100"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white"
                >

            </div>


            <!-- GENERATE -->
            <div class="flex items-end">

                <button
                    type="submit"
                    class="w-full px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm"
                >

                    <i class="fas fa-filter mr-1"></i>

                    Generate

                </button>

            </div>

        </div>

    </form>


    <!-- REPORT PERIOD -->
    <div class="glass-card rounded-2xl p-4 border">

        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">

            Current Report:

        </span>


        <strong class="ml-2 text-red-500">

            {{ $periodLabel }}

        </strong>

    </div>


    <!-- SUMMARY -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">

                Transactions

            </div>


            <div class="text-2xl font-black mt-2">

                {{ number_format($totalTransactions) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">

                Cash Sales

            </div>


            <div class="text-2xl font-black mt-2">

                ₱{{ number_format($cashSales, 2) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">

                GCash Sales

            </div>


            <div class="text-2xl font-black mt-2">

                ₱{{ number_format($gcashSales, 2) }}

            </div>

        </div>


        <div class="glass-card rounded-2xl p-5 border">

            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">

                Total Sales

            </div>


            <div class="text-2xl font-black text-emerald-500 mt-2">

                ₱{{ number_format($totalSales, 2) }}

            </div>

        </div>

    </div>


    <!-- TRANSACTION TABLE -->
    <div class="glass-card rounded-2xl border overflow-hidden">

        <div class="p-5 border-b border-slate-200 dark:border-slate-800">

            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white">

                Transaction History

            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>

                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">

                        <th class="px-4 py-3 text-left">
                            Transaction
                        </th>

                        <th class="px-4 py-3 text-left">
                            Date
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

                    <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-dark-800/40">

                        <td class="px-4 py-3 font-bold text-red-500">

                            INV-{{ $sale->ID }}

                        </td>


                        <td class="px-4 py-3 whitespace-nowrap">

                            {{ $sale->Date
                                ? $sale->Date->format('M d, Y h:i A')
                                : '-'
                            }}

                        </td>


                        <td class="px-4 py-3 text-center font-bold">

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
                            colspan="5"
                            class="px-4 py-12 text-center text-slate-400"
                        >

                            No transactions found for this period.

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

</div>


<script>

const personalReportPeriod =
    document.getElementById(
        'personalReportPeriod'
    );


const personalMonthContainer =
    document.getElementById(
        'personalMonthContainer'
    );


const personalYearContainer =
    document.getElementById(
        'personalYearContainer'
    );


function updatePersonalReportFields() {

    const period =
        personalReportPeriod.value;


    if (period === 'monthly') {

        personalMonthContainer
            .classList
            .remove('hidden');

        personalYearContainer
            .classList
            .add('hidden');

    } else if (
        period === 'yearly'
    ) {

        personalMonthContainer
            .classList
            .add('hidden');

        personalYearContainer
            .classList
            .remove('hidden');

    } else {

        personalMonthContainer
            .classList
            .add('hidden');

        personalYearContainer
            .classList
            .add('hidden');

    }

}


personalReportPeriod.addEventListener(
    'change',
    updatePersonalReportFields
);


updatePersonalReportFields();

</script>

@endsection
