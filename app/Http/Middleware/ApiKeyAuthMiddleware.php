<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Middleware to validate X-API-KEY header against API_POPULATE_KEY environment variable.
 */
class ApiKeyAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedKey = (string) config('services.tasks.populate_key');

        if (!$apiKey || $expectedKey === '' || $apiKey !== $expectedKey) {
            return response()->json(['message' => 'Invalid API key'], 403);
        }

        return $next($request);
    }
}
