<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * List all active categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

    /**
     * Get single category with products.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->with(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => $category,
        ]);
    }
}
