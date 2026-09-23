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
        $catDeepDish = Category::create(['Name' => '3-Row Deep Dish Matting']);
        $cat2Row = Category::create(['Name' => '2-Row Premium Matting']);
        $catCoil = Category::create(['Name' => 'Coil Carpet Overmats']);
        $catTrunk = Category::create(['Name' => 'Luggage & Trunk Trays']);
        $catCovers = Category::create(['Name' => 'Car Covers & Sunshades']);

        // 3. Payment Methods
        $payCash = PaymentMethod::create(['Name' => 'Cash']);
        $payGCash = PaymentMethod::create(['Name' => 'GCash']);
        $payMaya = PaymentMethod::create(['Name' => 'Maya']);
        $payCard = PaymentMethod::create(['Name' => 'Credit / Debit Card']);
        $payBank = PaymentMethod::create(['Name' => 'Bank Transfer']);

        // 4. Users with Roles from Use Case Diagram
        $admin = User::create([
            'name' => 'Admin Paolo',
            'email' => 'admin@paolopaolo.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
        ]);

        $packer = User::create([
            'name' => 'Juan Packer',
            'email' => 'packer@paolopaolo.com',
            'password' => Hash::make('password'),
            'role' => 'Packer',
        ]);

        $installer = User::create([
            'name' => 'Pedro Installer',
            'email' => 'installer@paolopaolo.com',
            'password' => Hash::make('password'),
            'role' => 'Accessory Installer',
        ]);

        $worker = User::create([
            'name' => 'Marco Worker',
            'email' => 'worker@paolopaolo.com',
            'password' => Hash::make('password'),
            'role' => 'Production Worker',
        ]);

        // 5. Products & Initial Stock-Ins
        $productsData = [
            [
                'name' => 'Toyota Fortuner 2016-2024 Deep Dish Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 15,
                'cost' => 2500.00,
                'retail' => 4500.00,
            ],
            [
                'name' => 'Mitsubishi Montero Sport 2016-2024 Deep Dish Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 12,
                'cost' => 2500.00,
                'retail' => 4500.00,
            ],
            [
                'name' => 'Toyota Hilux Revo / Conquest 2-Row Matting Set',
                'category_id' => $cat2Row->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 10,
                'cost' => 1800.00,
                'retail' => 3200.00,
            ],
            [
                'name' => 'Ford Ranger / Everest 2023-2024 Next-Gen Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 8,
                'cost' => 2600.00,
                'retail' => 4800.00,
            ],
            [
                'name' => 'Honda CR-V 2018-2024 Diamond Matting',
                'category_id' => $catDeepDish->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 6,
                'cost' => 2400.00,
                'retail' => 4200.00,
            ],
            [
                'name' => 'Nissan Navara Pro-4X Coil Carpet Overmats',
                'category_id' => $catCoil->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 7,
                'cost' => 1200.00,
                'retail' => 2200.00,
            ],
            [
                'name' => 'Universal Waterproof Heavy Duty Cargo Trunk Tray',
                'category_id' => $catTrunk->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 20,
                'cost' => 900.00,
                'retail' => 1800.00,
            ],
            [
                'name' => 'Toyota Fortuner Heavy-Duty All-Weather Car Cover',
                'category_id' => $catCovers->ID,
                'status_id' => $statusActive->ID,
                'quantity' => 5,
                'cost' => 1100.00,
                'retail' => 2200.00,
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $item) {
            $p = Product::create([
                'Name' => $item['name'],
                'Category_ID' => $item['category_id'],
                'Status_ID' => $item['status_id'],
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
