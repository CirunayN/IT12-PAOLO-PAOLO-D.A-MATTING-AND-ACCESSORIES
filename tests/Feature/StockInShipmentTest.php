<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class StockInShipmentTest extends TestCase
{
    use RefreshDatabase;

    private string $testPublicPath;

    private User $admin;

    private Category $category;

    private array $testUploadPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPublicPath = sys_get_temp_dir().'/paolo-stock-in-tests-'.Str::uuid();
        File::ensureDirectoryExists($this->testPublicPath);
        $this->app->usePublicPath($this->testPublicPath);
        $this->admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $this->category = Category::create(['Name' => 'Accessories']);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->testPublicPath)) {
                File::delete($this->testUploadPaths);
                foreach ($this->uploadedFiles() as $file) {
                    File::delete($file->getPathname());
                }
                foreach (['/uploads/products', '/uploads', ''] as $directory) {
                    if (is_dir($this->testPublicPath.$directory)) {
                        rmdir($this->testPublicPath.$directory);
                    }
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    private function uploadedFiles(): array
    {
        $directory = $this->testPublicPath.'/uploads/products';

        return is_dir($directory) ? File::files($directory) : [];
    }

    private function upload(string $name, string $contents, string $mimeType): UploadedFile
    {
        $path = $this->testPublicPath.'/upload-'.Str::uuid().'.tmp';
        File::put($path, $contents);
        $this->testUploadPaths[] = $path;

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function photo(string $name = 'photo.png', int $padding = 0): UploadedFile
    {
        return $this->upload($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aM1sAAAAASUVORK5CYII='
        ).str_repeat('x', $padding), 'image/png');
    }

    private function shipment(array $overrides = []): array
    {
        return array_replace([
            'product_mode' => 'new',
            'New_Product_Name' => 'New accessory',
            'New_Product_Description' => 'An accessory with photos',
            'New_Category_ID' => $this->category->ID,
            'Quantity' => 4,
            'Cost_Price' => 10,
            'Retail_Price' => 20,
        ], $overrides);
    }

    public function test_form_displays_automatic_processor_and_accepts_product_photos(): void
    {
        $this->actingAs($this->admin)->get(route('stock-in.create'))
            ->assertOk()
            ->assertSee($this->admin->name)
            ->assertDontSee('Choose an existing product or create a new one, then record the received batch.')
            ->assertDontSee('Processed / Received By')
            ->assertDontSee('Audit trail')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="images[]"', false)
            ->assertDontSee('name="User_ID"', false);
    }

    public function test_stock_in_form_prefills_when_product_id_provided_and_redirects_to_products(): void
    {
        $status = Status::create(['Name' => 'Active']);
        $product = Product::create([
            'Name' => 'Prefilled Floor Mat',
            'Description' => 'High quality rubber mat',
            'Category_ID' => $this->category->ID,
            'Status_ID' => $status->ID,
        ]);
        StockIn::create([
            'Product_ID' => $product->ID,
            'User_ID' => $this->admin->id,
            'Quantity' => 10,
            'Remaining_Quantity' => 2,
            'Cost_Price' => 350.50,
            'Retail_Price' => 599.99,
            'Condition' => 'Good',
        ]);

        $this->actingAs($this->admin)
            ->get(route('stock-in.create', ['product_id' => $product->ID, 'redirect_to' => 'products']))
            ->assertOk()
            ->assertSee('Prefilled Floor Mat')
            ->assertSee('High quality rubber mat')
            ->assertSee('350.50')
            ->assertSee('599.99');

        $this->actingAs($this->admin)->post(route('stock-in.store'), [
            'product_mode' => 'existing',
            'Product_ID' => $product->ID,
            'Quantity' => 15,
            'Cost_Price' => 350.50,
            'Retail_Price' => 599.99,
            'redirect_to' => 'products',
        ])->assertRedirect(route('products.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tbl_stock_in', [
            'Product_ID' => $product->ID,
            'User_ID' => $this->admin->id,
            'Quantity' => 15,
            'Cost_Price' => 350.50,
            'Retail_Price' => 599.99,
        ]);
    }

    public function test_new_product_saves_uploaded_photos_and_signed_in_processor(): void
    {
        $firstPhoto = $this->photo('front.png');
        $contents = file_get_contents($firstPhoto->getPathname());
        $this->actingAs($this->admin)->post(route('stock-in.store'), $this->shipment([
            'User_ID' => 999999,
            'images' => [$firstPhoto, $this->photo('side.png')],
        ]))->assertRedirect(route('stock-in.index'))->assertSessionHasNoErrors();

        $product = Product::sole();
        $this->assertCount(2, $product->Images);
        $this->assertSame($product->Images[0], $product->Image);
        foreach ($product->Images as $path) {
            $this->assertFileExists(public_path($path));
            $this->assertSame($contents, file_get_contents(public_path($path)));
        }
        $this->assertSame(asset($product->Image), $product->image_url);
        $this->assertDatabaseHas('tbl_stock_in', [
            'Product_ID' => $product->ID, 'User_ID' => $this->admin->id,
            'Quantity' => 4, 'Remaining_Quantity' => 4,
        ]);
    }

    public function test_new_product_photos_are_optional(): void
    {
        $this->actingAs($this->admin)->post(route('stock-in.store'), $this->shipment())
            ->assertRedirect(route('stock-in.index'))->assertSessionHasNoErrors();
        $product = Product::sole();
        $this->assertNull($product->Image);
        $this->assertSame([], $product->Images);
        $this->assertSame($this->admin->id, StockIn::sole()->User_ID);
    }

    public function test_invalid_oversized_or_excess_photos_do_not_create_products_or_batches(): void
    {
        $invalidUploads = [
            [[$this->upload('notes.txt', 'Not a photo', 'text/plain')], 'images.0'],
            [[$this->photo('huge.png', 5 * 1024 * 1024)], 'images.0'],
            [array_map(fn ($index) => $this->photo("photo-$index.png"), range(1, 6)), 'images'],
        ];
        foreach ($invalidUploads as [$images, $error]) {
            $this->actingAs($this->admin)->postJson(route('stock-in.store'), $this->shipment(['images' => $images]))
                ->assertUnprocessable()->assertJsonValidationErrors($error);
            $this->assertDatabaseCount('tbl_product', 0);
            $this->assertDatabaseCount('tbl_stock_in', 0);
        }
        $this->assertSame([], $this->uploadedFiles());
    }

    public function test_existing_product_stock_does_not_replace_photos(): void
    {
        $status = Status::create(['Name' => 'Active']);
        $product = Product::create([
            'Name' => 'Existing accessory', 'Category_ID' => $this->category->ID,
            'Status_ID' => $status->ID, 'Image' => 'uploads/products/saved.png',
            'Images' => ['uploads/products/saved.png'],
        ]);
        $this->actingAs($this->admin)->post(route('stock-in.store'), $this->shipment([
            'product_mode' => 'existing', 'Product_ID' => $product->ID,
            'images' => [$this->upload('ignored.txt', 'Ignored in existing mode', 'text/plain')],
        ]))->assertRedirect(route('stock-in.index'))->assertSessionHasNoErrors();

        $this->assertSame(['uploads/products/saved.png'], $product->fresh()->Images);
        $this->assertDatabaseCount('tbl_product', 1);
        $this->assertSame([], $this->uploadedFiles());
    }

    public function test_failed_batch_removes_uploaded_photos_and_rolls_back_product(): void
    {
        StockIn::creating(function () {
            throw new RuntimeException('Test batch failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin)->post(route('stock-in.store'), $this->shipment(['images' => [$this->photo()]]));
            $this->fail('The failed batch must roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Test batch failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('tbl_product', 0);
        $this->assertDatabaseCount('tbl_stock_in', 0);
        $this->assertSame([], $this->uploadedFiles());
    }
}
