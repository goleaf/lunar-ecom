<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\StockNotificationMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for tracking stock notification email metrics.
 */
class StockNotificationTrackingController extends Controller
{
    /**
     * Track email open (via tracking pixel).
     *
     * @param  Request  $request
     * @param  int  $metricId
     * @return Response
     */
    public function trackOpen(Request $request, int $metricId): Response
    {
        $metric = StockNotificationMetric::find($metricId);
        
        if ($metric) {
            $metric->trackEmailOpen();
        }

        // Return 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Track link click.
     *
     * @param  Request  $request
     * @param  int  $metricId
     * @param  string  $linkType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function trackClick(Request $request, int $metricId, string $linkType)
    {
        $metric = StockNotificationMetric::find($metricId);
        
        if ($metric) {
            $metric->trackLinkClick($linkType);
        }

        // Get redirect URL based on link type
        $variant = $metric->productVariant ?? null;
        $product = $variant?->product ?? null;

        switch ($linkType) {
            case 'buy_now':
                // Redirect to cart with variant
                $url = $variant ? url('/cart/add?variant_id=' . $variant->id) : url('/');
                break;
            case 'product_page':
                $url = $product ? ($product->urls->first()?->url ?? url('/products/' . $product->id)) : url('/');
                break;
            case 'unsubscribe':
                $notification = \App\Models\StockNotification::find($metric->stock_notification_id);
                $url = $notification ? url('/stock-notifications/unsubscribe/' . $notification->token) : url('/');
                break;
            default:
                $url = url('/');
        }

        return redirect($url);
    }
}


