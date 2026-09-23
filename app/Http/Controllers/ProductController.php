<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            $query->where('Name', 'like', '%' . $request->search . '%');
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

        return view('products.index', compact('products', 'categories', 'statuses', 'tab', 'activeCount', 'archivedCount', 'totalCount'));
    }

    public function create()
    {
        $categories = Category::all();
        $statuses = Status::where('Name', '!=', 'Archived')->get();
        return view('products.create', compact('categories', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:255',
            'Category_ID' => 'required|exists:tbl_category,ID',
            'Status_ID' => 'required|exists:tbl_status,ID',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'initial_quantity' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $destination = public_path('uploads/products');
            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0777, true, true);
            }

            foreach (array_slice($request->file('images'), 0, 5) as $file) {
                $filename = time() . '_' . Str::random(6) . '_' . Str::slug($request->Name) . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);
                $imagePaths[] = 'uploads/products/' . $filename;
            }
        }

        $product = Product::create([
            'Name' => $validated['Name'],
            'Category_ID' => $validated['Category_ID'],
            'Status_ID' => $validated['Status_ID'],
            'Image' => $imagePaths[0] ?? null,
            'Images' => $imagePaths,
        ]);

        if (!empty($validated['initial_quantity']) && $validated['initial_quantity'] > 0) {
            StockIn::create([
                'Product_ID' => $product->ID,
                'Quantity' => $validated['initial_quantity'],
                'Cost_Price' => $validated['cost_price'] ?? 0,
                'Retail_Price' => $validated['retail_price'] ?? 0,
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
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
            'Category_ID' => 'required|exists:tbl_category,ID',
            'Status_ID' => 'required|exists:tbl_status,ID',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        $currentImages = is_array($product->Images) ? $product->Images : ($product->Image ? [$product->Image] : []);

        // Remove unlinked images
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

        // Add new images up to remaining slots out of 5
        if ($request->hasFile('images')) {
            $destination = public_path('uploads/products');
            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0777, true, true);
            }

            $availableSlots = max(0, 5 - count($currentImages));
            foreach (array_slice($request->file('images'), 0, $availableSlots) as $file) {
                $filename = time() . '_' . Str::random(6) . '_' . Str::slug($request->Name) . '.' . $file->getClientOriginalExtension();
                $file->move($destination, $filename);
                $currentImages[] = 'uploads/products/' . $filename;
            }
        }

        $currentImages = array_values($currentImages);

        $product->update([
            'Name' => $validated['Name'],
            'Category_ID' => $validated['Category_ID'],
            'Status_ID' => $validated['Status_ID'],
            'Image' => $currentImages[0] ?? null,
            'Images' => $currentImages,
        ]);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $archivedStatus = Status::firstOrCreate(['Name' => 'Archived']);

        $product->update([
            'Status_ID' => $archivedStatus->ID
        ]);

        return redirect()->route('products.index')->with('success', "Product '{$product->Name}' has been archived and disabled from POS.");
    }

    public function restore($id)
    {
        $product = Product::findOrFail($id);
        $activeStatus = Status::firstOrCreate(['Name' => 'Active']);

        $product->update([
            'Status_ID' => $activeStatus->ID
        ]);

        return redirect()->route('products.index', ['tab' => 'archived'])->with('success', "Product '{$product->Name}' has been restored back to active catalog.");
    }
}