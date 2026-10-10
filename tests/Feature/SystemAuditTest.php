<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemAuditTest extends TestCase
{
    private User $admin;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'it12',
        ]);
        DB::purge('mysql');

        $this->admin = User::where('role', 'Admin')->firstOrFail();
        $this->cashier = User::where('role', 'Cashier')->firstOrFail();
    }

    // ==========================================
    // ADMIN FLOW TESTS (Full Permissions)
    // ==========================================

    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Admin Paolo');
        $response->assertSee('Administration');
        $response->assertSee('Top 5 Best-Selling Products');
        $response->assertSee('Top 5 Least-Selling Products');
        $response->assertDontSee('Apply Dates');
        $response->assertSee('Reset');
    }

    public function test_admin_pos_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pos.index'));
        $response->assertOk();
        $response->assertSee('POS Cashier Terminal');
        $response->assertSee('All Categories');
    }

    public function test_admin_pos_receipt_view(): void
    {
        $sale = Sale::latest('ID')->first();
        if ($sale) {
            $response = $this->actingAs($this->admin)->get(route('pos.receipt', $sale->ID));
            $response->assertOk();
            $response->assertSee('PAOLO PAOLO');
            $response->assertSee('Invoice #:');
        }
    }

    public function test_admin_pos_checkout_flow(): void
    {
        $product = Product::whereHas('stockIns', fn ($q) => $q->where('Remaining_Quantity', '>', 0))->first();
        $paymentMethod = PaymentMethod::where('Name', 'Cash')->first();

        $this->assertNotNull($product, 'Sellable product found');
        $this->assertNotNull($paymentMethod, 'Cash payment method found');

        DB::beginTransaction();
        try {
            $response = $this->actingAs($this->admin)->postJson(route('pos.checkout'), [
                'items' => [
                    [
                        'product_id' => $product->ID,
                        'quantity' => 1,
                    ],
                ],
                'payment_method_id' => $paymentMethod->ID,
                'amount_tendered' => 50000,
            ]);

            $response->assertOk();
            $response->assertJsonStructure(['success', 'sale_id', 'total', 'tendered', 'change']);
            $this->assertTrue($response->json('success'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_admin_products_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('products.index'));
        $response->assertOk();
        $response->assertSee('Inventory');

        $responseCreate = $this->actingAs($this->admin)->get(route('products.create'));
        $responseCreate->assertOk();

        $product = Product::first();
        if ($product) {
            $responseEdit = $this->actingAs($this->admin)->get(route('products.edit', $product));
            $responseEdit->assertOk();
        }

        $responsePrint = $this->actingAs($this->admin)->get(route('products.print'));
        $responsePrint->assertOk();

        $responsePdf = $this->actingAs($this->admin)->get(route('products.print', ['output' => 'pdf']));
        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_stock_in_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stock-in.index'));
        $response->assertOk();
        $response->assertSee('Receive New Shipment');

        $responseCreate = $this->actingAs($this->admin)->get(route('stock-in.create'));
        $responseCreate->assertOk();

        $responsePrint = $this->actingAs($this->admin)->get(route('stock-in.print'));
        $responsePrint->assertOk();

        $responsePdf = $this->actingAs($this->admin)->get(route('stock-in.print', ['output' => 'pdf']));
        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_categories_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('categories.index'));
        $response->assertOk();
        $response->assertJsonStructure(['categories']);

        $category = Category::first();
        if ($category) {
            $archiveRes = $this->actingAs($this->admin)->post(route('categories.archive', $category));
            $archiveRes->assertRedirect();

            $restoreRes = $this->actingAs($this->admin)->post(route('categories.restore', $category));
            $restoreRes->assertRedirect();
        }
    }

    public function test_admin_transactions_history_and_exports(): void
    {
        $response = $this->actingAs($this->admin)->get(route('transactions.index'));
        $response->assertOk();
        $response->assertSee('Transactions & Sales');

        $responseCsv = $this->actingAs($this->admin)->get(route('transactions.export_csv'));
        $responseCsv->assertOk();

        $responsePrint = $this->actingAs($this->admin)->get(route('transactions.print'));
        $responsePrint->assertOk();

        $responsePdf = $this->actingAs($this->admin)->get(route('transactions.print', ['output' => 'pdf']));
        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_reports(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertDontSee('Generate, review, and print business, inventory, or cashier performance reports in tamper-evident PDF format.');
        $response->assertSee(route('transactions.index'));
        $response->assertSee(route('products.index'));
        $response->assertSee(route('stock-in.index'));

        $responsePrint = $this->actingAs($this->admin)->get(route('reports.print'));
        $responsePrint->assertOk();

        $responsePdf = $this->actingAs($this->admin)->get(route('reports.print', ['output' => 'pdf']));
        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_backup_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('backup.index'));
        $response->assertOk();
        $response->assertSee('Backup');
        $response->assertSee('Active Backups');
    }

    public function test_admin_security_and_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('security.index'));
        $response->assertOk();
        $response->assertSee('Security');

        $responseAccount = $this->actingAs($this->admin)->get(route('settings.account'));
        $responseAccount->assertOk();

        $responsePassword = $this->actingAs($this->admin)->get(route('settings.password'));
        $responsePassword->assertOk();
    }

    // ==========================================
    // CASHIER FLOW TESTS (Allowed Operations)
    // ==========================================

    public function test_cashier_pos_terminal_renders(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('pos.index'));
        $response->assertOk();
        $response->assertSee('POS Cashier Terminal');
        $response->assertSee('Maria Cashier');
        $response->assertSee('Cashier Station');
    }

    public function test_cashier_can_process_checkout(): void
    {
        $product = Product::whereHas('stockIns', fn ($q) => $q->where('Remaining_Quantity', '>', 0))->first();
        $paymentMethod = PaymentMethod::where('Name', 'Cash')->first();

        $this->assertNotNull($product, 'Sellable product found');
        $this->assertNotNull($paymentMethod, 'Cash payment method found');

        DB::beginTransaction();
        try {
            $checkout = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), [
                'items' => [
                    [
                        'product_id' => $product->ID,
                        'quantity' => 1,
                    ],
                ],
                'payment_method_id' => $paymentMethod->ID,
                'amount_tendered' => 50000,
            ]);

            $checkout->assertOk();
            $checkout->assertJsonStructure(['success', 'sale_id', 'total']);
            $this->assertTrue($checkout->json('success'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_cashier_can_view_own_transactions(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('transactions.index'));
        $response->assertOk();
        $response->assertSee('Transactions & Sales');

        $responsePrint = $this->actingAs($this->cashier)->get(route('transactions.print'));
        $responsePrint->assertOk();
    }

    public function test_cashier_can_access_account_and_password_settings(): void
    {
        $responseAccount = $this->actingAs($this->cashier)->get(route('settings.account'));
        $responseAccount->assertOk();

        $responsePassword = $this->actingAs($this->cashier)->get(route('settings.password'));
        $responsePassword->assertOk();
    }

    // ==========================================
    // CASHIER RBAC RESTRICTIONS (Must be 403 Forbidden)
    // ==========================================

    public function test_cashier_is_forbidden_from_admin_dashboard(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));
        $response->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_inventory_management(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('products.index'));
        $response->assertForbidden();

        $responseCreate = $this->actingAs($this->cashier)->get(route('products.create'));
        $responseCreate->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_categories_management(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('categories.index'));
        $response->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_stock_in(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('stock-in.index'));
        $response->assertForbidden();

        $responseCreate = $this->actingAs($this->cashier)->get(route('stock-in.create'));
        $responseCreate->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_reports(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('reports.index'));
        $response->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_backups(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('backup.index'));
        $response->assertForbidden();
    }

    public function test_cashier_is_forbidden_from_security_center(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('security.index'));
        $response->assertForbidden();
    }

    // ==========================================
    // EDGE CASES & VALIDATION TESTS
    // ==========================================

    public function test_pos_checkout_validates_insufficient_payment(): void
    {
        $product = Product::whereHas('stockIns', fn ($q) => $q->where('Remaining_Quantity', '>', 0))->first();
        $cashPayment = PaymentMethod::where('Name', 'Cash')->first();

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $product->ID,
                    'quantity' => 1,
                ],
            ],
            'payment_method_id' => $cashPayment->ID,
            'amount_tendered' => 0.01, // Insufficient
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_pos_checkout_validates_gcash_requires_reference(): void
    {
        $product = Product::whereHas('stockIns', fn ($q) => $q->where('Remaining_Quantity', '>', 0))->first();
        $gcashPayment = PaymentMethod::where('Name', 'GCash')->first();

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $product->ID,
                    'quantity' => 1,
                ],
            ],
            'payment_method_id' => $gcashPayment->ID,
            'amount_tendered' => $product->retail_price,
            'gcash_reference' => '', // Empty
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_admin_dashboard_with_date_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-10',
        ]));
        $response->assertOk();
        $response->assertSee('Top 5 Best-Selling Products');
    }

    public function test_admin_transactions_with_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('transactions.index', [
            'period' => 'overall',
            'search' => 'INV',
        ]));
        $response->assertOk();
    }

    public function test_admin_reports_with_filters(): void
    {
        $category = Category::first();
        $response = $this->actingAs($this->admin)->get(route('reports.index', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-10',
            'category_id' => $category?->ID,
        ]));
        $response->assertOk();
    }

    public function test_inactive_user_cannot_access_system(): void
    {
        $inactiveUser = User::where('is_active', false)->first();
        if ($inactiveUser) {
            $response = $this->actingAs($inactiveUser)->get(route('dashboard'));
            // Active middleware should redirect or deny
            $this->assertTrue(in_array($response->status(), [302, 403]));
        }
    }
}
