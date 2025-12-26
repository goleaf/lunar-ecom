<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Dynamic robots.txt controller.
 */
class RobotsController extends Controller
{
    /**
     * Generate robots.txt dynamically.
     * 
     * @return Response
     */
    public function index()
    {
        $siteUrl = config('app.url');
        
        $content = <<<ROBOTS
# Robots.txt for Lunar E-commerce Store
# https://www.robotstxt.org/

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /checkout/
Disallow: /cart/
Disallow: /api/
Disallow: /search-analytics/
Disallow: /*?*sort=*
Disallow: /*?*filter=*

# Sitemap location
Sitemap: {$siteUrl}/sitemap/

# Crawl-delay (optional, adjust as needed)
# Crawl-delay: 1

# Specific bot rules
User-agent: Googlebot
Allow: /

User-agent: Bingbot
Allow: /

# Block bad bots (optional)
# User-agent: BadBot
# Disallow: /
ROBOTS;

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}

