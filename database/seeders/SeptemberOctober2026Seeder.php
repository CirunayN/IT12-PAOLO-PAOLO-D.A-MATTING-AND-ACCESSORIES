<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use App\Services\SqlServerSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/**
 * Add simulated shop history without resetting existing business records.
 * Receipts and quantities are fictional; product retail references are sourced.
 * Only batches added by this run fund its sales, preserving existing balances.
 */
class SeptemberOctober2026Seeder extends Seeder
{
    public const START = '2026-09-01';
    public const END = '2026-10-10';
    public const OPENING = '2026-08-30';
    public const REFERENCE_PREFIX = 'DEMO-SEP2026-';

    public array $report = [];

    private Randomizer $random;
    private array $profiles = [];
    private array $batches = [];
    private array $saleIds = [];
    private array $itemIds = [];
    private array $newProductIds = [];
    private array $daily = [];

    public function manifestPath(): string
    {
        $databaseKey = substr(hash('sha256', DB::connection()->getConfig('host').'|'.DB::connection()->getDatabaseName()), 0, 16);

        return storage_path('app/seed-history/'.$databaseKey.'-september-october-2026.json');
    }

    public function run(?int $randomSeed = null): void
    {
        File::ensureDirectoryExists(dirname($this->manifestPath()));
        $lock = fopen($this->manifestPath().'.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('This history seed is already running.');
        }

        try {
            if (File::exists($this->manifestPath())) {
                $manifest = json_decode(File::get($this->manifestPath()), true, 512, JSON_THROW_ON_ERROR);
                $ids = $manifest['sale_ids'] ?? [];
                if ($ids === [] || Sale::whereIn('ID', $ids)->count() !== count($ids)) {
                    throw new RuntimeException('The seed manifest does not match this database. Refusing to duplicate history.');
                }
                $this->report = $manifest['report'];
                $this->command?->info('This date range has already been seeded; no records were added.');

                return;
            }
            if (Sale::where('GCash_Reference_Number', 'like', self::REFERENCE_PREFIX.'%')->exists()) {
                throw new RuntimeException('Sample history already exists without its manifest. Refusing to duplicate it.');
            }

            $staff = User::where('is_active', true)->orderBy('id')->get()->filter(fn (User $user) => $user->isCashier());
            $admin = $staff->first(fn (User $user) => $user->isAdmin());
            $cashiers = $staff->reject(fn (User $user) => $user->isAdmin())->values();
            if (!$admin) {
                throw new RuntimeException('An existing active administrator is required to receive stock.');
            }

            $randomSeed ??= random_int(1, 2147483647);
            $this->random = new Randomizer(new Mt19937($randomSeed));
            $originals = $this->businessRows();
            File::ensureDirectoryExists(storage_path('app/backups'));
            $backup = storage_path('app/backups/pre_history_seed_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(3)).'.json');
            app(SqlServerSnapshotService::class)->write(DB::connection(), $backup);

            DB::transaction(function () use ($originals, $admin, $cashiers, $randomSeed, $backup) {
                $active = Status::firstOrCreate(['Name' => 'Active']);
                $payments = ['Cash' => PaymentMethod::firstOrCreate(['Name' => 'Cash'])->ID,
                    'GCash' => PaymentMethod::firstOrCreate(['Name' => 'GCash'])->ID];
                $this->loadProfiles($active->ID);
                $this->receiveStock(Carbon::parse(self::OPENING), $admin->id);

                for ($day = Carbon::parse(self::START); $day->toDateString() <= self::END; $day->addDay()) {
                    if ($day->isSunday()) {
                        $this->receiveStock($day, $admin->id);
                    }
                    $count = $day->isSunday() ? $this->random->getInt(2, 6)
                        : ($day->isSaturday() ? $this->random->getInt(7, 15) : $this->random->getInt(3, 12));
                    $seconds = [];
                    while (count($seconds) < $count) {
                        // Sunday receiving completes before the shop's first sales.
                        $second = $this->random->getInt($day->isSunday() ? 36000 : 30600, $day->isSunday() ? 55800 : 64200);
                        $seconds[$second] = $second;
                    }
                    sort($seconds);
                    $this->daily[$day->toDateString()] = ['sales' => 0, 'total_cents' => 0];
                    foreach ($seconds as $second) {
                        $cashier = $cashiers->isNotEmpty() && $this->random->getInt(1, 100) <= 76
                            ? $cashiers[$this->random->getInt(0, $cashiers->count() - 1)] : $admin;
                        $this->sell($day->copy()->startOfDay()->addSeconds($second), $cashier->id, $payments);
                    }
                }

                $this->assertOriginalsUnchanged($originals);
                $this->verifyInventory();
                $this->report = [
                    'range' => [self::START, self::END], 'opening_stock_date' => self::OPENING,
                    'new_products' => count($this->newProductIds), 'sales' => count($this->saleIds),
                    'sale_lines' => count($this->itemIds), 'stock_batches' => count($this->batches),
                    'units_received' => array_sum(array_column($this->batches, 'quantity')),
                    'units_sold' => (int) SoldItem::whereIn('ID', $this->itemIds)->sum('Quantity'),
                    'total_sales' => array_sum(array_column($this->daily, 'total_cents')) / 100,
                    'daily' => array_map(fn ($day) => ['sales' => $day['sales'], 'total' => $day['total_cents'] / 100], $this->daily),
                    'restock_dates' => array_values(array_unique(array_map(fn ($batch) => substr($batch['received_at'], 0, 10), $this->batches))),
                    'backup' => $backup, 'simulated' => true,
                ];
                // Keep the inserted IDs before commit so a repeat cannot silently duplicate a committed run.
                File::replace($this->manifestPath(), json_encode([
                    'format' => 'paolo-history-seed', 'database' => DB::connection()->getDatabaseName(),
                    'random_seed' => $randomSeed, 'simulated' => true, 'created_at' => now()->toIso8601String(),
                    'sale_ids' => $this->saleIds, 'sold_item_ids' => $this->itemIds,
                    'new_product_ids' => $this->newProductIds, 'stock_batch_ids' => array_keys($this->batches),
                    'fifo_allocations' => $this->batches, 'report' => $this->report,
                ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            });
            $this->command?->info(json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    private function loadProfiles(int $activeId): void
    {
        $knownNames = [];
        foreach (Product::with('category', 'status')->orderBy('ID')->get() as $product) {
            if ($product->status?->Name === 'Archived' || $product->category?->Is_Archived || isset($knownNames[$product->Name])) {
                continue;
            }
            $latest = $product->stockIns()->orderByDesc('ID')->first();
            if (!$latest || (float) $latest->Retail_Price <= 0) {
                continue;
            }
            $group = str_contains(strtolower($product->category?->Name ?? ''), 'fluid') ? 'fluid'
                : ((float) $latest->Retail_Price >= 1800 ? 'matting' : 'accessory');
            $this->profiles[$product->ID] = ['id' => $product->ID, 'group' => $group, 'launch' => self::OPENING,
                'retail_cents' => (int) round((float) $latest->Retail_Price * 100),
                'cost_cents' => (int) round((float) $latest->Cost_Price * 100), 'batch_ids' => []];
            $knownNames[$product->Name] = true;
        }

        $catalog = json_decode(File::get(database_path('seeders/data/automotive-catalog-2026.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($catalog['products'] as $item) {
            if (isset($knownNames[$item['name']])) {
                continue;
            }
            if (Product::where('Name', $item['name'])->exists()) {
                throw new RuntimeException('A sourced product already exists but is unavailable; inspect it before seeding.');
            }
            $launched = $item['launch'].' 07:30:00';
            $category = Category::where('Name', $item['category'])->first();
            if ($category?->Is_Archived) {
                throw new RuntimeException('A required category is archived: '.$item['category']);
            }
            $category ??= Category::create(['Name' => $item['category'], 'Is_Archived' => false]);
            $product = new Product(['Name' => $item['name'], 'Description' => $item['description'],
                'Category_ID' => $category->ID, 'Status_ID' => $activeId]);
            $product->created_at = $launched;
            $product->updated_at = $launched;
            $product->save();
            $this->newProductIds[] = $product->ID;
            $this->profiles[$product->ID] = ['id' => $product->ID, 'group' => $item['group'], 'launch' => $item['launch'],
                'retail_cents' => (int) round($item['retail'] * 100), 'cost_cents' => (int) round($item['cost'] * 100), 'batch_ids' => []];
            $knownNames[$item['name']] = true;
        }
    }

    private function receiveStock(Carbon $day, int $adminId): void
    {
        $targets = [];
        foreach ($this->profiles as $id => $profile) {
            if ($profile['launch'] > $day->toDateString()) {
                continue;
            }
            [$min, $max] = match ($profile['group']) {
                'matting' => [3, 6], 'scent' => [10, 19], 'fluid' => [6, 12],
                'care' => [5, 11], 'wiper' => [3, 8], default => [3, 7],
            };
            $targets[$id] = $this->random->getInt($min, $max);
        }
        // A newly introduced, small catalog still needs enough units for a week's trade.
        $scale = max(1, 240 / max(1, array_sum($targets)));
        foreach ($targets as $id => $weeklyTarget) {
            $profile = $this->profiles[$id];
            $target = (int) ceil($weeklyTarget * $scale);
            $remaining = $this->available($id);
            if ($remaining >= $target) {
                continue;
            }
            $quantity = $target - $remaining + $this->random->getInt(0, 2);
            $time = $day->copy()->startOfDay()->addSeconds($this->random->getInt(28200, 34200))->format('Y-m-d H:i:s');
            $batch = new StockIn(['Product_ID' => $id, 'User_ID' => $adminId, 'Quantity' => $quantity,
                'Remaining_Quantity' => $quantity,
                'Cost_Price' => round($profile['cost_cents'] * $this->random->getInt(97, 103) / 10000, 2),
                'Retail_Price' => $profile['retail_cents'] / 100, 'Has_Expiration' => false, 'Condition' => 'Good']);
            $batch->created_at = $time;
            $batch->updated_at = $time;
            $batch->save();
            $this->batches[$batch->ID] = ['product_id' => $id, 'received_at' => $time,
                'quantity' => $quantity, 'remaining' => $quantity, 'allocations' => []];
            $this->profiles[$id]['batch_ids'][] = $batch->ID;
        }
    }

    private function available(int $productId): int
    {
        return array_sum(array_map(fn ($id) => $this->batches[$id]['remaining'], $this->profiles[$productId]['batch_ids']));
    }

    private function pickProduct(array $candidates, ?string $group): int
    {
        $weights = [];
        foreach ($candidates as $id) {
            $profile = $this->profiles[$id];
            $weight = match ($profile['group']) {
                'scent' => 7, 'care' => 5, 'fluid' => 4, 'wiper' => 3, default => 2,
            };
            $weights[$id] = $weight * ($group === $profile['group'] ? 3 : 1);
        }
        $choice = $this->random->getInt(1, array_sum($weights));
        foreach ($weights as $id => $weight) {
            $choice -= $weight;
            if ($choice <= 0) {
                return $id;
            }
        }
        throw new RuntimeException('No stocked product could be selected.');
    }

    private function sell(Carbon $time, int $cashierId, array $payments): void
    {
        $candidates = array_keys(array_filter($this->profiles, fn ($profile) => $this->available($profile['id']) > 0));
        if ($candidates === []) {
            return;
        }
        $roll = $this->random->getInt(1, 100);
        $lineCount = min(count($candidates), $roll <= 48 ? 1 : ($roll <= 80 ? 2 : ($roll <= 94 ? 3 : 4)));
        $lines = [];
        $group = null;
        for ($index = 0; $index < $lineCount; $index++) {
            $id = $this->pickProduct($candidates, $group);
            $profile = $this->profiles[$id];
            $group ??= $profile['group'];
            $quantityRoll = $this->random->getInt(1, 100);
            $quantity = $quantityRoll <= 75 ? 1 : ($quantityRoll <= 94 ? 2 : 3);
            if ($profile['group'] === 'matting') {
                $quantity = $quantityRoll > 97 ? 2 : 1;
            }
            $quantity = min($quantity, $this->available($id));
            $lines[] = ['product_id' => $id, 'quantity' => $quantity, 'total_cents' => $profile['retail_cents'] * $quantity];
            $candidates = array_values(array_diff($candidates, [$id]));
        }
        $total = array_sum(array_column($lines, 'total_cents'));
        $gcash = $this->random->getInt(1, 100) <= 34;
        $received = $total;
        if (!$gcash && $this->random->getInt(1, 100) > 32) {
            $denominations = [2000, 5000, 10000, 50000, 100000];
            $denomination = $denominations[$this->random->getInt(0, count($denominations) - 1)];
            $received = (int) (ceil($total / $denomination) * $denomination);
        }
        $sale = new Sale(['Date' => $time->format('Y-m-d H:i:s'), 'Total' => $total / 100,
            'Amount_Received' => $received / 100, 'Change_Amount' => ($received - $total) / 100,
            'GCash_Reference_Number' => $gcash ? self::REFERENCE_PREFIX.$time->format('Ymd').'-'.bin2hex($this->random->getBytes(6)) : null,
            'User_ID' => $cashierId, 'Payment_Method_ID' => $payments[$gcash ? 'GCash' : 'Cash']]);
        $sale->created_at = $time;
        $sale->updated_at = $time;
        $sale->save();
        $this->saleIds[] = $sale->ID;
        foreach ($lines as $line) {
            $item = new SoldItem(['Product_ID' => $line['product_id'], 'Quantity' => $line['quantity'],
                'Total' => $line['total_cents'] / 100, 'Sale_ID' => $sale->ID]);
            $item->created_at = $time;
            $item->updated_at = $time;
            $item->save();
            $this->itemIds[] = $item->ID;
            $toAllocate = $line['quantity'];
            foreach ($this->profiles[$line['product_id']]['batch_ids'] as $batchId) {
                $take = min($toAllocate, $this->batches[$batchId]['remaining']);
                if ($take > 0) {
                    $this->batches[$batchId]['remaining'] -= $take;
                    $this->batches[$batchId]['allocations'][] = ['sale_id' => $sale->ID, 'sold_at' => $time->format('Y-m-d H:i:s'), 'quantity' => $take];
                    StockIn::where('ID', $batchId)->update(['Remaining_Quantity' => $this->batches[$batchId]['remaining'], 'updated_at' => $time]);
                    $toAllocate -= $take;
                }
                if ($toAllocate === 0) {
                    break;
                }
            }
            if ($toAllocate !== 0) {
                throw new RuntimeException('A simulated sale exceeded its available stock.');
            }
        }
        $day = $time->toDateString();
        $this->daily[$day]['sales']++;
        $this->daily[$day]['total_cents'] += $total;
    }

    private function businessRows(): array
    {
        $rows = [];
        foreach (['tbl_product', 'tbl_stock_in', 'tbl_sale', 'tbl_sold_item', 'tbl_category', 'tbl_status', 'tbl_payment_method'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('ID')->get()->map(fn ($row) => (array) $row)->all();
        }
        $rows['users'] = DB::table('users')->orderBy('id')->get(['id', 'name', 'username', 'role', 'is_active'])->map(fn ($row) => (array) $row)->all();

        return $rows;
    }

    private function assertOriginalsUnchanged(array $originals): void
    {
        $after = $this->businessRows();
        foreach ($originals as $table => $rows) {
            $key = $table === 'users' ? 'id' : 'ID';
            $ids = array_column($rows, $key);
            $oldRowsAfter = array_values(array_filter($after[$table], fn ($row) => in_array($row[$key], $ids, true)));
            if ($rows !== $oldRowsAfter) {
                throw new RuntimeException('Seeding changed an existing record in '.$table.'. All seed changes were rolled back.');
            }
        }
    }

    private function verifyInventory(): void
    {
        foreach ($this->batches as $id => $batch) {
            $used = array_sum(array_column($batch['allocations'], 'quantity'));
            if ($batch['remaining'] < 0 || $used + $batch['remaining'] !== $batch['quantity']
                || !Carbon::parse($batch['received_at'])->isSunday()
                || (int) StockIn::findOrFail($id)->Remaining_Quantity !== $batch['remaining']) {
                throw new RuntimeException('Seed batch balance or Sunday receiving validation failed.');
            }
            foreach ($batch['allocations'] as $allocation) {
                if ($allocation['sold_at'] < $batch['received_at']) {
                    throw new RuntimeException('A seed sale precedes its stock delivery.');
                }
            }
        }
        if (count($this->daily) !== 40 || min(array_column($this->daily, 'sales')) < 1) {
            throw new RuntimeException('The history does not cover every day in the requested range.');
        }
    }
}
