<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
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

        if (! in_array($user->role, ['admin', 'super-admin'], true)) {
            if (! $request->expectsJson()) {
                abort(403, 'Admin or super-admin role required.');
            }

            return response()->json([
                'message' => 'Admin or super-admin role required.',
            ], 403);
        }

        return $next($request);
    }
}
