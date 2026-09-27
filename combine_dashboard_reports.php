<?php

/**
 * Paolo Paolo - Combine Dashboard + Printable Reports
 *
 * Run this file from the ROOT of the Laravel project:
 *
 *     php combine_dashboard_reports.php
 *
 * This version DOES NOT modify controllers or routes.
 * It only combines Dashboard and Reports in the UI:
 *   - one sidebar module: "Dashboard & Reports"
 *   - Overview tab
 *   - Printable Reports tab
 *
 * Existing report generation and /reports/print are preserved.
 */

function fail(string $message): never
{
    fwrite(STDERR, "[ERROR] {$message}\n");
    exit(1);
}

function ok(string $message): void
{
    echo "[OK] {$message}\n";
}

function info(string $message): void
{
    echo "[INFO] {$message}\n";
}

function backupFile(string $path): void
{
    $backup = $path . '.dashboard-reports.bak';

    if (!file_exists($backup)) {
        if (!copy($path, $backup)) {
            fail("Could not create backup: {$backup}");
        }
        ok("Backup created: {$backup}");
    } else {
        info("Backup already exists: {$backup}");
    }
}

function writeFileSafe(string $path, string $contents): void
{
    $dir = dirname($path);

    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail("Could not create directory: {$dir}");
    }

    if (file_put_contents($path, $contents) === false) {
        fail("Could not write file: {$path}");
    }
}

$root = getcwd();

$dashboardPath = $root . DIRECTORY_SEPARATOR . 'resources/views/dashboard.blade.php';
$reportsPath   = $root . DIRECTORY_SEPARATOR . 'resources/views/reports/index.blade.php';
$layoutPath    = $root . DIRECTORY_SEPARATOR . 'resources/views/layouts/app.blade.php';
$tabsPath      = $root . DIRECTORY_SEPARATOR . 'resources/views/reports/tabs.blade.php';

foreach ([$dashboardPath, $reportsPath, $layoutPath] as $required) {
    if (!file_exists($required)) {
        fail(
            "Required file not found:\n{$required}\n\n" .
            "Put this installer in the ROOT of your Laravel project, beside artisan."
        );
    }
}

if (!file_exists($root . DIRECTORY_SEPARATOR . 'artisan')) {
    fail("artisan was not found. Run this script from the Laravel project root.");
}

info("Laravel project detected.");
info("Combining Dashboard and Printable Reports...");

backupFile($dashboardPath);
backupFile($reportsPath);
backupFile($layoutPath);

/*
|--------------------------------------------------------------------------
| 1. CREATE SHARED TAB COMPONENT
|--------------------------------------------------------------------------
*/

$tabs = <<<'BLADE'
<div class="mt-3 inline-flex items-center gap-1 p-1 rounded-xl bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700">
    <a
        href="{{ route('dashboard') }}"
        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all
            {{ request()->routeIs('dashboard')
                ? 'bg-white dark:bg-dark-700 text-red-600 dark:text-red-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
    >
        <i class="fas fa-chart-pie mr-1.5"></i>
        Overview
    </a>

    <a
        href="{{ route('reports.index') }}"
        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all
            {{ request()->routeIs('reports.*')
                ? 'bg-white dark:bg-dark-700 text-red-600 dark:text-red-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
    >
        <i class="fas fa-file-lines mr-1.5"></i>
        Printable Reports
    </a>
</div>
BLADE;

writeFileSafe($tabsPath, $tabs . PHP_EOL);
ok("Created shared Dashboard/Reports tabs.");

/*
|--------------------------------------------------------------------------
| 2. DASHBOARD VIEW
|--------------------------------------------------------------------------
*/

$dashboard = file_get_contents($dashboardPath);
if ($dashboard === false) {
    fail("Could not read dashboard view.");
}

/* Rename header. */
$dashboard = str_replace(
    'Executive Dashboard',
    'Dashboard &amp; Reports',
    $dashboard
);

/* Add tabs once, immediately under the dashboard description. */
if (!str_contains($dashboard, "@include('reports.tabs')")) {
    $dashboardDescriptionPattern =
        '/(<p class="text-xs text-slate-500 dark:text-slate-400 mt-0\.5">\s*' .
        'Real-time overview of daily sales, inventory levels, catalog metrics, and recent activities\.\s*' .
        '<\/p>)/s';

    $dashboardUpdated = preg_replace(
        $dashboardDescriptionPattern,
        "$1\n            @include('reports.tabs')",
        $dashboard,
        1,
        $dashboardCount
    );

    if ($dashboardUpdated === null) {
        fail("Regex error while updating dashboard.blade.php.");
    }

    if ($dashboardCount === 0) {
        /*
         * Fallback: insert before the header's closing left-side div.
         * This is intentionally conservative.
         */
        $marker = '<div class="flex items-center gap-3">';
        $pos = strpos($dashboard, $marker);

        if ($pos === false) {
            fail(
                "Could not locate the dashboard header safely.\n" .
                "Your dashboard.blade.php may be different from the GitHub version."
            );
        }

        $before = substr($dashboard, 0, $pos);
        $after  = substr($dashboard, $pos);

        $before = preg_replace(
            '/\s*<\/div>\s*$/',
            "\n            @include('reports.tabs')\n        </div>\n        ",
            $before,
            1,
            $fallbackCount
        );

        if ($fallbackCount === 0) {
            fail("Could not insert the Dashboard/Reports tabs into dashboard.blade.php.");
        }

        $dashboard = $before . $after;
    } else {
        $dashboard = $dashboardUpdated;
    }
}

writeFileSafe($dashboardPath, $dashboard);
ok("Updated dashboard.blade.php.");

/*
|--------------------------------------------------------------------------
| 3. REPORTS VIEW
|--------------------------------------------------------------------------
*/

$reports = file_get_contents($reportsPath);
if ($reports === false) {
    fail("Could not read reports view.");
}

$reports = str_replace(
    'Admin Reports',
    'Dashboard &amp; Reports',
    $reports
);

$reports = str_replace(
    'Business and employee transaction reports.',
    'Generate, review, and print business or employee reports.',
    $reports
);

/* Insert shared tabs once under the reports description. */
if (!str_contains($reports, "@include('reports.tabs')")) {
    $reportsDescriptionPattern =
        '/(<p class="text-xs text-slate-500 dark:text-slate-400 mt-1">\s*' .
        '(?:Generate, review, and print business or employee reports\.|Business and employee transaction reports\.)\s*' .
        '<\/p>)/s';

    $reportsUpdated = preg_replace(
        $reportsDescriptionPattern,
        "$1\n\n            @include('reports.tabs')",
        $reports,
        1,
        $reportsCount
    );

    if ($reportsUpdated === null) {
        fail("Regex error while updating reports/index.blade.php.");
    }

    if ($reportsCount === 0) {
        fail(
            "Could not locate the Reports header safely.\n" .
            "Your reports/index.blade.php may be different from the GitHub version."
        );
    }

    $reports = $reportsUpdated;
}

writeFileSafe($reportsPath, $reports);
ok("Updated reports/index.blade.php.");

/*
|--------------------------------------------------------------------------
| 4. APP SIDEBAR
|--------------------------------------------------------------------------
*/

$layout = file_get_contents($layoutPath);
if ($layout === false) {
    fail("Could not read layouts/app.blade.php.");
}

/*
 * Make the combined sidebar entry active for BOTH dashboard and reports.
 * The GitHub version has two occurrences in the Dashboard link:
 * one for the <a> class and one for the icon class.
 */
$layout = str_replace(
    "request()->routeIs('dashboard')",
    "(request()->routeIs('dashboard') || request()->routeIs('reports.*'))",
    $layout
);

/* Rename the visible Dashboard entry. */
$layout = str_replace(
    '<span class="font-display">Executive Dashboard</span>',
    '<span class="font-display">Dashboard &amp; Reports</span>',
    $layout
);

/*
 * Remove the separate Reports sidebar link.
 * It remains fully accessible through the "Printable Reports" tab.
 */
$reportsLinkPattern =
    '/\s*<a href="\{\{ route\(\'reports\.index\'\) \}\}"' .
    '.*?<span class="font-display">Reports<\/span>\s*<\/a>\s*/s';

$layoutUpdated = preg_replace(
    $reportsLinkPattern,
    "\n",
    $layout,
    1,
    $removedReportsLink
);

if ($layoutUpdated === null) {
    fail("Regex error while updating layouts/app.blade.php.");
}

$layout = $layoutUpdated;

if ($removedReportsLink === 0) {
    info(
        "Separate Reports sidebar link was not found. " .
        "It may already have been removed."
    );
} else {
    ok("Removed separate Reports sidebar entry.");
}

writeFileSafe($layoutPath, $layout);
ok("Updated layouts/app.blade.php.");

/*
|--------------------------------------------------------------------------
| 5. FINISH
|--------------------------------------------------------------------------
*/

echo PHP_EOL;
echo "============================================================\n";
echo " DASHBOARD + PRINTABLE REPORTS COMBINED SUCCESSFULLY\n";
echo "============================================================\n";
echo PHP_EOL;
echo "Visible admin module:\n";
echo "  Dashboard & Reports\n";
echo PHP_EOL;
echo "Tabs:\n";
echo "  1. Overview           -> /dashboard\n";
echo "  2. Printable Reports  -> /reports\n";
echo PHP_EOL;
echo "Printing remains:\n";
echo "  /reports/print\n";
echo PHP_EOL;
echo "No controllers, routes, database tables, or report logic were changed.\n";
echo PHP_EOL;
echo "Now run:\n";
echo "  php artisan optimize:clear\n";
echo PHP_EOL;
