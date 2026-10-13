<div>
    <label for="lowStockThreshold" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Low-Stock Threshold (pieces)</label>
    <input id="lowStockThreshold" type="number" name="Low_Stock_Threshold" min="0" max="2147483647" step="1"
        value="{{ old('Low_Stock_Threshold', $thresholdValue ?? 5) }}"
        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
        aria-describedby="lowStockThresholdHelp">
    <p id="lowStockThresholdHelp" class="mt-1 text-xs text-slate-500 dark:text-slate-400">Show a low-stock alert when this item's available stock reaches this count or lower. Zero stock is always marked out of stock.</p>
</div>
