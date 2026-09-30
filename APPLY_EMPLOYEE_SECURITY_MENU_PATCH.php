<?php

/**
 * IT12 - Remove the old standalone Employee Management drawer link.
 *
 * Employee management now lives in Settings > Security & Access.
 * This script edits only resources/views/layouts/app.blade.php.
 */

$projectRoot = __DIR__;
$layoutPath = $projectRoot . '/resources/views/layouts/app.blade.php';

if (!is_file($layoutPath)) {
    fwrite(STDERR, "ERROR: Could not find resources/views/layouts/app.blade.php\n");
    fwrite(STDERR, "Place this file in the Laravel project root, then run it again.\n");
    exit(1);
}

$content = file_get_contents($layoutPath);
if ($content === false) {
    fwrite(STDERR, "ERROR: Unable to read {$layoutPath}\n");
    exit(1);
}

if (!str_contains($content, "route('employees.index')")) {
    echo "Standalone Employee Management menu entry is already removed. No layout changes were needed.\n";
    exit(0);
}

$pattern = '~\n\s*<a\s+href="\{\{\s*route\(\'employees\.index\'\)\s*\}\}".*?</a>\s*~s';

$newContent = preg_replace($pattern, "\n", $content, 1, $count);

if ($newContent === null || $count !== 1) {
    fwrite(STDERR, "ERROR: Could not safely identify the standalone Employee Management link.\n");
    fwrite(STDERR, "No layout changes were made.\n");
    exit(1);
}

$backupPath = $layoutPath . '.before-employee-management-move';
if (!is_file($backupPath)) {
    if (!copy($layoutPath, $backupPath)) {
        fwrite(STDERR, "ERROR: Could not create the layout backup. No changes were made.\n");
        exit(1);
    }
}

if (file_put_contents($layoutPath, $newContent) === false) {
    fwrite(STDERR, "ERROR: Unable to write the updated layout.\n");
    exit(1);
}

echo "DONE: Standalone Employee Management was removed from the navigation drawer.\n";
echo "Employee management is now available in Settings > Security & Access.\n";
echo "Backup: resources/views/layouts/app.blade.php.before-employee-management-move\n";
echo "Next: run php artisan optimize:clear\n";
