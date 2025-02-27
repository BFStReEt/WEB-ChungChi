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

        $allowedCategories = $categories->filter(function ($category) {
            $hasPermission = Gate::allows($category . '.manage');
            return $hasPermission;
        });

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