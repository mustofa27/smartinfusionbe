<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if (! $request->expectsJson()) {
                return redirect()->route('admin.login');
            }

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'super-admin') {
            if (! $request->expectsJson()) {
                abort(403, 'Super-admin role required.');
            }

            return response()->json([
                'message' => 'Super-admin role required.',
            ], 403);
        }

        return $next($request);
    }
}