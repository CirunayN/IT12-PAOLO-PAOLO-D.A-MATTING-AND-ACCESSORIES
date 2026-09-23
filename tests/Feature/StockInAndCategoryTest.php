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

    public function test_stock_in_records_selected_processed_by_user(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
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
            'User_ID' => $installer->id,
            'Quantity' => 40,
            'Cost_Price' => 150.00,
            'Retail_Price' => 250.00,
        ]);

        $stockIn = StockIn::latest('ID')->first();
        $this->assertEquals($installer->id, $stockIn->user->id);
        $this->assertEquals('Pedro Installer', $stockIn->user->name);
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
}
