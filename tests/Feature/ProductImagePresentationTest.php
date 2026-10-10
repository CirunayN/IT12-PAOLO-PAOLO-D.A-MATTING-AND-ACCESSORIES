<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductImagePresentationTest extends TestCase
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

    public function test_all_products_have_valid_images_saved_in_uploads_products_folder(): void
    {
        $products = Product::all();
        $this->assertNotEmpty($products, 'Products should exist in the database');

        $multiImageProductsCount = 0;

        foreach ($products as $product) {
            $this->assertNotNull($product->Image, "Product #{$product->ID} ({$product->Name}) must have an Image path");
            $this->assertNotEmpty($product->Images, "Product #{$product->ID} ({$product->Name}) must have an Images array");
            $this->assertLessThanOrEqual(5, count($product->Images), "Product #{$product->ID} must not exceed 5 images");

            // File exists on disk
            $primaryFullPath = public_path($product->Image);
            $this->assertFileExists($primaryFullPath, "Primary image file must exist: {$primaryFullPath}");

            // All gallery files exist on disk
            foreach ($product->Images as $galleryPath) {
                $galleryFullPath = public_path($galleryPath);
                $this->assertFileExists($galleryFullPath, "Gallery image file must exist: {$galleryFullPath}");
            }

            // Image URL accessor works
            $this->assertNotNull($product->image_url, "image_url accessor should not be null for #{$product->ID}");
            $this->assertNotEmpty($product->all_image_urls, "all_image_urls should not be empty for #{$product->ID}");

            if (count($product->all_image_urls) > 1) {
                $multiImageProductsCount++;
            }
        }

        // Verify that multiple products have extra gallery images
        $this->assertGreaterThanOrEqual(5, $multiImageProductsCount, 'Multiple products should have extra gallery images');
    }

    public function test_inventory_displays_product_images_and_gallery_badges(): void
    {
        // 1. Active Catalog tab
        $responseActive = $this->actingAs($this->admin)->get(route('products.index', ['tab' => 'active']));
        $responseActive->assertOk();
        $responseActive->assertSee('openImageGallery', false);
        $responseActive->assertSee('uploads/products/da64_transformer_matting.jpg', false);

        // Search active product with newly generated image
        $responseTray = $this->actingAs($this->admin)->get(route('products.index', ['tab' => 'active', 'search' => 'Cargo']));
        $responseTray->assertOk();
        $responseTray->assertSee('uploads/products/da64_cargo_tray.jpg', false);

        // 2. Archived / Disabled tab
        $responseArchived = $this->actingAs($this->admin)->get(route('products.index', ['tab' => 'archived', 'search' => 'Black Ice']));
        $responseArchived->assertOk();
        $responseArchived->assertSee('uploads/products/little_trees_black_ice.jpg', false);
    }

    public function test_pos_displays_product_images_and_gallery_buttons(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('openPosGalleryFromButton', false);
        $response->assertSee('data-gallery-images', false);
        $response->assertSee('uploads/products/da64_transformer_matting.jpg', false);
    }
}
