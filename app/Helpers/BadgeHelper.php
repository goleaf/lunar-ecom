<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\ProductBadgeAssignment;
use App\Services\BadgeService;

class BadgeHelper
{
    /**
     * Get badges HTML for a product.
     *
     * @param  Product  $product
     * @param  string|null  $context
     * @param  string  $wrapperClass
     * @return string
     */
    public static function render(Product $product, ?string $context = null, string $wrapperClass = 'product-badges'): string
    {
        $badgeService = app(BadgeService::class);
        $assignments = $badgeService->getProductBadges($product, $context);

        if ($assignments->isEmpty()) {
            return '';
        }

        $html = '<div class="' . $wrapperClass . '">';
        
        foreach ($assignments as $assignment) {
            $badge = $assignment->badge;
            $html .= static::renderBadge($badge, $assignment);
        }
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single badge.
     *
     * @param  \App\Models\ProductBadge  $badge
     * @param  ProductBadgeAssignment  $assignment
     * @return string
     */
    public static function renderBadge($badge, ProductBadgeAssignment $assignment): string
    {
        $position = $assignment->display_position ?? $badge->position;
        $styles = $badge->getInlineStyles();
        $classes = $badge->getCssClasses() . ' badge-position-' . $position;
        $label = $badge->getDisplayLabel();

        $html = '<span class="' . $classes . '" style="' . $styles . '" data-badge-id="' . $badge->id . '">';
        
        if ($badge->show_icon && $badge->icon) {
            $html .= '<i class="' . $badge->icon . '"></i> ';
        }
        
        $html .= htmlspecialchars($label);
        $html .= '</span>';

        return $html;
    }

    /**
     * Get badges as array for JSON/API responses.
     *
     * @param  Product  $product
     * @param  string|null  $context
     * @return array
     */
    public static function toArray(Product $product, ?string $context = null): array
    {
        $badgeService = app(BadgeService::class);
        $assignments = $badgeService->getProductBadges($product, $context);

        return $assignments->map(function ($assignment) {
            $badge = $assignment->badge;
            
            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'label' => $badge->getDisplayLabel(),
                'type' => $badge->type,
                'position' => $assignment->display_position ?? $badge->position,
                'styles' => $badge->getInlineStyles(),
                'classes' => $badge->getCssClasses(),
                'icon' => $badge->icon,
                'show_icon' => $badge->show_icon,
            ];
        })->toArray();
    }
}

