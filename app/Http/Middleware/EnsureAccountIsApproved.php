<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isApproved()) {
            return response()->json([
                'message' => $user?->isPending()
                    ? 'Your account is still under review.'
                    : 'Your account is not approved for access.',
                'status' => $user?->status,
            ], 403);
        }

        return $next($request);
    }
}
