<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List products with category, search, and sorting filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images', 'variants'])
            ->where('is_active', true);

        // Filter by category slug or id
        if ($request->filled('category')) {
            $cat = $request->input('category');
            if ($cat !== 'semua' && $cat !== 'all') {
                $query->where(function ($q) use ($cat) {
                    $q->whereHas('category', function ($sub) use ($cat) {
                        $sub->where('slug', $cat)->orWhere('id', $cat);
                    })->orWhere('category_name', 'like', "%{$cat}%");
                });
            }
        }

        // Search query
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('subtitle', 'like', "%{$term}%")
                    ->orWhere('wood_type', 'like', "%{$term}%");
            });
        }

        // Featured filter
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc'),
        };

        $products = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => $products,
        ]);
    }

    /**
     * Get single product by slug or id.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $product = Product::with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->where(function ($q) use ($idOrSlug) {
                $q->where('slug', $idOrSlug)
                    ->orWhere('id', $idOrSlug);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product details retrieved successfully.',
            'data' => $product,
        ]);
    }
}
