<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Status $status;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $this->category = Category::create(['Name' => 'Parts']);
        $this->status = Status::create(['Name' => 'Active']);
        $this->actingAs($this->admin);
    }

    private function item(string $name, float $stock, float $price, string $date, int $threshold = 5): Product
    {
        $product = Product::create(['Name' => $name, 'Category_ID' => $this->category->ID,
            'Status_ID' => $this->status->ID, 'Low_Stock_Threshold' => $threshold]);
        $product->forceFill(['created_at' => $date.' 12:00:00'])->save();
        $batch = StockIn::create(['Product_ID' => $product->ID, 'User_ID' => $this->admin->id,
            'Quantity' => max(1, $stock), 'Remaining_Quantity' => $stock, 'Retail_Price' => $price,
            'Cost_Price' => 10, 'Condition' => 'Good', 'Has_Expiration' => false]);
        $batch->forceFill(['created_at' => $date.' 12:00:00'])->save();

        return $product;
    }

    private function catalog(): array
    {
        return [$this->item('Alpha oil', 9, 500, '2026-10-01'),
            $this->item('Bravo fluid', 2, 100, '2026-10-03'),
            $this->item('Charlie filter', 0, 300, '2026-10-02')];
    }

    public static function orders(): array
    {
        return [
            ['name', 'asc', [0, 1, 2]], ['name', 'desc', [2, 1, 0]],
            ['date', 'asc', [0, 2, 1]], ['date', 'desc', [1, 2, 0]],
            ['price', 'asc', [1, 2, 0]], ['price', 'desc', [0, 2, 1]],
            ['stock', 'asc', [2, 1, 0]], ['stock', 'desc', [0, 1, 2]],
        ];
    }

    #[DataProvider('orders')]
    public function test_inventory_and_printed_inventory_use_the_same_order(string $sort, string $direction, array $order): void
    {
        $products = $this->catalog();
        $expected = array_map(fn ($index) => $products[$index]->ID, $order);
        foreach (['products.index', 'products.print'] as $route) {
            $this->get(route($route, compact('sort', 'direction')))->assertOk()
                ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === $expected);
        }
        foreach (['stock-in.index', 'stock-in.print'] as $route) {
            $this->get(route($route, compact('sort', 'direction')))->assertOk()
                ->assertViewHas('stockIns', fn ($items) => $items->pluck('Product_ID')->all() === $expected);
        }
    }

    public function test_combined_date_price_stock_and_name_filters_and_one_sided_dates(): void
    {
        [$a, $b, $c] = $this->catalog();
        $filters = ['start_date' => '2026-10-02', 'end_date' => '2026-10-03',
            'min_price' => 100, 'max_price' => 300, 'min_stock' => 0, 'max_stock' => 2, 'sort' => 'price', 'direction' => 'desc'];
        foreach (['products.index', 'products.print'] as $route) {
            $this->get(route($route, $filters))->assertOk()
                ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$c->ID, $b->ID]);
        }
        foreach (['stock-in.index', 'stock-in.print'] as $route) {
            $this->get(route($route, $filters))->assertOk()
                ->assertViewHas('stockIns', fn ($items) => $items->pluck('Product_ID')->all() === [$c->ID, $b->ID]);
        }
        $this->get(route('products.index', $filters + ['search' => 'Bravo']))->assertOk()
            ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$b->ID]);
        $this->get(route('products.index', ['end_date' => '2026-10-01']))->assertOk()
            ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$a->ID]);
    }

    public function test_price_order_matches_visible_price_for_unsellable_batches_and_no_stock(): void
    {
        [$a, $b, $c] = $this->catalog();
        StockIn::create(['Product_ID' => $a->ID, 'User_ID' => $this->admin->id,
            'Quantity' => 2, 'Remaining_Quantity' => 2, 'Retail_Price' => 1, 'Cost_Price' => 1, 'Condition' => 'Damaged']);
        $empty = Product::create(['Name' => 'Empty item', 'Category_ID' => $this->category->ID, 'Status_ID' => $this->status->ID]);
        $this->get(route('products.index', ['sort' => 'price', 'direction' => 'asc']))->assertOk()
            ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$empty->ID, $b->ID, $c->ID, $a->ID]);
        $this->assertSame(500.0, $a->retail_price);
        $this->assertSame(300.0, $c->retail_price);
    }

    public function test_item_threshold_controls_inventory_dashboard_and_reports_and_can_be_edited(): void
    {
        $low = $this->item('Low item', 8, 100, '2026-10-01', 10);
        $available = $this->item('Available item', 8, 100, '2026-10-01', 2);
        $this->get(route('products.index', ['stock_level' => 'low']))->assertOk()
            ->assertDontSee('name="Low_Stock_Threshold"', false)
            ->assertSee(route('products.edit', $low))
            ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$low->ID]);
        $this->get(route('stock-in.index', ['stock_level' => 'low']))->assertOk()
            ->assertViewHas('stockIns', fn ($items) => $items->pluck('Product_ID')->all() === [$low->ID]);
        $this->get(route('dashboard'))->assertOk()->assertViewIs('dashboard')->assertViewHas('lowStockCount', 1)
            ->assertViewHas('stockAlerts', fn ($items) => $items->pluck('ID')->all() === [$low->ID]);
        $this->get(route('reports.index', ['scope' => 'inventory', 'stock_status' => 'low']))->assertOk()
            ->assertViewIs('reports.index')->assertViewHas('lowStockCount', 1)
            ->assertViewHas('products', fn ($items) => $items->pluck('ID')->all() === [$low->ID]);
        $this->get(route('products.edit', $low))->assertOk()
            ->assertSee('name="Low_Stock_Threshold"', false);
        $this->put(route('products.update', $low), ['Name' => $low->Name,
            'Category_ID' => $low->Category_ID, 'Status_ID' => $low->Status_ID,
            'Low_Stock_Threshold' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $low->fresh()->low_stock_threshold);
        $this->assertSame(2, $available->fresh()->low_stock_threshold);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('lowStockCount', 0);
    }

    public function test_threshold_edits_require_an_admin_and_whole_nonnegative_counts(): void
    {
        $product = $this->item('Threshold test', 8, 100, '2026-10-01');
        $payload = ['Name' => $product->Name, 'Category_ID' => $product->Category_ID, 'Status_ID' => $product->Status_ID];
        foreach ([-1, 1.5, 'abc', null, 2147483648] as $value) {
            $this->putJson(route('products.update', $product), $payload + ['Low_Stock_Threshold' => $value])
                ->assertUnprocessable()->assertJsonValidationErrors('Low_Stock_Threshold');
        }
        $this->assertSame(5, $product->fresh()->low_stock_threshold);
        $employee = User::factory()->create(['role' => 'Employee', 'is_active' => true]);
        $this->actingAs($employee)->putJson(route('products.update', $product), $payload + ['Low_Stock_Threshold' => 10])->assertForbidden();
    }

    public function test_dashboard_legacy_report_link_redirects_to_the_separate_report_module(): void
    {
        $this->get(route('dashboard', ['tab' => 'reports', 'scope' => 'inventory']))
            ->assertRedirect(route('reports.index', ['scope' => 'inventory']));
    }

    public function test_new_product_receiving_and_product_edit_save_the_item_threshold(): void
    {
        $this->post(route('stock-in.store'), ['product_mode' => 'new', 'New_Product_Name' => 'New oil',
            'New_Category_ID' => $this->category->ID, 'Low_Stock_Threshold' => 12,
            'Quantity' => 20, 'Cost_Price' => 50, 'Retail_Price' => 100])->assertSessionHasNoErrors();
        $product = Product::where('Name', 'New oil')->firstOrFail();
        $this->assertSame(12, $product->low_stock_threshold);
        $this->put(route('products.update', $product), ['Name' => $product->Name,
            'Category_ID' => $product->Category_ID, 'Status_ID' => $product->Status_ID,
            'Low_Stock_Threshold' => 3])->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->low_stock_threshold);
        $this->post(route('stock-in.store'), ['product_mode' => 'existing', 'Product_ID' => $product->ID,
            'Low_Stock_Threshold' => 99, 'Quantity' => 10, 'Cost_Price' => 50, 'Retail_Price' => 100])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->low_stock_threshold);
    }

    public static function invalidFilters(): array
    {
        return [[['direction' => 'sideways']], [['sort' => 'unknown']],
            [['start_date' => '2026-10-03', 'end_date' => '2026-10-01']],
            [['min_price' => 10, 'max_price' => 1]], [['min_stock' => 10, 'max_stock' => 1]], [['min_price' => -1]]];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected_by_inventory_and_receiving_logs(array $filters): void
    {
        foreach (['products.index', 'stock-in.index'] as $route) {
            $this->getJson(route($route, $filters))->assertUnprocessable();
        }
    }

    public static function invalidReferences(): array
    {
        return [[''], ['123456789012'], ['12345678901234'], ['123456789012A'], ['123456 7890123'], ['1234-567890123']];
    }

    #[DataProvider('invalidReferences')]
    public function test_invalid_gcash_references_cannot_record_sales_or_deduct_stock(string $reference): void
    {
        $item = $this->item('GCash item', 10, 100, '2026-10-01');
        $payment = PaymentMethod::create(['Name' => 'GCash']);
        $this->postJson(route('pos.checkout'), ['payment_method_id' => $payment->ID,
            'gcash_reference' => $reference, 'amount_tendered' => 100,
            'items' => [['product_id' => $item->ID, 'quantity' => 1]]])->assertUnprocessable()
            ->assertJsonValidationErrors('gcash_reference');
        $this->assertDatabaseCount('tbl_sale', 0);
        $this->assertSame(10.0, $item->stock_quantity);
    }

    public function test_valid_gcash_reference_preserves_leading_zeroes_and_records_sale(): void
    {
        $item = $this->item('GCash item', 10, 100, '2026-10-01');
        $payment = PaymentMethod::create(['Name' => 'GCash']);
        $this->postJson(route('pos.checkout'), ['payment_method_id' => $payment->ID,
            'gcash_reference' => '0123456789012', 'amount_tendered' => 100,
            'items' => [['product_id' => $item->ID, 'quantity' => 1]]])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('0123456789012', Sale::first()->GCash_Reference_Number);
        $this->assertSame(9.0, $item->stock_quantity);
    }
}
