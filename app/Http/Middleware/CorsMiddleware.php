<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $allowedOrigins = explode(',', env('ACCESS_CONTROL_ALLOW_ORIGIN'));

        if (in_array($request->header('origin'), $allowedOrigins)) {
            $origin = $request->header('origin');
        } else {
            $origin = env('ACCESS_CONTROL_ALLOW_ORIGIN_SPECIFIC');
        }

        //$origin = $_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : 'https://canonizer.com';

        $headers = [
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'false',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, X-Requested-With,Authorization'
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            if (strpos($request->url(), 'api/v1')) {
                $response->header($key, $value);
            }
        }

        return $response;
    }
}
