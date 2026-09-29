<?php

/**
 * IT12 - Combine Account + Password sidebar links into one Settings link.
 *
 * This patch only edits resources/views/layouts/app.blade.php.
 * It does NOT change password reset, recovery codes, security routes,
 * controllers, models, migrations, or access-control logic.
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

$combinedNeedle = "<span class=\"font-display\">Settings</span>";
if (str_contains($content, $combinedNeedle)
    && !str_contains($content, "<span class=\"font-display\">Password</span>")) {
    echo "Settings sidebar is already combined. No changes were needed.\n";
    exit(0);
}

$settingsHeader = '        <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pt-4 pb-1">Settings</div>';
$settingsStart = strpos($content, $settingsHeader);

if ($settingsStart === false) {
    fwrite(STDERR, "ERROR: The Settings section header was not found in the navigation drawer.\n");
    fwrite(STDERR, "No files were changed.\n");
    exit(1);
}

// The first Admin-only block after the Settings section starts "Admin Controls".
$adminBlockMarker = '        @if(auth()->user() && auth()->user()->isAdmin())';
$adminStart = strpos($content, $adminBlockMarker, $settingsStart);

if ($adminStart === false) {
    fwrite(STDERR, "ERROR: The Admin Controls block was not found after the Settings section.\n");
    fwrite(STDERR, "No files were changed.\n");
    exit(1);
}

$section = substr($content, $settingsStart, $adminStart - $settingsStart);

if (!str_contains($section, "route('settings.account')") ||
    !str_contains($section, "route('settings.password')")) {
    fwrite(STDERR, "ERROR: Expected Account and Password sidebar links were not found together.\n");
    fwrite(STDERR, "No files were changed.\n");
    exit(1);
}

$replacement = <<<'BLADE'
        <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pt-4 pb-1">Settings</div>

        <a href="{{ route('settings.account') }}"
           class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition-all {{ (request()->routeIs('settings.*') || request()->routeIs('security.*')) ? 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-dark-800' }}">
            <i class="fas fa-gear w-6 text-center text-lg {{ (request()->routeIs('settings.*') || request()->routeIs('security.*')) ? 'text-red-500' : 'text-slate-400' }}"></i>
            <span class="font-display">Settings</span>
        </a>

BLADE;

$backupPath = $layoutPath . '.before-combined-settings-menu';
if (!is_file($backupPath)) {
    if (!copy($layoutPath, $backupPath)) {
        fwrite(STDERR, "ERROR: Could not create the backup file. No changes were made.\n");
        exit(1);
    }
}

$newContent = substr($content, 0, $settingsStart)
    . $replacement
    . substr($content, $adminStart);

if (file_put_contents($layoutPath, $newContent) === false) {
    fwrite(STDERR, "ERROR: Unable to write the updated layout.\n");
    exit(1);
}

echo "DONE: Account + Password sidebar entries were combined into one Settings entry.\n";
echo "Backup: resources/views/layouts/app.blade.php.before-combined-settings-menu\n";
echo "Next: run php artisan optimize:clear\n";
