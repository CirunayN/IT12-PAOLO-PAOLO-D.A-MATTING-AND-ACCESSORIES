<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;
use App\Models\Category;

echo "\n=======================================================\n";
echo "       PAOLO PAOLO D.A MATTING & ACCESSORIES\n";
echo "              DATABASE OVERVIEW (p7db)\n";
echo "=======================================================\n\n";

echo "--- 1. REGISTERED USER ACCOUNTS ---\n";
printf("%-4s | %-12s | %-18s | %-22s | %-20s\n", "ID", "Username", "Name", "Role", "Email");
echo str_repeat("-", 85) . "\n";

foreach (User::all() as $u) {
    printf("%-4d | %-12s | %-18s | %-22s | %-20s\n", $u->id, $u->username, $u->name, $u->role, $u->email);
}

echo "\n--- 2. DATABASE SUMMARY ---\n";
echo "Total Users      : " . User::count() . "\n";
echo "Total Products   : " . Product::count() . "\n";
echo "Total Categories : " . Category::count() . "\n";
echo "Default Password : 123 (for all seeded accounts)\n\n";
