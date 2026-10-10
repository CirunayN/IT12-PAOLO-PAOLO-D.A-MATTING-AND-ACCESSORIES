<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_new_category_via_ajax(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('categories.store'), [
                'Name' => 'Kei Truck Performance Parts',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'category' => [
                'name' => 'Kei Truck Performance Parts',
            ],
        ]);

        $this->assertDatabaseHas('tbl_category', [
            'Name' => 'Kei Truck Performance Parts',
        ]);
    }

    public function test_category_validation_prevents_duplicates(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        Category::create(['Name' => 'Suspension Kits']);

        $response = $this->actingAs($admin)
            ->postJson(route('categories.store'), [
                'Name' => 'Suspension Kits',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['Name']);
    }

    public function test_stock_in_records_signed_in_user_even_when_another_processor_is_submitted(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $installer = User::factory()->create([
            'username' => 'installer',
            'name' => 'Pedro Installer',
            'role' => 'Accessory Installer',
        ]);

        $cat = Category::create(['Name' => 'Fluids']);
        $status = Status::create(['Name' => 'Active']);

        $product = Product::create([
            'Name' => 'Brake Fluid DOT 4 500ml',
            'Category_ID' => $cat->ID,
            'Status_ID' => $status->ID,
        ]);

        $response = $this->actingAs($admin)->post(route('stock-in.store'), [
            'product_mode' => 'existing',
            'Product_ID' => $product->ID,
            'User_ID' => $installer->id,
            'Quantity' => 40,
            'Cost_Price' => 150.00,
            'Retail_Price' => 250.00,
        ]);

        $response->assertRedirect(route('stock-in.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tbl_stock_in', [
            'Product_ID' => $product->ID,
            'User_ID' => $admin->id,
            'Quantity' => 40,
            'Cost_Price' => 150.00,
            'Retail_Price' => 250.00,
        ]);

        $stockIn = StockIn::latest('ID')->first();
        $this->assertEquals($admin->id, $stockIn->user->id);
    }

    public function test_pos_checkout_records_selected_cashier_user(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        $cashier = User::factory()->create([
            'username' => 'cashier',
            'name' => 'Maria Cashier',
            'role' => 'Cashier',
        ]);

        $cat = Category::create(['Name' => 'Fluids']);
        $status = Status::create(['Name' => 'Active']);
        $pm = PaymentMethod::create(['Name' => 'Cash']);

        $product = Product::create([
            'Name' => 'Engine Coolant 1L',
            'Category_ID' => $cat->ID,
            'Status_ID' => $status->ID,
        ]);

        StockIn::create([
            'Product_ID' => $product->ID,
            'User_ID' => $admin->id,
            'Quantity' => 20,
            'Cost_Price' => 100.00,
            'Retail_Price' => 200.00,
        ]);

        $response = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'payment_method_id' => $pm->ID,
            'user_id' => $cashier->id,
            'amount_tendered' => 500.00,
            'items' => [
                [
                    'product_id' => $product->ID,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'cashier' => 'Maria Cashier',
            'cashier_role' => 'Cashier',
            'total' => 400.00,
        ]);

        $this->assertDatabaseHas('tbl_sale', [
            'User_ID' => $cashier->id,
            'Total' => 400.00,
            'Payment_Method_ID' => $pm->ID,
        ]);
    }

    public function test_stock_in_index_renders_without_received_by_column_or_staff_filter_and_has_manage_categories(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_test',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $cat = Category::create(['Name' => 'Mats']);
        $status = Status::create(['Name' => 'Active']);
        $product = Product::create([
            'Name' => 'Heavy Rubber Mat',
            'Category_ID' => $cat->ID,
            'Status_ID' => $status->ID,
        ]);

        StockIn::create([
            'Product_ID' => $product->ID,
            'User_ID' => $admin->id,
            'Quantity' => 10,
            'Remaining_Quantity' => 10,
            'Cost_Price' => 120.00,
            'Retail_Price' => 220.00,
        ]);

        $response = $this->actingAs($admin)->get(route('stock-in.index'));

        $response->assertOk();
        $response->assertDontSee('Received By');
        $response->assertDontSee('Processed By:');
        $response->assertDontSee('siModalUser');
        $response->assertDontSee('All Receiving Staff');
        $response->assertSee('Manage Categories');
        $response->assertSee('openCategoryManager()', false);
    }

    public function test_stock_in_index_renders_same_filter_and_search_controls_as_active_catalog(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_filter_ui',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $cat = Category::create(['Name' => 'Interior']);

        $response = $this->actingAs($admin)->get(route('stock-in.index'));

        $response->assertOk();
        $response->assertSee('name="search"', false);
        $response->assertSee('Search product name or description...', false);
        $response->assertSee('name="category_id"', false);
        $response->assertSee('All Categories');
        $response->assertSee('Interior');
        $response->assertSee('name="stock_level"', false);
        $response->assertSee('All Stock Levels');
        $response->assertSee('Available (6+ units)');
        $response->assertSee('Low / Out of Stock');
        $response->assertSee('Low Stock (1-5 units)');
        $response->assertSee('Out of Stock (0)');
        $response->assertSee('name="sort"', false);
        $response->assertSee('Stock: Low to High (0 - 100+)');
        $response->assertSee('Stock: High to Low');
        $response->assertSee('Newest first');
        $response->assertSee('Oldest first');
        $response->assertSee('Name: A to Z');
        $response->assertSee('Name: Z to A');
        $response->assertSee('Price: Low to High');
        $response->assertSee('Price: High to Low');
        $response->assertSee(route('stock-in.index'));
        $response->assertSee('Reset');
    }

    public function test_stock_in_filters_by_search_category_stock_level_and_sort(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_filtering',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $cat1 = Category::create(['Name' => 'Accessories']);
        $cat2 = Category::create(['Name' => 'Lighting']);
        $status = Status::create(['Name' => 'Active']);

        $p1 = Product::create(['Name' => 'Alpha LED Headlight', 'Category_ID' => $cat2->ID, 'Status_ID' => $status->ID]);
        $p2 = Product::create(['Name' => 'Beta Rubber Mat', 'Category_ID' => $cat1->ID, 'Status_ID' => $status->ID]);
        $p3 = Product::create(['Name' => 'Gamma Floor Mat', 'Category_ID' => $cat1->ID, 'Status_ID' => $status->ID]);

        $si1 = StockIn::create([
            'Product_ID' => $p1->ID,
            'User_ID' => $admin->id,
            'Quantity' => 10,
            'Remaining_Quantity' => 10,
            'Cost_Price' => 100,
            'Retail_Price' => 300,
        ]);

        $si2 = StockIn::create([
            'Product_ID' => $p2->ID,
            'User_ID' => $admin->id,
            'Quantity' => 5,
            'Remaining_Quantity' => 2,
            'Cost_Price' => 50,
            'Retail_Price' => 150,
        ]);

        $si3 = StockIn::create([
            'Product_ID' => $p3->ID,
            'User_ID' => $admin->id,
            'Quantity' => 8,
            'Remaining_Quantity' => 0,
            'Cost_Price' => 80,
            'Retail_Price' => 200,
        ]);

        // Filter by search
        $resSearch = $this->actingAs($admin)->get(route('stock-in.index', ['search' => 'Headlight']));
        $resSearch->assertOk();
        $resSearch->assertSee('Alpha LED Headlight');
        $resSearch->assertDontSee('Beta Rubber Mat');

        // Filter by category
        $resCat = $this->actingAs($admin)->get(route('stock-in.index', ['category_id' => $cat1->ID]));
        $resCat->assertOk();
        $resCat->assertSee('Beta Rubber Mat');
        $resCat->assertSee('Gamma Floor Mat');
        $resCat->assertDontSee('Alpha LED Headlight');

        // Filter by stock level: out
        $resOut = $this->actingAs($admin)->get(route('stock-in.index', ['stock_level' => 'out']));
        $resOut->assertOk();
        $resOut->assertSee('Gamma Floor Mat');
        $resOut->assertDontSee('Alpha LED Headlight');
        $resOut->assertDontSee('Beta Rubber Mat');

        // Filter by stock level: low
        $resLow = $this->actingAs($admin)->get(route('stock-in.index', ['stock_level' => 'low']));
        $resLow->assertOk();
        $resLow->assertSee('Beta Rubber Mat');
        $resLow->assertDontSee('Alpha LED Headlight');
        $resLow->assertDontSee('Gamma Floor Mat');

        // Filter by stock level: available
        $resAvail = $this->actingAs($admin)->get(route('stock-in.index', ['stock_level' => 'available']));
        $resAvail->assertOk();
        $resAvail->assertSee('Alpha LED Headlight');
        $resAvail->assertDontSee('Beta Rubber Mat');
        $resAvail->assertDontSee('Gamma Floor Mat');

        // Sort by name A to Z
        $resNameAsc = $this->actingAs($admin)->get(route('stock-in.index', ['sort' => 'name_asc']));
        $resNameAsc->assertOk();
        $items = $resNameAsc->viewData('stockIns')->items();
        $this->assertEquals($p1->ID, $items[0]->Product_ID);
        $this->assertEquals($p2->ID, $items[1]->Product_ID);
        $this->assertEquals($p3->ID, $items[2]->Product_ID);

        // Sort by price: high to low
        $resPriceDesc = $this->actingAs($admin)->get(route('stock-in.index', ['sort' => 'price_desc']));
        $resPriceDesc->assertOk();
        $itemsPrice = $resPriceDesc->viewData('stockIns')->items();
        $this->assertEquals(300, (float)$itemsPrice[0]->Retail_Price);
        $this->assertEquals(200, (float)$itemsPrice[1]->Retail_Price);
        $this->assertEquals(150, (float)$itemsPrice[2]->Retail_Price);
    }
}
