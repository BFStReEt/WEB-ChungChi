<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryMidelleware
{

    public function handle(Request $request, Closure $next, $action = 'manage')
    {
        $categoryId = $request->route('categoryId') ?? $request->input('categoryId');
        
        if (!$categoryId) {
            return response()->json([
                'status' => false,
                'message' => 'Category ID is required'
            ], 400);
        }

        try {
            $category = Category::findOrFail($categoryId);
            
            $slugPath = [];
            $currentCategory = $category;
            
            while ($currentCategory) {
                array_unshift($slugPath, $currentCategory->name);
                $currentCategory = $currentCategory->parent;
            }
            
            $permissionSlug = implode('.', $slugPath) . '.' . $action;
            
            if (!Gate::allows($permissionSlug)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No permission to ' . $action . ' in this category',
                    'required_permission' => $permissionSlug
                ], 403);
            }
            
            $request->merge(['category' => $category]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }
}