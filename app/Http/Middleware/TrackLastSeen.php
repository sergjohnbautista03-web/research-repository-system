<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isDeanImportedMember() && ! Semester::open()->exists()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Your account is not available yet because there is no active semester. Please wait for the administrator to activate a semester.']);
            }

            $userId = Auth::id();
            $cacheKey = 'last_seen_' . $userId;

            if (!Cache::has($cacheKey)) {
                $user->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, now()->addMinutes(1)); // was 2
            }
        }

        return $next($request);
    }
}
