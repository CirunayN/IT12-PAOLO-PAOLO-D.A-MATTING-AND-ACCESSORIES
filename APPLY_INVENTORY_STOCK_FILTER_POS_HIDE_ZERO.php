<?php

/**
 * IT12 patch
 * - Inventory stock-level filter: Available (6+), Low Stock (1-5), Out of Stock (0)
 * - POS hides products with zero sellable stock
 *
 * Run from the Laravel project root:
 *   php APPLY_INVENTORY_STOCK_FILTER_POS_HIDE_ZERO.php
 */

$root = __DIR__;

function patchOrFail(string $path, callable $callback): void
{
    if (!is_file($path)) {
        fwrite(STDERR, "Missing file: {$path}\n");
        exit(1);
    }

    $original = file_get_contents($path);
    $updated = $callback($original);

    if ($updated === $original) {
        echo "No changes needed: {$path}\n";
        return;
    }

    $backup = $path . '.before-stock-filter-pos-hide-zero';
    if (!is_file($backup)) {
        copy($path, $backup);
    }

    file_put_contents($path, $updated);
    echo "Patched: {$path}\n";
}

// 1) Inventory: server-side stock-level filtering using the same sellable-stock rules.
$productController = $root . '/app/Http/Controllers/ProductController.php';
patchOrFail($productController, function (string $content): string {
    if (str_contains($content, "filled('stock_level')")) {
        return $content;
    }

    $needle = <<<'PHP'
        if ($request->filled('status_id')) {
            $query->where('Status_ID', $request->status_id);
        }

        $products = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
PHP;

    $replacement = <<<'PHP'
        if ($request->filled('status_id')) {
            $query->where('Status_ID', $request->status_id);
        }

        // Filter by SELLABLE stock only: remaining > 0, Good condition,
        // and either non-expiring or not yet expired.
        if ($request->filled('stock_level')) {
            $sellableStockSql = '(
                SELECT COALESCE(SUM(si.Remaining_Quantity), 0)
                FROM tbl_stock_in si
                WHERE si.Product_ID = tbl_product.ID
                  AND si.Remaining_Quantity > 0
                  AND si.Condition = ?
                  AND (
                      si.Has_Expiration = 0
                      OR si.Expiration_Date IS NULL
                      OR DATE(si.Expiration_Date) >= ?
                  )
            )';

            $stockBindings = ['Good', today()->toDateString()];

            switch ($request->stock_level) {
                case 'out':
                    $query->whereRaw($sellableStockSql . ' <= 0', $stockBindings);
                    break;

                case 'low':
                    $query->whereRaw(
                        $sellableStockSql . ' > 0 AND ' . $sellableStockSql . ' <= 5',
                        array_merge($stockBindings, $stockBindings)
                    );
                    break;

                case 'available':
                    $query->whereRaw($sellableStockSql . ' > 5', $stockBindings);
                    break;
            }
        }

        $products = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
PHP;

    if (!str_contains($content, $needle)) {
        fwrite(STDERR, "Could not find the expected ProductController filter block. Patch stopped before modifying this file.\n");
        exit(1);
    }

    return str_replace($needle, $replacement, $content);
});

// 2) Inventory UI: add stock-level dropdown and rebalance the filter grid.
$productView = $root . '/resources/views/products/index.blade.php';
patchOrFail($productView, function (string $content): string {
    if (str_contains($content, 'name="stock_level"')) {
        return $content;
    }

    $content = preg_replace(
        '/<div class="sm:col-span-6 relative">/',
        '<div class="sm:col-span-4 relative">',
        $content,
        1,
        $countSearch
    );

    $content = preg_replace(
        '/<div class="sm:col-span-4">\s*\n\s*<select name="category_id"/',
        '<div class="sm:col-span-3">' . "\n" . '                <select name="category_id"',
        $content,
        1,
        $countCategory
    );

    $buttonNeedle = '            <div class="sm:col-span-2 flex items-center gap-2">';
    $stockBlock = <<<'BLADE'
            <div class="sm:col-span-3">
                <select name="stock_level" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Stock Levels</option>
                    <option value="available" {{ request('stock_level') === 'available' ? 'selected' : '' }}>Available (6+ units)</option>
                    <option value="low" {{ request('stock_level') === 'low' ? 'selected' : '' }}>Low Stock (1-5 units)</option>
                    <option value="out" {{ request('stock_level') === 'out' ? 'selected' : '' }}>Out of Stock (0)</option>
                </select>
            </div>
BLADE;

    if (($countSearch ?? 0) !== 1 || ($countCategory ?? 0) !== 1 || !str_contains($content, $buttonNeedle)) {
        fwrite(STDERR, "Could not find the expected Inventory filter form. Patch stopped before saving this view.\n");
        exit(1);
    }

    return str_replace($buttonNeedle, $stockBlock . "\n" . $buttonNeedle, $content);
});

// 3) POS: exclude products that have no sellable stock before rendering the grid.
$posController = $root . '/app/Http/Controllers/PosController.php';
patchOrFail($posController, function (string $content): string {
    if (str_contains($content, '// Hide zero-stock products from the POS catalog.')) {
        return $content;
    }

    $needle = <<<'PHP'
        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        $products = $query->get();
PHP;

    $replacement = <<<'PHP'
        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        // Hide zero-stock products from the POS catalog.
        // A product is shown only when it has at least one sellable batch:
        // remaining > 0, Good condition, and not expired.
        $query->whereHas('stockIns', function ($stockQuery) {
            $stockQuery
                ->where('Remaining_Quantity', '>', 0)
                ->where('Condition', 'Good')
                ->where(function ($expirationQuery) {
                    $expirationQuery
                        ->where('Has_Expiration', false)
                        ->orWhereNull('Expiration_Date')
                        ->orWhereDate('Expiration_Date', '>=', today()->toDateString());
                });
        });

        $products = $query->get();
PHP;

    if (!str_contains($content, $needle)) {
        fwrite(STDERR, "Could not find the expected PosController catalog block. Patch stopped before modifying this file.\n");
        exit(1);
    }

    return str_replace($needle, $replacement, $content);
});

echo "\nPatch complete.\n";
echo "Inventory filters: All / Available 6+ / Low 1-5 / Out 0.\n";
echo "POS now hides products with zero sellable stock.\n";
echo "Next run: php artisan optimize:clear\n";
