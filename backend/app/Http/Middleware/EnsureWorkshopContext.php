<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkshopContext
{
    /**
     * Handle an incoming request and guarantee a valid active workshop tenant context.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => new \stdClass(),
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User account is inactive.',
                'errors' => new \stdClass(),
            ], 401);
        }

        if (! $user->workshop_id || ! $user->workshop) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to a valid workshop.',
                'errors' => new \stdClass(),
            ], 403);
        }

        // Attach tenant context to request attributes for request-scoped access
        $request->attributes->set('workshop', $user->workshop);
        $request->attributes->set('workshop_id', $user->workshop->id);

        return $next($request);
    }
}
