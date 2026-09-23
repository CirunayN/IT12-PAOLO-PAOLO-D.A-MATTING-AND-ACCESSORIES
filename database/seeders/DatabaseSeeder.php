<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Status;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Sale;
use App\Models\SoldItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Statuses
        $statusActive = Status::create(['Name' => 'Active']);
        $statusLowStock = Status::create(['Name' => 'Low Stock']);
        $statusOutOfStock = Status::create(['Name' => 'Out of Stock']);
        $statusArchived = Status::create(['Name' => 'Archived']);

        // 2. Categories
        $catDeepDish = Category::create(['Name' => 'Transformer DA64 Custom Matting']);
        $cat2Row = Category::create(['Name' => 'DA Mini Van Coil Carpet & Interior']);
        $catCargo = Category::create(['Name' => 'Kei Van Luggage & Cargo Trays']);
        $catFluids = Category::create(['Name' => 'Oils, Coolants & Fluids']);
        $catSafety = Category::create(['Name' => 'Safety & Van Accessories']);

        // 3. Payment Methods
        $payCash = PaymentMethod::create(['Name' => 'Cash']);
        $payGCash = PaymentMethod::create(['Name' => 'GCash']);
        $payMaya = PaymentMethod::create(['Name' => 'Maya']);
        $payCard = PaymentMethod::create(['Name' => 'Credit / Debit Card']);
        $payBank = PaymentMethod::create(['Name' => 'Bank Transfer']);

        // 4. Users with Roles (Admin / Owner Level & Cashier / Employee Level)
        $admin = User::create([
            'name' => 'Admin Paolo',
            'username' => 'admin',
            'email' => 'admin@paolopaolo.com',
            'password' => Hash::make('123'),
            'role' => 'Admin',
        ]);

        $cashier = User::create([
            'name' => 'Maria Cashier',
            'username' => 'cashier',
            'email' => 'cashier@paolopaolo.com',
            'password' => Hash::make('123'),
            'role' => 'Cashier',
        ]);

        $packer = User::create([
            'name' => 'Juan Packer',
            'username' => 'packer',
            'email' => 'packer@paolopaolo.com',
            'password' => Hash::make('123'),
            'role' => 'Packer',
        ]);

        $installer = User::create([
            'name' => 'Pedro Installer',
            'username' => 'installer',
            'email' => 'installer@paolopaolo.com',
            'password' => Hash::make('123'),
            'role' => 'Accessory Installer',
        ]);

        $worker = User::create([
            'name' => 'Marco Worker',
            'username' => 'worker',
            'email' => 'worker@paolopaolo.com',
            'password' => Hash::make('123'),
            'role' => 'Production Worker',
        ]);

        // 5. Products & Initial Stock-Ins (Suzuki DA Transformer & Van Spec)
        $productsData = [
            [
                'name' => 'Suzuki Every DA64 "Transformer" 3-Row Deep Dish Floor Matting (Black/Red Trim)',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/da64_transformer_matting.jpg',
                'images' => ['uploads/products/da64_transformer_matting.jpg'],
                'quantity' => 15,
                'cost' => 2400.00,
                'retail' => 3800.00,
            ],
            [
                'name' => 'Suzuki Every DA64 "Transformer" Full Diamond Stitched Interior Floor Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/da64_transformer_matting.jpg',
                'images' => ['uploads/products/da64_transformer_matting.jpg'],
                'quantity' => 12,
                'cost' => 2800.00,
                'retail' => 4200.00,
            ],
            [
                'name' => 'Suzuki Multicab DA63T / DA64 2-Row Front Cabin Premium Matting Set',
                'category_id' => $cat2Row->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/da64_transformer_matting.jpg',
                'images' => ['uploads/products/da64_transformer_matting.jpg'],
                'quantity' => 10,
                'cost' => 1500.00,
                'retail' => 2400.00,
            ],
            [
                'name' => 'Suzuki Every DA17V / DA17W "Next-Gen Transformer" All-Weather 3-Row TPE Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/da64_transformer_matting.jpg',
                'images' => ['uploads/products/da64_transformer_matting.jpg'],
                'quantity' => 8,
                'cost' => 2900.00,
                'retail' => 4500.00,
            ],
            [
                'name' => 'Suzuki Every DA64 "Transformer" Heavy-Duty Rear Cargo & Luggage Bed Tray',
                'category_id' => $catCargo->ID,
                'status_id' => $statusActive->ID,
                'image' => null,
                'images' => null,
                'quantity' => 6,
                'cost' => 1100.00,
                'retail' => 1850.00,
            ],
            [
                'name' => 'Suzuki DA64V / DA64W Transformer Heavy-Duty Coil Carpet Overmats (Dirt Trap)',
                'category_id' => $cat2Row->ID,
                'status_id' => $statusActive->ID,
                'image' => null,
                'images' => null,
                'quantity' => 7,
                'cost' => 1350.00,
                'retail' => 2200.00,
            ],
            [
                'name' => 'Suzuki DA Transformer Heavy-Duty All-Weather Water-Resistant Van Body Cover',
                'category_id' => $catSafety->ID,
                'status_id' => $statusActive->ID,
                'image' => null,
                'images' => null,
                'quantity' => 20,
                'cost' => 1300.00,
                'retail' => 2100.00,
            ],
            [
                'name' => 'Suzuki DA64 Transformer Stainless Steel Door Sill Scuff Plate Set (4-Piece)',
                'category_id' => $catSafety->ID,
                'status_id' => $statusActive->ID,
                'image' => null,
                'images' => null,
                'quantity' => 5,
                'cost' => 750.00,
                'retail' => 1250.00,
            ],
            [
                'name' => 'Long-Life Radiator Coolant 1L (Pre-Mixed 50/50 Anti-Rust for DA Mini Van)',
                'category_id' => $catFluids->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/coolant_da.jpg',
                'images' => ['uploads/products/coolant_da.jpg'],
                'quantity' => 35,
                'cost' => 180.00,
                'retail' => 280.00,
            ],
            [
                'name' => 'Semi-Synthetic Engine Oil 10W-40 4L (Spec for Suzuki K6A Transformer Van)',
                'category_id' => $catFluids->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/engine_oil_da.jpg',
                'images' => ['uploads/products/engine_oil_da.jpg'],
                'quantity' => 25,
                'cost' => 950.00,
                'retail' => 1450.00,
            ],
            [
                'name' => 'Transmission & Differential Hypoid Gear Oil 80W-90 1L (DA Mini Van Spec)',
                'category_id' => $catFluids->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/gear_oil_da.jpg',
                'images' => ['uploads/products/gear_oil_da.jpg'],
                'quantity' => 30,
                'cost' => 220.00,
                'retail' => 350.00,
            ],
            [
                'name' => 'Compact Vehicle Mini Fire Extinguisher 500g with Heavy-Duty Mounting Bracket',
                'category_id' => $catSafety->ID,
                'status_id' => $statusActive->ID,
                'image' => 'uploads/products/fire_extinguisher_mini.jpg',
                'images' => ['uploads/products/fire_extinguisher_mini.jpg'],
                'quantity' => 25,
                'cost' => 280.00,
                'retail' => 450.00,
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $item) {
            $p = Product::create([
                'Name' => $item['name'],
                'Category_ID' => $item['category_id'],
                'Status_ID' => $item['status_id'],
                'Image' => $item['image'],
                'Images' => $item['images'],
            ]);

            StockIn::create([
                'Product_ID' => $p->ID,
                'Quantity' => $item['quantity'],
                'Cost_Price' => $item['cost'],
                'Retail_Price' => $item['retail'],
            ]);

            $createdProducts[] = $p;
        }

        // 6. Sample Sales Transactions
        $sale1 = Sale::create([
            'Date' => now()->subHours(3),
            'Total' => 4500.00,
            'User_ID' => $admin->id,
            'Payment_Method_ID' => $payCash->ID,
        ]);
        SoldItem::create([
            'Product_ID' => $createdProducts[0]->ID,
            'Quantity' => 1,
            'Total' => 4500.00,
            'Sale_ID' => $sale1->ID,
        ]);

        $sale2 = Sale::create([
            'Date' => now()->subHour(1),
            'Total' => 5000.00,
            'User_ID' => $packer->id,
            'Payment_Method_ID' => $payGCash->ID,
        ]);
        SoldItem::create([
            'Product_ID' => $createdProducts[2]->ID,
            'Quantity' => 1,
            'Total' => 3200.00,
            'Sale_ID' => $sale2->ID,
        ]);
        SoldItem::create([
            'Product_ID' => $createdProducts[6]->ID,
            'Quantity' => 1,
            'Total' => 1800.00,
            'Sale_ID' => $sale2->ID,
        ]);
    }
}
