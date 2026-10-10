<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
    @foreach([
        ['id' => 'bestSellingProducts', 'title' => 'Top 5 Best-Selling Products', 'icon' => 'fa-arrow-trend-up', 'products' => $bestSellingProducts, 'empty' => 'No products sold in the selected dates.'],
        ['id' => 'leastSellingProducts', 'title' => 'Top 5 Least-Selling Products', 'icon' => 'fa-arrow-trend-down', 'products' => $leastSellingProducts, 'empty' => 'No active products available.'],
    ] as $ranking)
        <section id="{{ $ranking['id'] }}" aria-labelledby="{{ $ranking['id'] }}Title" class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm min-w-0">
            <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                    <i class="fas {{ $ranking['icon'] }} text-xs" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <h2 id="{{ $ranking['id'] }}Title" class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">{{ $ranking['title'] }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $periodLabel }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th scope="col" class="py-2.5 pr-3 font-bold">Product</th>
                            <th scope="col" class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Units Sold</th>
                            <th scope="col" class="pl-3 py-2.5 font-bold text-right whitespace-nowrap">Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($ranking['products'] as $product)
                            <tr>
                                <td class="py-3 pr-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300 font-bold text-xs flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white break-words">{{ $product->Name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $product->category?->Name ?? 'Uncategorized' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-slate-900 dark:text-white whitespace-nowrap">{{ number_format($product->dashboard_units_sold, fmod($product->dashboard_units_sold, 1) == 0 ? 0 : 2) }}</td>
                                <td class="pl-3 py-3 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">₱{{ number_format($product->dashboard_sales_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400 text-xs">{{ $ranking['empty'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>
