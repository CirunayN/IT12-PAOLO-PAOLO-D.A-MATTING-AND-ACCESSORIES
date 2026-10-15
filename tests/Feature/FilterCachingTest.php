<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use App\Services\FilterCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class FilterCachingTest extends TestCase
{
    use RefreshDatabase;
    private string $root;
    private Product $product;
    private StockIn $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/paolo-filters-'.Str::uuid();
        $this->app->useStoragePath($this->root);
        File::ensureDirectoryExists($this->root.'/framework/views');
        foreach (['Active', 'Archived', 'Low Stock', 'Out of Stock'] as $name) Status::create(['Name' => $name]);
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);
        $this->product = Product::create(['Name' => 'Engine Oil', 'Category_ID' => Category::create(['Name' => 'Fluids'])->ID, 'Status_ID' => Status::where('Name', 'Active')->value('ID')]);
        $this->stock = StockIn::create(['Product_ID' => $this->product->ID, 'User_ID' => $admin->id, 'Quantity' => 10, 'Remaining_Quantity' => 10, 'Cost_Price' => 100, 'Retail_Price' => 150, 'Condition' => 'Good', 'Has_Expiration' => false]);
    }

    protected function tearDown(): void
    {
        $resolved = isset($this->root) ? realpath($this->root) : false;
        if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'paolo-filters-')) File::deleteDirectory($resolved);
        parent::tearDown();
    }

    public function test_repeated_inventory_filters_reuse_results_without_business_queries(): void
    {
        $this->get('/products?sort=name&direction=asc')->assertOk()->assertSee('Engine Oil');
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->get('/products?direction=asc&sort=name')->assertOk()->assertSee('Engine Oil');
        $business = array_filter(DB::getQueryLog(), fn ($query) => preg_match('/tbl_(product|stock_in|sold_item|category|status)/i', $query['query']));
        $this->assertCount(0, $business, json_encode(array_values($business)));
        DB::disableQueryLog();
    }

    public function test_saved_stock_and_product_edits_invalidate_cached_filters(): void
    {
        $this->get('/products?min_stock=5')->assertOk()->assertSee('Engine Oil');
        $this->stock->update(['Remaining_Quantity' => 1]);
        $this->get('/products?min_stock=5')->assertOk()->assertDontSee('Engine Oil');
        $this->product->update(['Name' => 'Updated Oil']);
        $this->get('/products?search=Updated')->assertOk()->assertSee('Updated Oil');
    }

    public function test_other_read_modules_reuse_cached_results(): void
    {
        foreach (['/stock-in', '/dashboard', '/reports', '/my-transactions'] as $url) {
            $this->get($url)->assertOk();
            DB::enableQueryLog(); DB::flushQueryLog();
            $this->get($url)->assertOk();
            $business = array_filter(DB::getQueryLog(), fn ($query) => preg_match('/tbl_(product|stock_in|sold_item|category|status|sale|payment)/i', $query['query']));
            $this->assertCount(0, $business, $url.': '.json_encode(array_values($business)));
            DB::disableQueryLog();
        }
    }

    public function test_loaded_batch_totals_and_prices_match_live_calculations(): void
    {
        foreach ([['Good', -1, 20, 500], ['Damaged', 1, 10, 600], ['Good', 0, 3, 700]] as [$condition, $days, $quantity, $price]) {
            StockIn::create(['Product_ID'=>$this->product->ID, 'User_ID'=>auth()->id(), 'Quantity'=>$quantity,
                'Remaining_Quantity'=>$quantity, 'Cost_Price'=>50, 'Retail_Price'=>$price, 'Condition'=>$condition,
                'Has_Expiration'=>true, 'Expiration_Date'=>today()->addDays($days)]);
        }
        $live = $this->product->fresh(); $loaded = $this->product->fresh('stockIns');
        $this->assertSame(13.0, $loaded->stock_quantity);
        $this->assertSame(700.0, $loaded->retail_price);
        foreach (['stock_quantity','retail_price','cost_price'] as $field) $this->assertSame($live->$field, $loaded->$field);
    }

    public function test_cache_expires_and_does_not_mix_users(): void
    {
        $cache = app(FilterCache::class); $calls = 0;
        $load = function () use (&$calls) { return ++$calls; };
        $this->assertSame(1, $cache->remember('test', ['sort' => 'name'], $load));
        $this->assertSame(1, $cache->remember('test', ['sort' => 'name'], $load));
        $this->travel(31)->seconds();
        $this->assertSame(2, $cache->remember('test', ['sort' => 'name'], $load));
        $other = User::factory()->create(['role' => 'Cashier']);
        $this->actingAs($other);
        $this->assertSame(3, $cache->remember('test', ['sort' => 'name'], $load));
        $this->get('/products?sort=name')->assertForbidden();
    }

    public function test_rollback_does_not_publish_uncommitted_results_and_commit_invalidates_them(): void
    {
        $cache = app(FilterCache::class);
        $revision = $cache->revision();
        DB::beginTransaction();
        $this->product->update(['Name' => 'Rolled back']);
        $this->assertSame($revision, $cache->revision());
        DB::rollBack();
        $this->assertSame($revision, $cache->revision());
        DB::transaction(fn () => $this->product->fresh()->update(['Name' => 'Committed']));
        $this->assertNotSame($revision, $cache->revision());
    }

    public function test_invalid_filters_are_rejected_and_exports_read_current_records(): void
    {
        $this->get('/products?sort=name')->assertOk();
        $this->getJson('/products?min_stock=-1')->assertUnprocessable();
        // Direct database changes bypass observers; printed reports must still read live data.
        DB::table('tbl_product')->where('ID', $this->product->ID)->update(['Name' => 'Live Export']);
        $this->get('/products/print?sort=name')->assertOk()->assertSee('Live Export');
    }
}
