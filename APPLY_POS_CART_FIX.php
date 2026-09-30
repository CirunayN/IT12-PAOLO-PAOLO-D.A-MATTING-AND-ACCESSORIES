<?php
/**
 * IT12 POS cart click fix
 *
 * Run from the Laravel project root:
 *   php APPLY_POS_CART_FIX.php
 *
 * This patches only resources/views/pos/index.blade.php and creates a backup.
 */

$root = getcwd();
$file = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'pos' . DIRECTORY_SEPARATOR . 'index.blade.php';

if (!is_file($file)) {
    fwrite(STDERR, "ERROR: Could not find resources/views/pos/index.blade.php\nRun this script from the Laravel project root.\n");
    exit(1);
}

$contents = file_get_contents($file);
if ($contents === false) {
    fwrite(STDERR, "ERROR: Could not read POS Blade file.\n");
    exit(1);
}

$backup = $file . '.before-cart-fix.bak';
if (!is_file($backup)) {
    if (!copy($file, $backup)) {
        fwrite(STDERR, "ERROR: Could not create backup: {$backup}\n");
        exit(1);
    }
}

$original = $contents;

// 1) Replace unsafe inline product-name JS in Add button.
$oldAdd = <<<'BLADE'
onclick='addToCart({{ $p->ID }}, @js($p->Name), {{ $price }}, {{ $qty }})'
BLADE;
$newAdd = <<<'BLADE'
onclick="addProductRowToCart(this)"
BLADE;
$contents = str_replace($oldAdd, $newAdd, $contents, $addCount);

// 2) Replace unsafe inline gallery JSON/title JS with escaped data attributes.
$oldGallery = <<<'BLADE'
onclick='openPosGallery({!! $imagesJson !!}, @js($p->Name), event)'
BLADE;
$newGallery = <<<'BLADE'
data-gallery-images="{{ json_encode($allImages) }}"
                                    data-gallery-title="{{ $p->Name }}"
                                    onclick="openPosGalleryFromButton(this, event)"
BLADE;
$contents = str_replace($oldGallery, $newGallery, $contents, $galleryCount);

// 3) Add safe bridge helpers once, before addToCart().
$helperMarker = "function addToCart(id, name, price, maxStock) {";
$helperCode = <<<'JS'
function addProductRowToCart(button) {
    const row = button.closest('.product-item');
    if (!row) return;

    addToCart(
        row.dataset.id,
        row.dataset.name || 'Product',
        row.dataset.price,
        row.dataset.stock
    );
}

function openPosGalleryFromButton(button, event) {
    if (event) event.stopPropagation();

    let images = [];
    try {
        images = JSON.parse(button.dataset.galleryImages || '[]');
    } catch (error) {
        images = [];
    }

    openPosGallery(
        images,
        button.dataset.galleryTitle || 'Product Photos',
        event
    );
}

JS;

if (strpos($contents, 'function addProductRowToCart(button)') === false) {
    $pos = strpos($contents, $helperMarker);
    if ($pos === false) {
        fwrite(STDERR, "ERROR: Could not locate addToCart() in the current POS file. No changes written.\n");
        exit(1);
    }
    $contents = substr($contents, 0, $pos) . $helperCode . substr($contents, $pos);
}

if ($addCount < 1) {
    fwrite(STDERR, "WARNING: Add-button pattern was not found. The file may already be patched or differ from the expected GitHub version.\n");
}
if ($galleryCount < 1) {
    fwrite(STDERR, "WARNING: Gallery pattern was not found. The file may already be patched or differ from the expected GitHub version.\n");
}

if ($contents === $original) {
    echo "No changes were necessary. The POS file may already contain the fix.\n";
    exit(0);
}

if (file_put_contents($file, $contents) === false) {
    fwrite(STDERR, "ERROR: Could not write the patched POS Blade file.\n");
    exit(1);
}

echo "POS cart click fix applied successfully.\n";
echo "Backup: resources/views/pos/index.blade.php.before-cart-fix.bak\n";
echo "Next run: php artisan optimize:clear\n";
