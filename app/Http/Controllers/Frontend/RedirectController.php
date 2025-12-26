<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\RedirectService;
use Illuminate\Http\Request;

/**
 * Controller for handling URL redirects.
 */
class RedirectController extends Controller
{
    /**
     * Handle redirect request.
     *
     * @param  Request  $request
     * @param  RedirectService  $redirectService
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request, RedirectService $redirectService)
    {
        $redirect = $redirectService->handleRedirect($request);

        if ($redirect) {
            return $redirect;
        }

        abort(404);
    }
}


