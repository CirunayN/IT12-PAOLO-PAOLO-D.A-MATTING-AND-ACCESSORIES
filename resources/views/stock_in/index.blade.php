@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-boxes-stacked text-red-500"></i>
                Inventory
            </h1>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('stock-in.print', request()->except(['page', 'output'])) }}" onclick="event.preventDefault(); openReportOutput(this.href)"
                class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-print text-blue-500"></i>
                <span>Print / Download Receiving</span>
            </a>

            <a href="{{ route('stock-in.create') }}"
                class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2 transition-all">
                <i class="fas fa-truck-ramp-box"></i>
                <span>Receive New Shipment</span>
            </a>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <a href="{{ route('products.index', ['tab' => 'active']) }}"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <i class="fas fa-box-check"></i>
            <span>Active Catalog</span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'archived']) }}"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <i class="fas fa-box-archive"></i>
            <span>Archived / Disabled</span>
        </a>

        <a href="{{ route('stock-in.index') }}"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-red-600 text-white shadow-sm">
            <i class="fas fa-truck-ramp-box"></i>
            <span>Stock-In Receiving Logs</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-4 border shadow-sm">
        <form data-auto-filter method="GET" action="{{ route('stock-in.index') }}"
            class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">

            <div class="sm:col-span-5">
                <select name="product_id"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Products</option>
                    @foreach($products as $prod)
                    <option value="{{ $prod->ID }}"
                        {{ request('product_id') == $prod->ID ? 'selected' : '' }}>
                        {{ $prod->Name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-4">
                <select name="user_id"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Receiving Staff</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->role ?? 'Staff' }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3 flex items-center gap-2">
                <noscript><button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-dark-700 dark:hover:bg-dark-600 text-white font-bold text-sm transition-colors">
                    Filter
                </button></noscript>

                <a href="{{ route('stock-in.index') }}"
                    class="px-4 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-sm transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[1250px]">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4">Batch ID</th>
                        <th class="p-4">Date Received</th>
                        <th class="p-4">Product</th>
                        <th class="p-4">Processed By</th>
                        <th class="p-4 text-right">Received</th>
                        <th class="p-4 text-right">Remaining</th>
                        <th class="p-4 text-right">Cost</th>
                        <th class="p-4 text-right">Retail</th>
                        <th class="p-4">Expiration</th>
                        <th class="p-4">Condition</th>
                        <th class="p-4 text-right">Batch Cost</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($stockIns as $si)
                    @php
                        $isExpired =
                            $si->Has_Expiration &&
                            $si->Expiration_Date &&
                            $si->Expiration_Date->lt(today());

                        $remaining = (float) ($si->Remaining_Quantity ?? 0);
                    @endphp

                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors {{ $isExpired ? 'bg-rose-500/5' : '' }}">
                        <td class="p-4">
                            <div class="font-mono font-bold text-slate-800 dark:text-slate-200">
                                #SI-{{ $si->ID }}
                            </div>

                            @if($remaining <= 0)
                            <span class="inline-block mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-dark-800 text-slate-500">
                                Depleted
                            </span>
                            @endif
                        </td>

                        <td class="p-4 text-slate-600 dark:text-slate-300">
                            {{ $si->created_at ? $si->created_at->format('M d, Y h:i A') : '-' }}
                        </td>

                        <td class="p-4">
                            <div class="font-bold text-slate-900 dark:text-white">
                                {{ $si->product->Name ?? 'N/A' }}
                            </div>

                            @if($si->product?->category)
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $si->product->category->Name }}
                            </div>
                            @endif
                        </td>

                        <td class="p-4">
                            @if($si->user)
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg {{ $si->user->isAdmin() ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'bg-slate-200 dark:bg-dark-700 text-slate-600 dark:text-slate-300' }} flex items-center justify-center text-xs font-black">
                                    {{ strtoupper(substr($si->user->name, 0, 1)) }}
                                </div>

                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">
                                        {{ $si->user->name }}
                                    </div>

                                    <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded {{ $si->user->isAdmin() ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20' : 'bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300' }}">
                                        {{ $si->user->role ?? 'Staff' }}
                                    </span>
                                </div>
                            </div>
                            @else
                            <span class="text-xs text-slate-400">System Admin</span>
                            @endif
                        </td>

                        <td class="p-4 text-right font-black text-emerald-600 dark:text-emerald-400 text-base">
                            +{{ number_format($si->Quantity, 0) }}
                        </td>

                        <td class="p-4 text-right">
                            <span class="font-black {{ $remaining > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                {{ number_format($remaining, 0) }}
                            </span>
                        </td>

                        <td class="p-4 text-right text-slate-500 dark:text-slate-400">
                            ₱{{ number_format($si->Cost_Price, 2) }}
                        </td>

                        <td class="p-4 text-right font-bold text-slate-900 dark:text-white">
                            ₱{{ number_format($si->Retail_Price, 2) }}
                        </td>

                        <td class="p-4">
                            @if($si->Has_Expiration && $si->Expiration_Date)
                                @if($isExpired)
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-500 bg-rose-500/10 px-2 py-1 rounded-lg border border-rose-500/20">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    Expired {{ $si->Expiration_Date->format('M d, Y') }}
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-1 rounded-lg border border-amber-500/20">
                                    <i class="fas fa-calendar"></i>
                                    {{ $si->Expiration_Date->format('M d, Y') }}
                                </span>
                                @endif
                            @else
                            <span class="text-xs text-slate-400">No expiration</span>
                            @endif
                        </td>

                        <td class="p-4">
                            @php
                                $condition = $si->Condition ?? 'Good';
                            @endphp

                            <span class="inline-flex items-center text-xs font-bold px-2 py-1 rounded-lg
                                {{ $condition === 'Good'
                                    ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
                                    : 'bg-rose-500/10 text-rose-500 border border-rose-500/20' }}">
                                {{ $condition }}
                            </span>
                        </td>

                        <td class="p-4 text-right font-black font-display text-slate-900 dark:text-white">
                            ₱{{ number_format($si->Quantity * $si->Cost_Price, 2) }}
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center text-slate-400">
                            No stock-in records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stockIns->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
