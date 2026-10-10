<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        [$query, $tab, $archivedStatus] = $this->inventoryQuery($request);

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('Name')->get();
        $statuses = Status::all();

        $activeCount = Product::where('Status_ID', '!=', $archivedStatus->ID)->count();
        $archivedCount = Product::where('Status_ID', $archivedStatus->ID)->count();
        $totalCount = Product::count();

        return view('products.index', compact(
            'products',
            'categories',
            'statuses',
            'tab',
            'activeCount',
            'archivedCount',
            'totalCount'
        ));
    }

    private function inventoryQuery(Request $request): array
    {
        $request->validate(['sort' => ['nullable', Rule::in([
            'newest', 'oldest', 'name_asc', 'name_desc',
            'stock_asc', 'stock_desc', 'price_asc', 'price_desc',
        ])]]);
        $query = Product::with(['category', 'status', 'stockIns', 'soldItems']);

        $tab = $request->get('tab', 'active');
        $archivedStatus = Status::firstOrCreate(['Name' => 'Archived']);

        if ($tab === 'archived') {
            $query->where('Status_ID', $archivedStatus->ID);
        } elseif ($tab === 'active') {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('Name', 'like', '%' . $request->search . '%')
                    ->orWhere('Description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('Category_ID', $request->category_id);
        }

        if ($request->filled('status_id')) {
            $query->where('Status_ID', $request->status_id);
        }

        // Filter by SELLABLE stock only: remaining > 0, Good condition,
        // and either non-expiring or not yet expired.
        $sellableBatches = fn () => StockIn::query()
            ->whereColumn('Product_ID', 'tbl_product.ID')
            ->where('Remaining_Quantity', '>', 0)
            ->where('Condition', 'Good')
            ->where(function ($stockQuery) {
                $stockQuery->where('Has_Expiration', false)
                    ->orWhereNull('Expiration_Date')
                    ->orWhereDate('Expiration_Date', '>=', today()->toDateString());
            });
        $sellableStock = fn () => $sellableBatches()->selectRaw('COALESCE(SUM(Remaining_Quantity), 0)');

        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'attention':
                    $query->where($sellableStock(), '<=', 5);
                    break;

                case 'out':
                    $query->where($sellableStock(), '<=', 0);
                    break;

                case 'low':
                    $query->where($sellableStock(), '>', 0)
                        ->where($sellableStock(), '<=', 5);
                    break;

                case 'available':
                    $query->where($sellableStock(), '>', 5);
                    break;
            }
        }

        switch ($request->input('sort', 'stock_asc')) {
            case 'name_asc':
            case 'name_desc':
                $query->orderBy('Name', $request->sort === 'name_asc' ? 'asc' : 'desc');
                break;
            case 'stock_desc':
                $query->orderBy($sellableStock(), 'desc');
                break;
            case 'stock_asc':
                $query->orderBy($sellableStock(), 'asc');
                break;
            case 'price_asc':
            case 'price_desc':
                // Match the retail price shown in inventory: the newest sellable batch.
                $query->orderBy($sellableBatches()->select('Retail_Price')->orderBy('ID', 'desc')->limit(1), $request->sort === 'price_asc' ? 'asc' : 'desc');
                break;
        }
        $query->orderBy('ID', $request->sort === 'oldest' ? 'asc' : 'desc');

        return [$query, $tab, $archivedStatus];
    }

    public function print(Request $request)
    {
        [$query, $tab] = $this->inventoryQuery($request);
        $products = $query->get();
        return app(\App\Services\PrintableReport::class)->respond($request, 'products.print', compact('products', 'tab'));
    }

    public function create()
    {
        $archivedStatus = Status::where('Name', 'Archived')->first();

        $productQuery = Product::with(['category', 'status', 'stockIns', 'soldItems']);

        if ($archivedStatus) {
            $productQuery->where('Status_ID', '!=', $archivedStatus->ID);
        }

        $existingProducts = $productQuery->orderBy('Name', 'asc')->get();
        $categories = Category::active()->orderBy('Name', 'asc')->get();
        $statuses = Status::where('Name', '!=', 'Archived')->get();

        return view(
            'products.create',
            compact('existingProducts', 'categories', 'statuses')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_mode' => 'required|in:existing,new',

            'Existing_Product_ID' =>
                'required_if:product_mode,existing|nullable|exists:tbl_product,ID',

            'Name' =>
                'required_if:product_mode,new|nullable|string|max:255|unique:tbl_product,Name',

            'Description' =>
                'nullable|string|max:2000',

            'Category_ID' =>
                ['required_if:product_mode,new', 'nullable', Rule::exists('tbl_category', 'ID')->where('Is_Archived', false)],

            'Status_ID' =>
                'required_if:product_mode,new|nullable|exists:tbl_status,ID',

            'images' =>
                'nullable|array|max:5',

            'images.*' =>
                'image|mimes:jpeg,png,jpg,webp,gif|max:5120',

            'initial_quantity' =>
                'required_if:product_mode,existing|nullable|numeric|min:0',

            'cost_price' =>
                'nullable|numeric|min:0',

            'retail_price' =>
                'nullable|numeric|min:0',

            'has_expiration' =>
                'nullable|boolean',

            'expiration_date' =>
                'nullable|required_if:has_expiration,1|date|after_or_equal:today',

            'condition' =>
                'nullable|in:Good,Damaged,Defective',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            /*
             * EXISTING PRODUCT MODE
             * ---------------------
             * Do not duplicate tbl_product.
             * Add a new stock-in/batch row, allowing a different
             * cost, retail price, condition, and expiration.
             */
            if ($validated['product_mode'] === 'existing') {
                $product = Product::findOrFail($validated['Existing_Product_ID']);

                $quantity = (float) ($validated['initial_quantity'] ?? 0);

                if ($quantity < 1) {
                    return back()
                        ->withErrors([
                            'initial_quantity' =>
                                'Quantity must be at least 1 when adding stock to an existing product.',
                        ])
                        ->withInput();
                }

                if (!array_key_exists('cost_price', $validated) || $validated['cost_price'] === null) {
                    return back()
                        ->withErrors([
                            'cost_price' =>
                                'Cost price is required when adding stock to an existing product.',
                        ])
                        ->withInput();
                }

                if (!array_key_exists('retail_price', $validated) || $validated['retail_price'] === null) {
                    return back()
                        ->withErrors([
                            'retail_price' =>
                                'Retail price is required when adding stock to an existing product.',
                        ])
                        ->withInput();
                }

                $hasExpiration = $request->boolean('has_expiration');

                StockIn::create([
                    'Product_ID' => $product->ID,
                    'User_ID' => auth()->id(),
                    'Quantity' => $quantity,
                    'Remaining_Quantity' => $quantity,
                    'Cost_Price' => $validated['cost_price'],
                    'Retail_Price' => $validated['retail_price'],
                    'Has_Expiration' => $hasExpiration,
                    'Expiration_Date' => $hasExpiration
                        ? ($validated['expiration_date'] ?? null)
                        : null,
                    'Condition' => $validated['condition'] ?? 'Good',
                ]);

                return redirect()
                    ->route('products.index')
                    ->with(
                        'success',
                        "New inventory batch added for '{$product->Name}'. " .
                        "Different price/condition/expiration values are stored as a new stock row."
                    );
            }

            /*
             * NEW PRODUCT MODE
             * ----------------
             * Create a new product master row, then optionally
             * create its first stock-in batch.
             */
            $imagePaths = [];

            if ($request->hasFile('images')) {
                $destination = public_path('uploads/products');

                if (!File::exists($destination)) {
                    File::makeDirectory($destination, 0777, true, true);
                }

                foreach (array_slice($request->file('images'), 0, 5) as $file) {
                    $filename =
                        time() . '_' .
                        Str::random(6) . '_' .
                        Str::slug($request->Name) . '.' .
                        $file->getClientOriginalExtension();

                    $file->move($destination, $filename);
                    $imagePaths[] = 'uploads/products/' . $filename;
                }
            }

            $product = Product::create([
                'Name' => $validated['Name'],
                'Description' => $validated['Description'] ?? null,
                'Category_ID' => $validated['Category_ID'],
                'Status_ID' => $validated['Status_ID'],
                'Image' => $imagePaths[0] ?? null,
                'Images' => $imagePaths,
            ]);

            $quantity = (float) ($validated['initial_quantity'] ?? 0);

            if ($quantity > 0) {
                $hasExpiration = $request->boolean('has_expiration');

                StockIn::create([
                    'Product_ID' => $product->ID,
                    'User_ID' => auth()->id(),
                    'Quantity' => $quantity,
                    'Remaining_Quantity' => $quantity,
                    'Cost_Price' => $validated['cost_price'] ?? 0,
                    'Retail_Price' => $validated['retail_price'] ?? 0,
                    'Has_Expiration' => $hasExpiration,
                    'Expiration_Date' => $hasExpiration
                        ? ($validated['expiration_date'] ?? null)
                        : null,
                    'Condition' => $validated['condition'] ?? 'Good',
                ]);
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Product created successfully!');
        });
    }

    public function edit($id)
    {
        $product = Product::with(['category', 'status', 'stockIns' => fn ($query) => $query->with('user')->orderBy('ID', 'desc')])->findOrFail($id);
        $categories = Category::where(fn ($query) => $query->active()->orWhere('ID', $product->Category_ID))->orderBy('Name')->get();
        $statuses = Status::all();

        return view('products.edit', compact('product', 'categories', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $existingProduct = Product::findOrFail($id);

        $validated = $request->validate([
            'Name' => 'required|string|max:255',
            'Description' => 'nullable|string|max:2000',
            'Category_ID' => ['required', Rule::exists('tbl_category', 'ID')->where(fn ($query) =>
                $query->where(fn ($categories) => $categories->where('Is_Archived', false)->orWhere('ID', $existingProduct->Category_ID)))],
            'Status_ID' => 'required|exists:tbl_status,ID',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'batches' => 'sometimes|array',
            'batches.*' => 'array:ID,Quantity,Cost_Price,Retail_Price,Has_Expiration,Expiration_Date,Condition',
            'batches.*.ID' => ['required', 'integer', 'distinct', Rule::exists('tbl_stock_in', 'ID')->where('Product_ID', $id)],
            'batches.*.Quantity' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'batches.*.Cost_Price' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'batches.*.Retail_Price' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'batches.*.Has_Expiration' => 'required|boolean',
            // Existing batches may already be expired; their dates must remain editable.
            'batches.*.Expiration_Date' => 'nullable|required_if:batches.*.Has_Expiration,true,1|date_format:Y-m-d',
            'batches.*.Condition' => 'required|in:Good,Damaged,Defective',
        ]);

        $newImagePaths = [];
        $removedImagePaths = [];

        try {
            DB::transaction(function () use ($request, $id, $validated, &$newImagePaths, &$removedImagePaths) {
                $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
                $batchInputs = $validated['batches'] ?? [];
                $batches = $product->stockIns()
                    ->whereIn('ID', array_column($batchInputs, 'ID'))
                    ->orderBy('ID')->lockForUpdate()->get()->keyBy('ID');
                $batchChanges = [];

                foreach ($batchInputs as $key => $input) {
                    $batch = $batches->get($input['ID']);
                    if (!$batch) {
                        throw ValidationException::withMessages([
                            "batches.$key.ID" => 'This stock batch no longer belongs to this product.',
                        ]);
                    }

                    // Preserve FIFO allocations, including any sale made since the edit form opened.
                    $usedQuantity = max(0, round((float) $batch->Quantity - (float) $batch->Remaining_Quantity, 2));
                    $quantity = round((float) $input['Quantity'], 2);
                    if ($quantity < $usedQuantity) {
                        throw ValidationException::withMessages([
                            "batches.$key.Quantity" => "Batch #SI-{$batch->ID} already has {$usedQuantity} units sold or used. Received quantity cannot be lower than this.",
                        ]);
                    }

                    $hasExpiration = (bool) $input['Has_Expiration'];
                    $batchChanges[] = [$batch, [
                        'Quantity' => $quantity,
                        'Remaining_Quantity' => round($quantity - $usedQuantity, 2),
                        'Cost_Price' => $input['Cost_Price'],
                        'Retail_Price' => $input['Retail_Price'],
                        'Has_Expiration' => $hasExpiration,
                        'Expiration_Date' => $hasExpiration ? $input['Expiration_Date'] : null,
                        'Condition' => $input['Condition'],
                    ]];
                }

                $currentImages = is_array($product->Images) && count($product->Images) > 0
                    ? $product->Images
                    : ($product->Image ? [$product->Image] : []);
                $removedImagePaths = array_values(array_intersect($currentImages, $validated['remove_images'] ?? []));
                $currentImages = array_values(array_diff($currentImages, $removedImagePaths));
                $uploads = $request->file('images', []);

                if (count($currentImages) + count($uploads) > 5) {
                    throw ValidationException::withMessages([
                        'images' => 'A product can have up to 5 photos. Remove an existing photo before adding another.',
                    ]);
                }

                foreach ($uploads as $file) {
                    $destination = public_path('uploads/products');
                    File::ensureDirectoryExists($destination);
                    $filename = Str::uuid().'.'.$file->extension();
                    $file->move($destination, $filename);
                    $newImagePaths[] = 'uploads/products/'.$filename;
                    $currentImages[] = 'uploads/products/'.$filename;
                }

                $product->update([
                    'Name' => $validated['Name'],
                    'Description' => $validated['Description'] ?? null,
                    'Category_ID' => $validated['Category_ID'],
                    'Status_ID' => $validated['Status_ID'],
                    'Image' => $currentImages[0] ?? null,
                    'Images' => $currentImages,
                ]);

                foreach ($batchChanges as [$batch, $changes]) {
                    $batch->update($changes);
                }
            });
        } catch (\Throwable $exception) {
            File::delete(array_map(fn ($path) => public_path($path), $newImagePaths));
            throw $exception;
        }

        // Delete old photos only after both the product and its batches have saved.
        File::delete(array_map(fn ($path) => public_path($path), $removedImagePaths));

        return redirect()->route('products.index')
            ->with('success', 'Product and stock batch details updated successfully!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $archivedStatus = Status::firstOrCreate(['Name' => 'Archived']);

        $product->update([
            'Status_ID' => $archivedStatus->ID,
        ]);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                "Product '{$product->Name}' has been archived and disabled from POS."
            );
    }

    public function restore($id)
    {
        $product = Product::findOrFail($id);
        $activeStatus = Status::firstOrCreate(['Name' => 'Active']);

        $product->update([
            'Status_ID' => $activeStatus->ID,
        ]);

        return redirect()
            ->route('products.index', ['tab' => 'archived'])
            ->with(
                'success',
                "Product '{$product->Name}' has been restored back to active catalog."
            );
    }
}
