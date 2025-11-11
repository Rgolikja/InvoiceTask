<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For APIs, return null so Laravel returns a 401 JSON instead of redirecting.
     */
    protected function redirectTo($request): ?string
    {
        // If request expects JSON, return null (prevents redirect)
        if (!$request->expectsJson()) {
            abort(response()->json(
                [
                    'error' => 'Unauthenticated use valid token'
                ],
                401

            ));
        }


    }
}
