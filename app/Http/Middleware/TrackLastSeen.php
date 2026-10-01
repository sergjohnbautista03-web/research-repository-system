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
            $user = Auth::user();

            // Keep admin staff sessions available even when there is no open semester.
            if ($user->isDeanImportedMember() && ! $user->hasActiveSemesterEnrollment()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Your account is not activated for the current semester. Please contact your Department Dean to activate your account.']);
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
