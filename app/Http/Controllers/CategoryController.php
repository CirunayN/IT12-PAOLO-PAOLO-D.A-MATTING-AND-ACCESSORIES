<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:tbl_category,Name',
        ]);

        $category = Category::create([
            'Name' => trim($validated['Name']),
        ]);

        $userName = auth()->user()->name ?? 'System';
        $userRole = auth()->user()->role ?? 'Staff';
        Log::info("Category '{$category->Name}' created by {$userName} ({$userRole})");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->Name}' created successfully!",
                'category' => [
                    'id' => $category->ID,
                    'name' => $category->Name,
                ],
            ]);
        }

        return back()->with('success', "Category '{$category->Name}' created successfully!");
    }
}
