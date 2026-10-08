<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get or generate a session token from request.
     */
    protected function getSessionToken(Request $request): string
    {
        $token = $request->header('X-Cart-Session');
        if (! $token || strlen($token) < 10) {
            $token = $request->input('session_token');
        }
        return $token ?: Str::random(32);
    }

    /**
     * Get cart items.
     */
    public function index(Request $request): JsonResponse
    {
        $token = $this->getSessionToken($request);
        $userId = $request->user()?->id;

        $items = CartItem::with(['product', 'variant'])
            ->where(function ($q) use ($token, $userId) {
                $q->where('session_token', $token);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->get();

        $totalAmount = $items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $totalCount = $items->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully.',
            'data' => [
                'session_token' => $token,
                'items' => $items,
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
            ],
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'integer|min:1|max:50',
            'session_token' => 'nullable|string',
        ]);

        $token = $this->getSessionToken($request);
        $userId = $request->user()?->id;
        $product = Product::findOrFail($validated['product_id']);
        $quantity = $validated['quantity'] ?? 1;

        $unitPrice = $product->price;

        // Check if item already exists in cart
        $cartItem = CartItem::where('product_id', $product->id)
            ->where(function ($q) use ($token, $userId) {
                $q->where('session_token', $token);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'session_token' => $token,
                'user_id' => $userId,
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        $cartItem->load(['product', 'variant']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart.',
            'data' => [
                'item' => $cartItem,
                'session_token' => $token,
            ],
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
        ]);

        $cartItem = CartItem::findOrFail($id);
        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();
        $cartItem->load(['product', 'variant']);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated.',
            'data' => $cartItem,
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(int $id): JsonResponse
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
        ]);
    }

    /**
     * Clear all items in cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $token = $this->getSessionToken($request);
        $userId = $request->user()?->id;

        CartItem::where(function ($q) use ($token, $userId) {
            $q->where('session_token', $token);
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        })->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared.',
        ]);
    }
}
