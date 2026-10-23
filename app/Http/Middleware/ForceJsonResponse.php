<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Laravel decides JSON vs HTML/redirect from the Accept header.
        // Forcing it means clients that forget the header still get JSON.
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
