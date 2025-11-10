<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Handle unauthenticated requests.
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }
    }


}
