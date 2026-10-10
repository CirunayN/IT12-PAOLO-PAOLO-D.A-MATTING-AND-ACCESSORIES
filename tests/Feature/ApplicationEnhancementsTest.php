<?php

namespace Tests\Feature;

use App\Http\Controllers\BackupController;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use App\Services\NativeBackupFolderPicker;
use App\Services\SqlServerSnapshotService;
use Database\Seeders\SeptemberOctober2026Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationEnhancementsTest extends TestCase
{
    private User $admin;

    private Product $product;

    private StockIn $batch;

    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('SQLSERVER_TEST_DATABASE');
        if (! $database || ! extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('SQL Server test database required.');
        }
        if (! preg_match('/^[a-zA-Z0-9_]+_sqlserver_test$/', $database)) {
            throw new \RuntimeException('Dedicated test database required.');
        }
        config(['database.default' => 'sqlsrv', 'database.connections.sqlsrv.database' => $database, 'database.connections.sqlsrv.url' => null,
            'database.connections.sqlsrv.host' => '.\\SQLEXPRESS', 'database.connections.sqlsrv.port' => '', 'database.connections.sqlsrv.username' => null,
            'database.connections.sqlsrv.password' => null, 'database.connections.sqlsrv.encrypt' => 'yes', 'database.connections.sqlsrv.trust_server_certificate' => 'yes']);
        DB::purge('sqlsrv');
        Artisan::call('migrate:fresh', ['--database' => 'sqlsrv', '--force' => true]);
        $this->temporaryRoot = sys_get_temp_dir().'/paolo-enhancements-'.Str::uuid();
        File::ensureDirectoryExists($this->temporaryRoot.'/storage/app');
        File::ensureDirectoryExists($this->temporaryRoot.'/backups');
        $this->app->useStoragePath($this->temporaryRoot.'/storage');
        $this->admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $category = Category::create(['Name' => 'Accessories']);
        $status = Status::create(['Name' => 'Active']);
        $this->product = Product::create(['Name' => 'Test floor mat', 'Category_ID' => $category->ID, 'Status_ID' => $status->ID]);
        $this->batch = StockIn::create(['Product_ID' => $this->product->ID, 'User_ID' => $this->admin->id, 'Quantity' => 10, 'Remaining_Quantity' => 6, 'Cost_Price' => 50, 'Retail_Price' => 100, 'Has_Expiration' => false]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryRoot)) {
            $resolved = realpath($this->temporaryRoot);
            if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'paolo-enhancements-')) {
                File::deleteDirectory($resolved);
            }
        }
        parent::tearDown();
    }

    private function payload(array $batchOverrides = []): array
    {
        return ['Name' => 'Edited floor mat', 'Description' => 'Updated details', 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID,
            'batches' => [$this->batch->ID => array_replace(['ID' => $this->batch->ID, 'Quantity' => 12, 'Cost_Price' => 55.25, 'Retail_Price' => 125.50, 'Has_Expiration' => true, 'Expiration_Date' => '2026-09-01', 'Condition' => 'Damaged'], $batchOverrides)]];
    }

    private function settings(array $overrides = []): array
    {
        $settings = array_replace(['backup_mode' => 'automatic', 'frequency' => '1_day', 'backup_time' => '18:00', 'retention' => 'keep_all', 'storage_path' => $this->temporaryRoot.'/backups', 'last_backup_at' => null, 'last_automatic_backup_at' => null, 'gdrive_enabled' => false], $overrides);
        File::put(storage_path('app/backup_settings.json'), json_encode($settings));

        return $settings;
    }

    public function test_batch_edit_updates_all_details_and_preserves_used_units_and_audit(): void
    {
        $before = $this->batch->fresh()->created_at;
        $this->actingAs($this->admin)->get(route('products.edit', $this->product->ID))->assertOk()->assertSee('Stock batches')->assertSee('Expiration_Date', false);
        $this->put(route('products.update', $this->product->ID), $this->payload())->assertRedirect(route('products.index'))->assertSessionHasNoErrors();
        $batch = $this->batch->fresh();
        $this->assertSame('12.00', $batch->Quantity);
        $this->assertSame('8.00', $batch->Remaining_Quantity);
        $this->assertSame('55.25', $batch->Cost_Price);
        $this->assertSame('125.50', $batch->Retail_Price);
        $this->assertSame('Damaged', $batch->Condition);
        $this->assertTrue($batch->Has_Expiration);
        $this->assertSame('2026-09-01', $batch->Expiration_Date->format('Y-m-d'));
        $this->assertEquals($this->admin->id, $batch->User_ID);
        $this->assertTrue($before->equalTo($batch->created_at));
    }

    public function test_invalid_batch_edits_never_partially_update_the_product(): void
    {
        foreach ([['Quantity' => 3], ['Cost_Price' => -1], ['Retail_Price' => 1.234], ['Has_Expiration' => true, 'Expiration_Date' => null], ['Expiration_Date' => '2026-02-30'], ['Condition' => 'Unknown']] as $input) {
            $this->actingAs($this->admin)->putJson(route('products.update', $this->product->ID), $this->payload($input))->assertUnprocessable();
            $this->assertSame('Test floor mat', $this->product->fresh()->Name);
            $this->assertSame('6.00', $this->batch->fresh()->Remaining_Quantity);
        }
    }

    public function test_expiration_can_be_removed_and_foreign_batches_are_rejected(): void
    {
        $this->actingAs($this->admin)->put(route('products.update', $this->product->ID), $this->payload(['Has_Expiration' => false]))->assertSessionHasNoErrors();
        $this->assertNull($this->batch->fresh()->Expiration_Date);
        $other = Product::create(['Name' => 'Other item', 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID]);
        $foreign = StockIn::create(['Product_ID' => $other->ID, 'User_ID' => $this->admin->id, 'Quantity' => 2]);
        $this->putJson(route('products.update', $this->product->ID), $this->payload(['ID' => $foreign->ID]))->assertUnprocessable();
        $cashier = User::factory()->create(['role' => 'Cashier', 'is_active' => true]);
        $this->actingAs($cashier)->putJson(route('products.update', $this->product->ID), $this->payload())->assertForbidden();
    }

    public function test_dashboard_sales_respect_dates_and_show_current_stock_alerts(): void
    {
        $payment = PaymentMethod::create(['Name' => 'Cash']);
        foreach (['2026-10-01 12:00:00' => 50, '2026-10-02 23:59:59' => 100, '2026-10-03 00:00:00' => 200] as $date => $total) {
            Sale::create(['Date' => $date, 'Total' => $total, 'User_ID' => $this->admin->id, 'Payment_Method_ID' => $payment->ID]);
        }
        $empty = Product::create(['Name' => 'Empty item', 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID]);
        $this->batch->update(['Remaining_Quantity' => 3]);
        $this->actingAs($this->admin)->get(route('dashboard', ['start_date' => '2026-10-01', 'end_date' => '2026-10-02']))
            ->assertOk()->assertViewHas('todaySalesTotal', 150)->assertViewHas('todaySalesCount', 2)
            ->assertViewHas('lowStockCount', 1)->assertViewHas('outOfStockCount', 1)->assertSee('Total Items')->assertSee('Low / Out of Stock')->assertSee('Empty item');
        $this->getJson(route('dashboard', ['start_date' => '2026-10-03', 'end_date' => '2026-10-01']))->assertUnprocessable();
    }

    public function test_inventory_and_receiving_exports_retain_filters_and_return_real_pdfs(): void
    {
        $empty = Product::create(['Name' => 'Empty item', 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID]);
        $this->actingAs($this->admin)->get(route('products.index', ['stock_level' => 'out']))->assertOk()->assertSee('Empty item')->assertDontSee('Test floor mat');
        $this->get(route('products.print', ['stock_level' => 'out']))->assertOk()->assertSee('Empty item')->assertDontSee('Test floor mat');
        $this->get(route('stock-in.print', ['user_id' => 99999]))->assertOk()->assertDontSee('Test floor mat');
        foreach (['products.print', 'stock-in.print', 'reports.print', 'transactions.print'] as $route) {
            $response = $this->get(route($route, ['output' => 'pdf', 'period' => 'overall']));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_transaction_filters_and_exports_share_the_same_month_and_cashier_scope(): void
    {
        $cash = PaymentMethod::create(['Name' => 'Cash']);
        $gcash = PaymentMethod::create(['Name' => 'GCash']);
        $cashier = User::factory()->create(['role' => 'Cashier']);
        $makeSale = fn ($date, $user, $payment, $total, $reference = null) => Sale::create([
            'Date' => $date, 'User_ID' => $user->id, 'Payment_Method_ID' => $payment->ID,
            'Total' => $total, 'GCash_Reference_Number' => $reference,
        ]);
        $target = $makeSale('2026-10-31 23:59:59', $cashier, $gcash, 125, 'GCASH-MERGE-104');
        $makeSale('2026-11-01 00:00:00', $cashier, $gcash, 250, 'GCASH-NEXT-MONTH');
        $makeSale('2026-10-20 12:00:00', $cashier, $cash, 50);
        $makeSale('2026-10-20 12:00:00', $this->admin, $gcash, 75, 'GCASH-OTHER-USER');
        $filters = ['period' => 'monthly', 'month' => '2026-10', 'payment_method' => 'GCash', 'cashier_id' => $cashier->id];
        $this->actingAs($this->admin)->get(route('transactions.index', $filters))
            ->assertOk()->assertViewHas('totalTransactions', 1)->assertViewHas('totalSales', 125.0)
            ->assertViewHas('sales', fn ($sales) => $sales->pluck('ID')->all() === [$target->ID]);
        $this->get(route('transactions.print', $filters))->assertOk()
            ->assertViewHas('sales', fn ($sales) => $sales->pluck('ID')->all() === [$target->ID]);
        $this->get(route('transactions.index', array_replace($filters, ['search' => 'INV-'.$target->ID])))
            ->assertOk()->assertViewHas('totalTransactions', 1);
        $csv = $this->get(route('transactions.export_csv', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('GCASH-MERGE-104', $csv);
        $this->assertStringNotContainsString('GCASH-NEXT-MONTH', $csv);
        $this->assertStringNotContainsString('GCASH-OTHER-USER', $csv);
    }

    public function test_cashiers_cannot_read_other_cashiers_transactions_or_assign_them_a_sale(): void
    {
        $cash = PaymentMethod::create(['Name' => 'Cash']);
        $cashier = User::factory()->create(['role' => 'Cashier']);
        Sale::create(['Date' => now(), 'Total' => 100, 'User_ID' => $cashier->id, 'Payment_Method_ID' => $cash->ID]);
        Sale::create(['Date' => now(), 'Total' => 200, 'User_ID' => $this->admin->id, 'Payment_Method_ID' => $cash->ID]);
        $filters = ['period' => 'overall', 'cashier_id' => $this->admin->id];
        $this->actingAs($cashier)->get(route('transactions.index', $filters))
            ->assertOk()->assertViewHas('totalSales', 100.0)->assertViewHas('totalTransactions', 1);
        $this->get(route('transactions.print', $filters))->assertOk()->assertViewHas('totalSales', 100.0);
        $remaining = $this->batch->fresh()->Remaining_Quantity;
        $this->postJson(route('pos.checkout'), [
            'payment_method_id' => $cash->ID, 'user_id' => $this->admin->id, 'amount_tendered' => 100,
            'items' => [['product_id' => $this->product->ID, 'quantity' => 1]],
        ])->assertForbidden();
        $this->assertEquals($remaining, $this->batch->fresh()->Remaining_Quantity);
        $this->assertSame(2, Sale::count());
    }

    public function test_admin_checkout_assignment_requires_an_active_cashier(): void
    {
        $cash = PaymentMethod::create(['Name' => 'Cash']);
        $cashier = User::factory()->create(['role' => 'Cashier']);
        $payload = ['payment_method_id' => $cash->ID, 'user_id' => $cashier->id, 'amount_tendered' => 100,
            'items' => [['product_id' => $this->product->ID, 'quantity' => 1]]];
        $this->actingAs($this->admin)->postJson(route('pos.checkout'), $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertEquals($cashier->id, Sale::first()->User_ID);
        $cashier->update(['is_active' => false]);
        $this->postJson(route('pos.checkout'), $payload)->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertSame(1, Sale::count());
    }

    public function test_transaction_filters_reject_invalid_sql_server_values(): void
    {
        $this->actingAs($this->admin)->getJson(route('transactions.index', ['cashier_id' => 'broken']))
            ->assertUnprocessable()->assertJsonValidationErrors('cashier_id');
        $this->getJson(route('transactions.index', ['month' => '2026-13']))
            ->assertUnprocessable()->assertJsonValidationErrors('month');
    }

    public function test_inventory_order_uses_sellable_quantities_and_current_prices_and_exports_keep_it(): void
    {
        $create = fn ($name) => Product::create(['Name' => $name, 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID]);
        $alpha = $create('Alpha item');
        $empty = $create('Zulu item');
        $middle = $create('Middle item');
        foreach ([[$alpha, 2, 250], [$middle, 9, 40]] as [$product, $quantity, $price]) {
            StockIn::create(['Product_ID' => $product->ID, 'User_ID' => $this->admin->id, 'Quantity' => $quantity, 'Remaining_Quantity' => $quantity, 'Retail_Price' => $price, 'Has_Expiration' => false]);
        }
        // Newer unsellable batches must not change either the shown stock or price order.
        StockIn::create(['Product_ID' => $alpha->ID, 'User_ID' => $this->admin->id, 'Quantity' => 999, 'Retail_Price' => 9999, 'Condition' => 'Damaged']);
        StockIn::create(['Product_ID' => $middle->ID, 'User_ID' => $this->admin->id, 'Quantity' => 999, 'Retail_Price' => 9999, 'Has_Expiration' => true, 'Expiration_Date' => today()->subDay()]);
        $expected = [
            'newest' => [$middle->ID, $empty->ID, $alpha->ID, $this->product->ID],
            'oldest' => [$this->product->ID, $alpha->ID, $empty->ID, $middle->ID],
            'name_asc' => [$alpha->ID, $middle->ID, $this->product->ID, $empty->ID],
            'name_desc' => [$empty->ID, $this->product->ID, $middle->ID, $alpha->ID],
            'stock_asc' => [$empty->ID, $alpha->ID, $this->product->ID, $middle->ID],
            'stock_desc' => [$middle->ID, $this->product->ID, $alpha->ID, $empty->ID],
            'price_asc' => [$empty->ID, $middle->ID, $this->product->ID, $alpha->ID],
            'price_desc' => [$alpha->ID, $this->product->ID, $middle->ID, $empty->ID],
        ];
        $this->actingAs($this->admin);
        foreach ($expected as $sort => $ids) {
            $this->get(route('products.index', ['sort' => $sort]))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->pluck('ID')->all() === $ids);
            $this->get(route('products.print', ['sort' => $sort]))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->pluck('ID')->all() === $ids);
        }
        $this->get(route('products.index', ['sort' => 'stock_desc', 'stock_level' => 'available']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('ID')->all() === [$middle->ID, $this->product->ID]);
        $this->getJson(route('products.index', ['sort' => 'invalid']))->assertUnprocessable();
    }

    public function test_dashboard_product_rankings_use_period_quantities_and_include_zero_sales(): void
    {
        $cash = PaymentMethod::create(['Name' => 'Cash']);
        $products = [];
        foreach (['Popular air freshener', 'Premium floor mat', 'Alpha wiper', 'Beta wiper', 'Slow towel', 'Zero alpha', 'Zero beta'] as $name) {
            $products[$name] = Product::create(['Name' => $name, 'Category_ID' => $this->product->Category_ID, 'Status_ID' => $this->product->Status_ID]);
        }
        $archived = Product::create(['Name' => 'Archived sample product', 'Category_ID' => $this->product->Category_ID,
            'Status_ID' => Status::create(['Name' => 'Archived'])->ID]);
        $makeSale = function (string $date, array $lines) use ($cash): void {
            $sale = Sale::create(['Date' => $date, 'Total' => array_sum(array_column($lines, 2)),
                'User_ID' => $this->admin->id, 'Payment_Method_ID' => $cash->ID]);
            foreach ($lines as [$product, $quantity, $total]) {
                SoldItem::create(['Product_ID' => $product->ID, 'Quantity' => $quantity, 'Total' => $total, 'Sale_ID' => $sale->ID]);
            }
        };
        $makeSale('2026-10-01 12:00:00', [
            [$products['Popular air freshener'], 10, 100], [$products['Premium floor mat'], 6, 6000],
            [$products['Alpha wiper'], 4, 400], [$products['Beta wiper'], 4, 40],
            [$products['Slow towel'], 1.25, 125], [$this->product, 2, 200], [$archived, 100, 10000],
        ]);
        $makeSale('2026-10-02 23:59:59', [[$products['Popular air freshener'], 2, 20]]);
        $makeSale('2026-10-03 00:00:00', [[$products['Zero alpha'], 99, 9900]]);
        $makeSale('2026-09-30 23:59:59', [[$products['Zero beta'], 88, 8800]]);

        $expectedBest = array_map(fn ($product) => (int) $product->ID, [
            $products['Popular air freshener'], $products['Premium floor mat'], $products['Alpha wiper'], $products['Beta wiper'], $this->product,
        ]);
        $expectedLeast = array_map(fn ($product) => (int) $product->ID, [
            $products['Zero alpha'], $products['Zero beta'], $products['Slow towel'], $this->product, $products['Alpha wiper'],
        ]);
        $response = $this->actingAs($this->admin)->get(route('dashboard', ['start_date' => '2026-10-01', 'end_date' => '2026-10-02']));
        $response->assertOk()->assertSee('Top 5 Best-Selling Products')->assertSee('Top 5 Least-Selling Products')
            ->assertSee('Units Sold')->assertSee('1.25')->assertDontSee('Archived sample product')
            ->assertViewHas('bestSellingProducts', fn ($ranking) => $ranking->pluck('ID')->map(fn ($id) => (int) $id)->all() === $expectedBest)
            ->assertViewHas('leastSellingProducts', fn ($ranking) => $ranking->pluck('ID')->map(fn ($id) => (int) $id)->all() === $expectedLeast);
        $this->assertSame(12.0, $response->viewData('bestSellingProducts')->first()->dashboard_units_sold);
        $this->assertSame(120.0, $response->viewData('bestSellingProducts')->first()->dashboard_sales_total);
        $this->assertSame(0.0, $response->viewData('leastSellingProducts')->first()->dashboard_units_sold);

        $this->get(route('dashboard', ['start_date' => '2026-10-04', 'end_date' => '2026-10-04']))
            ->assertOk()->assertSee('No products sold in the selected dates.')
            ->assertViewHas('bestSellingProducts', fn ($ranking) => $ranking->isEmpty())
            ->assertViewHas('leastSellingProducts', fn ($ranking) => $ranking->count() === 5 && $ranking->every(fn ($product) => $product->dashboard_units_sold === 0.0));
    }

    public function test_history_seed_preserves_existing_data_and_keeps_stock_and_receipts_consistent(): void
    {
        User::factory()->create(['role' => 'Cashier', 'is_active' => true]);
        $inactive = User::factory()->create(['role' => 'Cashier', 'is_active' => false]);
        $originalBatch = $this->batch->fresh()->getAttributes();
        $originalProduct = $this->product->fresh()->getAttributes();
        $seeder = new SeptemberOctober2026Seeder;
        $seeder->run(20261010);
        $manifest = json_decode(File::get($seeder->manifestPath()), true, 512, JSON_THROW_ON_ERROR);
        $sales = Sale::whereIn('ID', $manifest['sale_ids'])->with('soldItems', 'paymentMethod')->orderBy('Date')->get();
        $batches = StockIn::whereIn('ID', $manifest['stock_batch_ids'])->get();

        $this->assertSame($originalBatch, $this->batch->fresh()->getAttributes());
        $this->assertSame($originalProduct, $this->product->fresh()->getAttributes());
        $this->assertSame(16, $seeder->report['new_products']);
        $this->assertCount(40, $sales->groupBy(fn ($sale) => $sale->Date->toDateString()));
        $this->assertSame('2026-09-01', $sales->first()->Date->toDateString());
        $this->assertSame('2026-10-10', $sales->last()->Date->toDateString());
        $this->assertTrue($batches->every(fn ($batch) => $batch->created_at->isSunday()));
        $this->assertEqualsCanonicalizing(['2026-08-30', '2026-09-06', '2026-09-13', '2026-09-20', '2026-09-27', '2026-10-04'], $seeder->report['restock_dates']);
        $this->assertTrue($sales->every(fn ($sale) => (int) $sale->User_ID !== (int) $inactive->id));
        $this->assertSame(['Cash', 'GCash'], $sales->pluck('paymentMethod.Name')->unique()->sort()->values()->all());
        $this->assertGreaterThanOrEqual(6, count(array_unique(array_column($seeder->report['daily'], 'sales'))));
        $this->assertGreaterThanOrEqual(30, count(array_unique(array_column($seeder->report['daily'], 'total'))));

        $paymentTotalsValid = true;
        $chronologyValid = true;
        $soldByProduct = [];
        foreach ($sales as $sale) {
            $cents = (int) round((float) $sale->Total * 100);
            $received = (int) round((float) $sale->Amount_Received * 100);
            $change = (int) round((float) $sale->Change_Amount * 100);
            $paymentTotalsValid = $paymentTotalsValid
                && $cents === (int) round((float) $sale->soldItems->sum('Total') * 100)
                && $received >= $cents && $received - $cents === $change;
            if ($sale->paymentMethod->Name === 'GCash') {
                $paymentTotalsValid = $paymentTotalsValid && $received === $cents && $change === 0
                    && str_starts_with($sale->GCash_Reference_Number, SeptemberOctober2026Seeder::REFERENCE_PREFIX);
            }
            foreach ($sale->soldItems as $item) {
                $receivedByThen = $batches->filter(fn ($batch) => $batch->Product_ID === $item->Product_ID && $batch->created_at->lte($sale->Date))->sum('Quantity');
                $soldByProduct[$item->Product_ID] = ($soldByProduct[$item->Product_ID] ?? 0) + (float) $item->Quantity;
                $chronologyValid = $chronologyValid && $receivedByThen >= $soldByProduct[$item->Product_ID];
            }
        }
        $this->assertTrue($paymentTotalsValid, 'Each receipt must balance, including exact GCash payment and cash change.');
        $this->assertTrue($chronologyValid, 'Products must be received before they are sold, without overselling.');
        $this->assertTrue($batches->every(fn ($batch) => (float) $batch->Remaining_Quantity >= 0 && (float) $batch->Remaining_Quantity <= (float) $batch->Quantity));
        foreach ($batches->groupBy('Product_ID') as $productId => $productBatches) {
            $this->assertEquals($productBatches->sum('Quantity') - ($soldByProduct[$productId] ?? 0), $productBatches->sum('Remaining_Quantity'));
        }
        $this->assertFileExists($seeder->report['backup']);
        $counts = [Product::count(), StockIn::count(), Sale::count(), DB::table('tbl_sold_item')->count()];
        (new SeptemberOctober2026Seeder)->run();
        $this->assertSame($counts, [Product::count(), StockIn::count(), Sale::count(), DB::table('tbl_sold_item')->count()]);
    }

    public function test_topbar_actions_are_hidden_on_their_own_pages(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('topbarPosLink')->assertSee('topbarRestockLink');
        $this->get(route('pos.index'))->assertOk()->assertDontSee('topbarPosLink')->assertDontSee('posLayoutRatio')->assertSee('posWorkspace');
        foreach (['products.index', 'stock-in.index', 'stock-in.create'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('topbarRestockLink');
        }
    }

    public function test_native_folder_selection_and_cancel_preserve_saved_settings(): void
    {
        $this->settings();
        $before = File::get(storage_path('app/backup_settings.json'));
        $this->mock(NativeBackupFolderPicker::class, fn ($mock) => $mock->shouldReceive('choose')->once()->andReturn($this->temporaryRoot.'/backups'));
        $this->actingAs($this->admin)->postJson(route('backup.folder-picker'))->assertOk()->assertJsonPath('cancelled', false);
        $this->assertSame($before, File::get(storage_path('app/backup_settings.json')));
        $this->mock(NativeBackupFolderPicker::class, fn ($mock) => $mock->shouldReceive('choose')->once()->andReturn(null));
        $this->postJson(route('backup.folder-picker'))->assertOk()->assertJsonPath('cancelled', true);
    }

    public function test_backup_time_saves_and_failed_snapshots_retry_without_marking_success(): void
    {
        $this->settings();
        $this->actingAs($this->admin)->post(route('backup.settings'), ['backup_mode' => 'automatic', 'frequency' => '1_day', 'backup_time' => '19:30', 'retention' => 'keep_all', 'storage_path' => $this->temporaryRoot.'/backups'])->assertSessionHasNoErrors();
        $this->assertSame('19:30', json_decode(File::get(storage_path('app/backup_settings.json')), true)['backup_time']);
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldReceive('write')->once()->andThrow(new \RuntimeException('Test dump failure')));
        $controller = app(BackupController::class);
        $this->assertFalse($controller->runScheduledBackup(Carbon::parse('2026-10-10 19:30', 'Asia/Manila')));
        $this->assertNull(json_decode(File::get(storage_path('app/backup_settings.json')), true)['last_automatic_backup_at']);
        $this->app->forgetInstance(SqlServerSnapshotService::class);
        $this->assertTrue($controller->runScheduledBackup(Carbon::parse('2026-10-10 19:31', 'Asia/Manila')));
        $this->assertFalse($controller->runScheduledBackup(Carbon::parse('2026-10-10 20:00', 'Asia/Manila')));
        $this->assertCount(1, File::files($this->temporaryRoot.'/backups'));
    }

    public function test_manual_backup_does_not_delay_scheduled_backup_and_retention_ignores_other_files(): void
    {
        $this->settings(['retention' => '1_week']);
        $unrelated = $this->temporaryRoot.'/backups/important.json';
        File::put($unrelated, 'keep');
        touch($unrelated, time() - 86400 * 40);
        $old = $this->temporaryRoot.'/backups/backup_p7db_old.json';
        File::put($old, 'old');
        touch($old, time() - 86400 * 40);
        $this->actingAs($this->admin)->post(route('backup.create'))->assertSessionHas('success');
        $settings = json_decode(File::get(storage_path('app/backup_settings.json')), true);
        $this->assertNull($settings['last_automatic_backup_at']);
        $this->assertFileExists($unrelated);
        $this->assertFileDoesNotExist($old);
        $this->assertTrue(app(BackupController::class)->runScheduledBackup(Carbon::parse('2026-10-10 18:00', 'Asia/Manila')));
    }

    public function test_categories_are_trimmed_unique_and_admin_only(): void
    {
        $this->actingAs($this->admin)->postJson(route('categories.store'), ['Name' => '  Interior  '])->assertOk()->assertJsonPath('category.name', 'Interior');
        $this->postJson(route('categories.store'), ['Name' => ' Interior '])->assertUnprocessable();
        $this->postJson(route('categories.store'), ['Name' => '  '])->assertUnprocessable();
        $this->actingAs(User::factory()->create(['role' => 'Cashier', 'is_active' => true]))->postJson(route('categories.store'), ['Name' => 'Other'])->assertForbidden();
    }

    public function test_category_edit_archive_and_restore_preserve_products_and_restrict_new_assignments(): void
    {
        $category = $this->product->category;
        $this->actingAs($this->admin)->getJson(route('categories.index'))->assertOk()
            ->assertJsonPath('categories.0.id', $category->ID)->assertJsonPath('categories.0.products_count', 1);
        $this->putJson(route('categories.update', $category), ['Name' => '  Interior  '])->assertOk()->assertJsonPath('category.name', 'Interior');
        $this->putJson(route('categories.update', $category), ['Name' => 'Interior'])->assertOk();
        $other = Category::create(['Name' => 'Other']);
        $this->putJson(route('categories.update', $category), ['Name' => 'Other'])->assertUnprocessable();
        $this->putJson(route('categories.update', $category), ['Name' => '  '])->assertUnprocessable();
        $this->postJson(route('categories.archive', $category))->assertOk()->assertJsonPath('category.is_archived', true);
        $this->assertSame((int) $category->ID, (int) $this->product->fresh()->Category_ID);
        $this->assertSame(1, Product::count());
        $this->assertSame(1, StockIn::count());
        $this->get(route('products.index', ['category_id' => $category->ID]))->assertOk()->assertSee('Interior (Archived)')->assertSee('Test floor mat');
        $this->get(route('stock-in.create'))->assertOk()->assertViewHas('categories', fn ($categories) => !$categories->contains('ID', $category->ID));
        $this->get(route('products.edit', $this->product))->assertOk()->assertViewHas('categories', fn ($categories) => $categories->contains('ID', $category->ID));
        $this->postJson(route('stock-in.store'), ['product_mode' => 'new', 'New_Product_Name' => 'New item', 'New_Category_ID' => $category->ID,
            'Quantity' => 1, 'Cost_Price' => 1, 'Retail_Price' => 2])->assertUnprocessable()->assertJsonValidationErrors('New_Category_ID');
        $this->putJson(route('products.update', $this->product), ['Name' => 'Edited existing item', 'Category_ID' => $category->ID, 'Status_ID' => $this->product->Status_ID])->assertRedirect();
        $this->postJson(route('categories.archive', $other))->assertOk();
        $this->putJson(route('products.update', $this->product), ['Name' => 'Edited existing item', 'Category_ID' => $other->ID, 'Status_ID' => $this->product->Status_ID])
            ->assertUnprocessable()->assertJsonValidationErrors('Category_ID');
        $this->postJson(route('categories.restore', $category))->assertOk()->assertJsonPath('category.is_archived', false);
        $this->get(route('stock-in.create'))->assertOk()->assertViewHas('categories', fn ($categories) => $categories->contains('ID', $category->ID));
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Sales use the selected dates. Inventory shows current stock.');
    }

    public function test_only_admins_can_list_edit_archive_or_restore_categories(): void
    {
        $category = $this->product->category;
        $this->actingAs(User::factory()->create(['role' => 'Cashier', 'is_active' => true]));
        $this->getJson(route('categories.index'))->assertForbidden();
        $this->putJson(route('categories.update', $category), ['Name' => 'Renamed'])->assertForbidden();
        $this->postJson(route('categories.archive', $category))->assertForbidden();
        $this->postJson(route('categories.restore', $category))->assertForbidden();
        $this->assertSame('Accessories', $category->fresh()->Name);
        $this->assertFalse($category->fresh()->Is_Archived);
    }

    public function test_backups_before_category_archiving_remain_restorable(): void
    {
        $service = app(SqlServerSnapshotService::class);
        $snapshot = $service->snapshot(DB::connection());
        foreach ($snapshot['tables'] as $table => &$data) {
            if (strtolower($table) === 'tbl_category') {
                $data['columns'] = array_values(array_diff($data['columns'], ['Is_Archived']));
                foreach ($data['rows'] as &$row) unset($row['Is_Archived']);
                unset($row);
            }
            if (strtolower($table) === 'migrations') {
                $data['rows'] = array_values(array_filter($data['rows'], fn ($row) => $row['migration'] !== '2026_10_10_000001_add_category_archiving'));
            }
        }
        unset($data);
        $this->product->category->update(['Is_Archived' => true]);
        $service->restore(DB::connection(), $snapshot);
        $this->assertFalse($this->product->fresh()->category->Is_Archived);
        $this->assertSame(1, Product::count());
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'sqlsrv', '--force' => true]));
    }

    public function test_recovery_code_print_flows_have_guarded_pdf_downloads(): void
    {
        $this->postJson(route('recovery-codes.download'), ['codes' => ['ABCD-EFGH-IJKL']])->assertForbidden();
        $this->withSession(['new_admin_recovery_codes' => ['ABCD-EFGH-IJKL']])->get(route('password.recovery-codes'))->assertOk()->assertSee('Print / Download Codes');
        $response = $this->post(route('recovery-codes.download'), ['codes' => ['ABCD-EFGH-IJKL']]);
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->withSession(['recovery_codes_export_until' => 0])->actingAs(User::factory()->create(['role' => 'Cashier', 'is_active' => true]))
            ->postJson(route('recovery-codes.download'),['codes' => ['ABCD-EFGH-IJKL']])->assertForbidden();
    }
}
