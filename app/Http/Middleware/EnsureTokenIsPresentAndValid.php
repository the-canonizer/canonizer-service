<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsPresentAndValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('X-Api-Token') && $request->header('X-Api-Token') == env('API_TOKEN')) {
            return $next($request);
        }
        
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
