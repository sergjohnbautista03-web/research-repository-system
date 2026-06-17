<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRepositoryPolicyAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (
            $request->routeIs('policy.show')
            || $request->routeIs('policy.accept')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        $currentVersion = config('repository_policy.version', '2026-04-29');
        $hasAcceptedCurrentPolicy = $user->policy_accepted_at
            && $user->policy_version === $currentVersion;

        if (! $hasAcceptedCurrentPolicy) {
            return redirect()->guest(route('policy.show'));
        }

        return $next($request);
    }
}
