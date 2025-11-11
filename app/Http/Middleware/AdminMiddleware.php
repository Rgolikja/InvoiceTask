<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {

        //kontrollojm nese nje user esht logged in ose esht admin
        if (!Auth::check()) {
            Log::warning('Unauthorized access no token', [
                'path' => $request->path(),
                'ip' => $request->ip,
            ]);
            return response()->json([
                'error' => 'unauthenticated'
            ], 401);
        }


        if ((Auth()->user()->role !== 'admin')) {
            Log::warning('not an admin', [
                'user' => auth()->user()->username,
                'path' => $request->path(),
            ]);
            return response()->json([
                'error' => 'admin only'
            ], 403);
        }
        return $next($request);
    }
}
