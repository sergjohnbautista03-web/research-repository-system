<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = 'last_seen_' . $userId;

            if (!Cache::has($cacheKey)) {
                Auth::user()->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, now()->addMinutes(1)); // was 2
            }
        }

        return $next($request);
    }
}