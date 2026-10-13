<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function sale(string $payment = 'Cash'): Sale
    {
        $cashier = User::factory()->create(['role' => 'Cashier', 'is_active' => true]);
        $category = Category::create(['Name' => 'Matting']);
        $status = Status::create(['Name' => 'Active']);
        $product = Product::create(['Name' => 'Suzuki DA64V / DA64W Transformer Heavy-Duty Coil Carpet Overmats',
            'Category_ID' => $category->ID, 'Status_ID' => $status->ID]);
        StockIn::create(['Product_ID' => $product->ID, 'Quantity' => 10, 'Remaining_Quantity' => 10,
            'Cost_Price' => 50, 'Retail_Price' => 999, 'User_ID' => $cashier->id]);
        $method = PaymentMethod::create(['Name' => $payment]);
        $sale = Sale::create(['Date' => now(), 'User_ID' => $cashier->id, 'Payment_Method_ID' => $method->ID,
            'Total' => 150, 'Amount_Received' => $payment === 'Cash' ? 200 : 150,
            'Change_Amount' => $payment === 'Cash' ? 50 : 0,
            'GCash_Reference_Number' => $payment === 'GCash' ? '0123456789012' : null]);
        SoldItem::create(['Product_ID' => $product->ID, 'Sale_ID' => $sale->ID, 'Quantity' => 2, 'Total' => 150]);

        return $sale;
    }

    public function test_cash_receipt_uses_recorded_sale_prices_and_payment_totals(): void
    {
        $sale = $this->sale();
        $this->actingAs($sale->user)->get(route('pos.receipt', $sale->ID))->assertOk()
            ->assertSee('2x @ ₱75.00')->assertDontSee('₱999.00')
            ->assertSee('₱150.00')->assertSee('₱200.00')->assertSee('₱50.00')
            ->assertSee($sale->soldItems->first()->product->Name)->assertDontSee('<img', false);
    }

    public function test_gcash_receipt_preserves_the_reference_without_cash_change_fields(): void
    {
        $sale = $this->sale('GCash');
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('pos.receipt', $sale->ID))->assertOk()
            ->assertSee('0123456789012')->assertSee('GCash')
            ->assertDontSee('Amount Received:')->assertDontSee('Change:');
    }

    public function test_receipt_retains_fractional_quantities_and_other_cashiers_cannot_read_it(): void
    {
        $sale = $this->sale();
        $sale->soldItems->first()->update(['Quantity' => 1.5]);
        $this->actingAs($sale->user)->get(route('pos.receipt', $sale->ID))->assertOk()
            ->assertSee('1.5x @ ₱100.00');
        $other = User::factory()->create(['role' => 'Cashier', 'is_active' => true]);
        $this->actingAs($other)->get(route('pos.receipt', $sale->ID))->assertForbidden();
    }
}
