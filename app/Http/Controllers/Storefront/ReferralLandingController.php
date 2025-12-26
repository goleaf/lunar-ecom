<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReferralLandingController extends Controller
{
    /**
     * Redirect /r/{code} to /{resolvedLocale}/r/{code}.
     */
    public function redirectToLocalized(Request $request, string $code)
    {
        $locale = app()->getLocale();

        return redirect()->route('frontend.referrals.landing', [
            'locale' => $locale,
            'code' => $code,
        ] + $request->query());
    }
}


