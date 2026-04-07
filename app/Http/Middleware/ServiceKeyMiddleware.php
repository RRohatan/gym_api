<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $serviceKey = config('app.service_api_key');

        if (!$serviceKey || $request->header('X-Service-Key') !== $serviceKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
