<div class="lg:col-span-7 glass-card rounded-3xl p-5 sm:p-6 border shadow-sm">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <h3 class="font-display font-bold text-lg">Low / Out of Stock</h3>
        <a href="{{ route('products.index', ['stock_level' => 'attention']) }}" class="text-xs font-bold text-blue-500">View Inventory &rarr;</a>
    </div>
    <div class="overflow-x-auto max-h-96">
        <table class="w-full text-left text-sm">
            <thead><tr class="text-xs uppercase text-slate-400"><th class="py-3">Product</th><th class="py-3">Category</th><th class="py-3 text-right">On Hand</th><th class="py-3 text-right">Stock Status</th></tr></thead>
            <tbody>
                @forelse($stockAlerts as $product)
                <tr class="border-t border-slate-200 dark:border-slate-800">
                    <td class="py-3 pr-3 font-bold"><a href="{{ route('products.edit', $product->ID) }}">{{ $product->Name }}</a></td>
                    <td class="py-3 pr-3 text-xs text-slate-500"><span data-category-label="{{ $product->Category_ID }}">{{ $product->category->Name ?? '—' }}</span></td>
                    <td class="py-3 text-right font-bold">{{ number_format($product->dashboard_stock, 0) }}</td>
                    <td class="py-3 text-right text-xs font-bold {{ $product->dashboard_stock <= 0 ? 'text-rose-500' : 'text-amber-500' }}">{{ $product->dashboard_stock <= 0 ? 'Out of Stock' : 'Low on Stock' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-8 text-center text-slate-400">All active items have sufficient stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
