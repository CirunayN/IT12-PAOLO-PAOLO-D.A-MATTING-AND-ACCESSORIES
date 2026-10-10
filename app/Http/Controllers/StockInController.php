<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockInController extends Controller
{
    private function stockInQuery(Request $request)
    {
        $request->validate(['sort' => ['nullable', Rule::in([
            'newest', 'oldest', 'name_asc', 'name_desc',
            'stock_asc', 'stock_desc', 'price_asc', 'price_desc',
        ])]]);

        $query = StockIn::with(['product.category', 'user']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('Name', 'like', '%' . $search . '%')
                        ->orWhere('Description', 'like', '%' . $search . '%');
                });
                $batchId = preg_replace('/[^0-9]/', '', $search);
                if ($batchId !== '') {
                    $q->orWhere('tbl_stock_in.ID', (int) $batchId);
                }
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('Category_ID', $request->category_id);
            });
        }

        if ($request->filled('product_id')) {
            $query->where('tbl_stock_in.Product_ID', $request->product_id);
        }

        if ($request->filled('user_id')) {
            $query->where('tbl_stock_in.User_ID', $request->user_id);
        }

        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'attention':
                    $query->where('tbl_stock_in.Remaining_Quantity', '<=', 5);
                    break;
                case 'out':
                    $query->where('tbl_stock_in.Remaining_Quantity', '<=', 0);
                    break;
                case 'low':
                    $query->where('tbl_stock_in.Remaining_Quantity', '>', 0)
                        ->where('tbl_stock_in.Remaining_Quantity', '<=', 5);
                    break;
                case 'available':
                    $query->where('tbl_stock_in.Remaining_Quantity', '>', 5);
                    break;
            }
        }

        switch ($request->input('sort', 'stock_asc')) {
            case 'name_asc':
            case 'name_desc':
                $query->join('tbl_product', 'tbl_product.ID', '=', 'tbl_stock_in.Product_ID')
                    ->select('tbl_stock_in.*')
                    ->orderBy('tbl_product.Name', $request->sort === 'name_asc' ? 'asc' : 'desc');
                break;
            case 'stock_desc':
                $query->orderBy('tbl_stock_in.Remaining_Quantity', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('tbl_stock_in.Remaining_Quantity', 'asc');
                break;
            case 'price_asc':
            case 'price_desc':
                $query->orderBy('tbl_stock_in.Retail_Price', $request->sort === 'price_asc' ? 'asc' : 'desc');
                break;
            case 'newest':
            case 'oldest':
                break;
        }

        $query->orderBy('tbl_stock_in.ID', $request->sort === 'oldest' ? 'asc' : 'desc');

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->stockInQuery($request);

        $stockIns = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('Name', 'asc')->get();
        $products = Product::orderBy('Name', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();

        return view('stock_in.index', compact('stockIns', 'categories', 'products', 'users'));
    }

    public function print(Request $request)
    {
        $query = $this->stockInQuery($request);
        $stockIns = $query->get();
        return app(\App\Services\PrintableReport::class)->respond($request, 'stock_in.print', compact('stockIns'));
    }

    public function create(Request $request)
    {
        $archivedStatus = Status::where('Name', 'Archived')->first();

        $query = Product::with(['category', 'status', 'stockIns', 'soldItems']);

        if ($archivedStatus) {
            $query->where('Status_ID', '!=', $archivedStatus->ID);
        }

        $products = $query->orderBy('Name', 'asc')->get();
        $categories = Category::active()->orderBy('Name', 'asc')->get();

        $selectedProductId = $request->input('product_id') ?: old('Product_ID');
        $preselectedProduct = $selectedProductId ? $products->firstWhere('ID', (int) $selectedProductId) : null;

        return view('stock_in.create', compact('products', 'categories', 'preselectedProduct'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_mode' => 'required|in:existing,new',
            'Product_ID' => 'required_if:product_mode,existing|nullable|exists:tbl_product,ID',
            'New_Product_Name' => 'required_if:product_mode,new|nullable|string|max:255|unique:tbl_product,Name',
            'New_Product_Description' => 'nullable|string|max:2000',
            'New_Category_ID' => ['required_if:product_mode,new', 'nullable', Rule::exists('tbl_category', 'ID')->where(fn ($query) => $query->where('Is_Archived', false)->orWhereNull('Is_Archived'))],
            'images' => 'exclude_unless:product_mode,new|nullable|array|max:5',
            'images.*' => 'exclude_unless:product_mode,new|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'Quantity' => 'required|numeric|min:1',
            'Cost_Price' => 'required|numeric|min:0',
            'Retail_Price' => 'required|numeric|min:0',
            'Has_Expiration' => 'nullable|boolean',
            'Expiration_Date' => 'nullable|required_if:Has_Expiration,1|date|after_or_equal:today',
            'Condition' => 'nullable|in:Good,Damaged,Defective',
        ]);

        $imagePaths = [];

        try {
            return DB::transaction(function () use ($request, $validated, &$imagePaths) {
                $processor = $request->user();

                if ($validated['product_mode'] === 'new') {
                    $activeStatus = Status::firstOrCreate(['Name' => 'Active']);

                    foreach ($request->file('images', []) as $file) {
                        $destination = public_path('uploads/products');
                        File::ensureDirectoryExists($destination);
                        $filename = Str::uuid().'.'.$file->extension();
                        $file->move($destination, $filename);
                        $imagePaths[] = 'uploads/products/'.$filename;
                    }

                    $product = Product::create([
                        'Name' => $validated['New_Product_Name'],
                        'Description' => $validated['New_Product_Description'] ?? null,
                        'Category_ID' => $validated['New_Category_ID'],
                        'Status_ID' => $activeStatus->ID,
                        'Image' => $imagePaths[0] ?? null,
                        'Images' => $imagePaths,
                    ]);
                } else {
                    $product = Product::findOrFail($validated['Product_ID']);
                }

                $hasExpiration = $request->boolean('Has_Expiration');

                $stockIn = StockIn::create([
                    'Product_ID' => $product->ID,
                    'User_ID' => $processor->id,
                    'Quantity' => $validated['Quantity'],
                    'Remaining_Quantity' => $validated['Quantity'],
                    'Cost_Price' => $validated['Cost_Price'],
                    'Retail_Price' => $validated['Retail_Price'],
                    'Has_Expiration' => $hasExpiration,
                    'Expiration_Date' => $hasExpiration
                        ? ($validated['Expiration_Date'] ?? null)
                        : null,
                    'Condition' => $validated['Condition'] ?? 'Good',
                ]);

                $processorName = $processor->name ?? 'Staff';
                $processorRole = $processor->role ?? 'Staff';

                Log::info(
                    "Stock-In #{$stockIn->ID}: Received {$stockIn->Quantity} units of ".
                    "'{$product->Name}' processed by {$processorName} ({$processorRole})"
                );

                $redirectResponse = $request->input('redirect_to') === 'products'
                    ? redirect()->route('products.index')
                    : redirect()->route('stock-in.index');

                return $redirectResponse->with(
                    'success',
                    "Stock-In batch #SI-{$stockIn->ID} recorded successfully ".
                    "(+{$stockIn->Quantity} units of {$product->Name})!"
                );
            });
        } catch (\Throwable $exception) {
            File::delete(array_map(fn ($path) => public_path($path), $imagePaths));

            throw $exception;
        }
    }
}
