<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use App\Services\SqlServerSnapshotService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class SqlServerDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $testDatabase = getenv('SQLSERVER_TEST_DATABASE');
        if (!$testDatabase || !extension_loaded('pdo_sqlsrv')) {
            $this->markTestSkipped('Set SQLSERVER_TEST_DATABASE and use scripts/php.ps1 to run SQL Server integration tests.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+_sqlserver_test$/', $testDatabase)) {
            throw new InvalidArgumentException('SQL Server tests require a dedicated database ending in _sqlserver_test.');
        }

        config([
            'database.default' => 'sqlsrv',
            'database.connections.sqlsrv.host' => '.\\SQLEXPRESS',
            'database.connections.sqlsrv.port' => '',
            'database.connections.sqlsrv.database' => $testDatabase,
            'database.connections.sqlsrv.url' => null,
            'database.connections.sqlsrv.username' => null,
            'database.connections.sqlsrv.password' => null,
            'database.connections.sqlsrv.encrypt' => 'yes',
            'database.connections.sqlsrv.trust_server_certificate' => 'yes',
        ]);
        DB::purge('sqlsrv');
        Artisan::call('migrate:fresh', ['--database' => 'sqlsrv', '--force' => true]);
    }

    protected function inventory(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = Category::create(['Name' => 'Test category']);
        $status = Status::create(['Name' => 'Active']);
        $product = Product::create(['Name' => 'Test product', 'Category_ID' => $category->ID, 'Status_ID' => $status->ID]);
        StockIn::create(['Product_ID' => $product->ID, 'User_ID' => $user->id,
            'Quantity' => 3, 'Cost_Price' => 5, 'Retail_Price' => 10]);
        return [$user, $product];
    }

    public function test_new_shipment_saves_product_photo_and_authenticated_processor(): void
    {
        [$user, $existingProduct] = $this->inventory();
        $otherUser = User::factory()->create(['is_active' => true]);
        $testPublicPath = sys_get_temp_dir().'/paolo-sqlserver-shipment-'.Str::uuid();
        File::ensureDirectoryExists($testPublicPath);
        $this->app->usePublicPath($testPublicPath);
        $uploadPath = $testPublicPath.'/photo.png';
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aM1sAAAAASUVORK5CYII=');
        File::put($uploadPath, $contents);

        try {
            $this->actingAs($user)->post('/stock-in', [
                'product_mode' => 'new', 'New_Product_Name' => 'Shipment with photo',
                'New_Category_ID' => $existingProduct->Category_ID,
                'User_ID' => $otherUser->id,
                'images' => [new UploadedFile($uploadPath, 'photo.png', 'image/png', null, true)],
                'Quantity' => 4, 'Cost_Price' => 5, 'Retail_Price' => 10,
            ])->assertRedirect(route('stock-in.index'))->assertSessionHasNoErrors();

            $product = Product::where('Name', 'Shipment with photo')->sole();
            $this->assertCount(1, $product->Images);
            $this->assertSame($product->Images[0], $product->Image);
            $this->assertSame($contents, file_get_contents(public_path($product->Image)));
            $this->assertDatabaseHas('tbl_stock_in', [
                'Product_ID' => $product->ID, 'User_ID' => $user->id,
                'Quantity' => 4, 'Remaining_Quantity' => 4,
            ]);
        } finally {
            File::delete($uploadPath);
            if (is_dir($testPublicPath.'/uploads/products')) {
                foreach (File::files($testPublicPath.'/uploads/products') as $file) {
                    File::delete($file->getPathname());
                }
            }
            foreach (['/uploads/products', '/uploads', ''] as $directory) {
                if (is_dir($testPublicPath.$directory)) {
                    rmdir($testPublicPath.$directory);
                }
            }
        }
    }

    public function test_inventory_filters_exclude_expired_and_damaged_stock(): void
    {
        [$user, $product] = $this->inventory();
        StockIn::create(['Product_ID' => $product->ID, 'User_ID' => $user->id, 'Quantity' => 50,
            'Cost_Price' => 5, 'Retail_Price' => 10, 'Has_Expiration' => true,
            'Expiration_Date' => today()->subDay()]);
        StockIn::create(['Product_ID' => $product->ID, 'User_ID' => $user->id, 'Quantity' => 50,
            'Cost_Price' => 5, 'Retail_Price' => 10, 'Condition' => 'Damaged']);

        $this->actingAs($user)->get('/products?stock_level=low')->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('ID')->all() === [$product->ID]);
        $this->get('/products?stock_level=available')->assertOk()
            ->assertViewHas('products', fn ($products) => $products->isEmpty());
        $this->assertEquals(3, $product->stock_quantity);
    }

    public function test_checkout_uses_sql_server_inventory_and_preserves_cashier(): void
    {
        [$user, $product] = $this->inventory();
        $payment = PaymentMethod::create(['Name' => 'Cash']);
        $this->actingAs($user)->postJson('/pos/checkout', [
            'payment_method_id' => $payment->ID, 'amount_tendered' => 50,
            'items' => [['product_id' => $product->ID, 'quantity' => 2]],
        ])->assertOk()->assertJson(['success' => true, 'total' => 20, 'cashier' => $user->name]);
        $this->assertEquals(1, $product->stock_quantity);
        $this->assertDatabaseHas('tbl_sale', ['User_ID' => $user->id, 'Total' => 20]);
    }

    public function test_snapshot_restores_records_and_identity_values(): void
    {
        [$user, $product] = $this->inventory();
        $service = app(SqlServerSnapshotService::class);
        $snapshot = $service->snapshot(DB::connection());
        $product->update(['Name' => 'Changed after backup']);
        $service->restore(DB::connection(), $snapshot);
        $this->assertDatabaseHas('tbl_product', ['ID' => $product->ID, 'Name' => 'Test product']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'password' => $user->password]);
        $nextProduct = Product::create(['Name' => 'Next product', 'Category_ID' => $product->Category_ID, 'Status_ID' => $product->Status_ID]);
        $this->assertGreaterThan($product->ID, $nextProduct->ID);
    }

    public function test_invalid_foreign_key_rolls_back_entire_restore(): void
    {
        [$user, $product] = $this->inventory();
        $service = app(SqlServerSnapshotService::class);
        $before = $service->snapshot(DB::connection());
        $invalid = $before;
        $table = collect(array_keys($invalid['tables']))->first(fn ($name) => strtolower($name) === 'tbl_product');
        $invalid['tables'][$table]['rows'][0]['Category_ID'] = 999999;
        try {
            $service->restore(DB::connection(), $invalid);
            $this->fail('Invalid references must reject the restore.');
        } catch (QueryException) {
            $this->assertSame($before['tables'], $service->snapshot(DB::connection())['tables']);
            $disabled = DB::selectOne('SELECT COUNT(*) AS total FROM sys.foreign_keys WHERE is_disabled = 1');
            $this->assertEquals(0, $disabled->total);
        }
    }

    public function test_incomplete_snapshot_preserves_existing_records(): void
    {
        [$user, $product] = $this->inventory();
        $service = app(SqlServerSnapshotService::class);
        $snapshot = $service->snapshot(DB::connection());
        unset($snapshot['tables']['users']);
        try {
            $service->restore(DB::connection(), $snapshot);
            $this->fail('Incomplete snapshots must be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
            $this->assertDatabaseHas('tbl_product', ['ID' => $product->ID]);
        }
    }
}
