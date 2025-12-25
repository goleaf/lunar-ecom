<?php

namespace App\Http\Controllers;

use App\Contracts\CartManagerInterface;
use App\Services\CartSessionService;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        protected CartManagerInterface $cartManager,
        protected CartSessionService $cartSession
    ) {}

    /**
     * Get current cart
     */
    public function show(): JsonResponse
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            return response()->json(['message' => 'No active cart'], 404);
        }

        return response()->json([
            'cart' => $cart->load(['lines.purchasable', 'currency']),
            'item_count' => $this->cartManager->getItemCount(),
        ]);
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:lunar_product_variants,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);
        
        // Ensure user can view this variant (can only add purchasable variants to cart)
        $this->authorize('view', $variant);
        
        // Check if variant is purchasable
        if (!$variant->purchasable) {
            return response()->json(['error' => 'This item is not available for purchase'], 400);
        }
        
        try {
            $cartLine = $this->cartManager->addItem($variant, $request->quantity);
            
            return response()->json([
                'message' => 'Item added to cart',
                'cart_line' => $cartLine->load('purchasable'),
                'item_count' => $this->cartManager->getItemCount(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add item to cart'], 500);
        }
    }

    /**
     * Update cart line quantity
     */
    public function updateQuantity(Request $request, int $lineId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:999',
        ]);

        try {
            $this->cartManager->updateQuantity($lineId, $request->quantity);
            
            return response()->json([
                'message' => $request->quantity > 0 ? 'Cart updated' : 'Item removed from cart',
                'item_count' => $this->cartManager->getItemCount(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update cart'], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $lineId): JsonResponse
    {
        try {
            $this->cartManager->removeItem($lineId);
            
            return response()->json([
                'message' => 'Item removed from cart',
                'item_count' => $this->cartManager->getItemCount(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to remove item from cart'], 500);
        }
    }

    /**
     * Apply discount code
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        try {
            $this->cartManager->applyDiscount($request->coupon_code);
            
            return response()->json([
                'message' => 'Discount applied',
                'cart' => $this->cartSession->current()->load(['lines.purchasable', 'currency']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to apply discount'], 500);
        }
    }

    /**
     * Clear cart
     */
    public function clear(): JsonResponse
    {
        try {
            $this->cartManager->clear();
            
            return response()->json(['message' => 'Cart cleared']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove discount from cart
     */
    public function removeDiscount(): JsonResponse
    {
        try {
            $this->cartManager->removeDiscount();
            
            return response()->json([
                'message' => 'Discount removed',
                'cart' => $this->cartSession->current()->load(['lines.purchasable', 'currency']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get cart item count
     */
    public function getItemCount(): JsonResponse
    {
        return response()->json([
            'item_count' => $this->cartManager->getItemCount(),
        ]);
    }

    /**
     * Get cart total
     */
    public function getTotal(): JsonResponse
    {
        return response()->json([
            'total' => $this->cartManager->getTotal(),
            'has_items' => $this->cartManager->hasItems(),
        ]);
    }
}