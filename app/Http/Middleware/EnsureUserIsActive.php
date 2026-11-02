<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Deactivated accounts are refused on EVERY authenticated request.
     * (Logout stays reachable since it lives on the guest side.)
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->is_active, 403, 'Account deactivated. Contact your administrator.');

        return $next($request);
    }
}
