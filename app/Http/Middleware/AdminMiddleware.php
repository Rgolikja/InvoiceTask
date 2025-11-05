<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        //kontrollojm nese nje user esht logged in ose esht admin
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Access denied admin only'
            ], 403);
        }

        return $next($request);
    }
}
