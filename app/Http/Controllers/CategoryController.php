<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

use App\Models\Category;

class CategoryController extends Controller
{
    public function show(Request $request){
    try {
        $categories = Category::pluck('name');

        // Log the categories for debugging
        \Log::info('Categories:', $categories->toArray());

        $allowedCategories = $categories->filter(function ($category) {
            $hasPermission = Gate::allows($category . '.manage');
            // Log the permission check for each category
            \Log::info('Checking permission for category: ' . $category, ['hasPermission' => $hasPermission]);
            return $hasPermission;
        });

        // Log the allowed categories for debugging
        \Log::info('Allowed Categories:', $allowedCategories->toArray());

        return response()->json([
            'status' => true,
            'categories' => $allowedCategories,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}
}