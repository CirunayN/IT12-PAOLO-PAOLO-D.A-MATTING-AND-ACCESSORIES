<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(['categories' => Category::withCount('products')
            ->orderBy('Is_Archived')->orderBy('Name')->get()
            ->map(fn ($category) => $this->categoryData($category))->values()]);
    }

    public function store(Request $request)
    {
        $category = Category::create($this->validateName($request));

        return $this->respond($request, $category, 'created');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validateName($request, $category));

        return $this->respond($request, $category, 'updated');
    }

    public function archive(Request $request, Category $category)
    {
        $category->update(['Is_Archived' => true]);

        return $this->respond($request, $category, 'archived');
    }

    public function restore(Request $request, Category $category)
    {
        $category->update(['Is_Archived' => false]);

        return $this->respond($request, $category, 'restored');
    }

    private function validateName(Request $request, ?Category $category = null): array
    {
        if (is_string($request->input('Name'))) {
            $request->merge(['Name' => trim($request->input('Name'))]);
        }

        return $request->validate(['Name' => ['required', 'string', 'max:100',
            Rule::unique('tbl_category', 'Name')->ignore($category?->ID, 'ID')]]);
    }

    private function categoryData(Category $category): array
    {
        return ['id' => $category->ID, 'name' => $category->Name,
            'is_archived' => (bool) $category->Is_Archived, 'products_count' => (int) $category->products_count,
            'update_url' => route('categories.update', $category),
            'archive_url' => route('categories.archive', $category),
            'restore_url' => route('categories.restore', $category),
            'products_url' => route('products.index', ['tab' => 'all', 'category_id' => $category->ID])];
    }

    private function respond(Request $request, Category $category, string $action)
    {
        $category->refresh()->loadCount('products');
        $message = "Category '{$category->Name}' {$action} successfully!";
        Log::info($message, ['user_id' => $request->user()->id, 'category_id' => $category->ID]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'category' => $this->categoryData($category)]);
        }

        return back()->with('success', $message);
    }
}
