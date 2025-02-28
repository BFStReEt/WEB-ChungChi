<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

use App\Models\Category;

class CategoryController extends Controller
{
    public function show()
    {
        try {
            $categories = Category::where('parent_id', null)
                ->with('childrenRecursive')  
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request){
        try {
            $parent_categories = Category::whereNull('parent_id')->get();

            return response()->json([
                'status' => true,
                'parent_categories' => $parent_categories,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}