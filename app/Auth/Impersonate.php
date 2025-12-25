<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use Lunar\Hub\Auth\Impersonate as LunarImpersonate;

/**
 * Custom impersonation handler for Lunar customers.
 * 
 * Allows admins to impersonate customers via signed URLs.
 * See: https://docs.lunarphp.com/1.x/reference/customers#impersonating-users
 */
class Impersonate extends LunarImpersonate
{
    /**
     * Return the URL for impersonation.
     * 
     * Creates a temporary signed route that allows an admin to log in as the user.
     * 
     * @param Authenticatable $authenticatable
     * @return string
     */
    public function getUrl(Authenticatable $authenticatable): string
    {
        return URL::temporarySignedRoute(
            'impersonate.link',
            now()->addMinutes(5),
            [
                'user' => $authenticatable->getAuthIdentifier(),
            ]
        );
    }
}


