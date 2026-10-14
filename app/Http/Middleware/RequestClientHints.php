<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestClientHints
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $hints = array_filter(array_map('trim', explode(',', $response->headers->get('Accept-CH', ''))));
        $hints[] = 'Sec-CH-UA-Platform-Version';
        $response->headers->set('Accept-CH', implode(', ', array_unique($hints)));

        return $response;
    }
}
