<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(Request $request)
    {
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

        $products = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
        $categories = Category::all();
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

    public function create()
    {
        $archivedStatus = Status::where('Name', 'Archived')->first();

        $productQuery = Product::with(['category', 'status', 'stockIns', 'soldItems']);

        if ($archivedStatus) {
            $productQuery->where('Status_ID', '!=', $archivedStatus->ID);
        }

        $existingProducts = $productQuery->orderBy('Name', 'asc')->get();
        $categories = Category::orderBy('Name', 'asc')->get();
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
                'required_if:product_mode,new|nullable|exists:tbl_category,ID',

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
        $product = Product::with(['category', 'status'])->findOrFail($id);
        $categories = Category::all();
        $statuses = Status::all();

        return view('products.edit', compact('product', 'categories', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'Name' => 'required|string|max:255',
            'Description' => 'nullable|string|max:2000',
            'Category_ID' => 'required|exists:tbl_category,ID',
            'Status_ID' => 'required|exists:tbl_status,ID',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        $currentImages = is_array($product->Images)
            ? $product->Images
            : ($product->Image ? [$product->Image] : []);

        if ($request->filled('remove_images') && is_array($request->remove_images)) {
            $filtered = [];

            foreach ($currentImages as $img) {
                if (in_array($img, $request->remove_images)) {
                    $fullPath = public_path($img);

                    if (File::exists($fullPath)) {
                        @unlink($fullPath);
                    }
                } else {
                    $filtered[] = $img;
                }
            }

            $currentImages = $filtered;
        }

        if ($request->hasFile('images')) {
            $destination = public_path('uploads/products');

            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0777, true, true);
            }

            $availableSlots = max(0, 5 - count($currentImages));

            foreach (array_slice($request->file('images'), 0, $availableSlots) as $file) {
                $filename =
                    time() . '_' .
                    Str::random(6) . '_' .
                    Str::slug($request->Name) . '.' .
                    $file->getClientOriginalExtension();

                $file->move($destination, $filename);
                $currentImages[] = 'uploads/products/' . $filename;
            }
        }

        $currentImages = array_values($currentImages);

        $product->update([
            'Name' => $validated['Name'],
            'Description' => $validated['Description'] ?? null,
            'Category_ID' => $validated['Category_ID'],
            'Status_ID' => $validated['Status_ID'],
            'Image' => $currentImages[0] ?? null,
            'Images' => $currentImages,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully!');
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
