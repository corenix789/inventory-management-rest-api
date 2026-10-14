<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Usage: ->middleware('role:admin,inventory-manager')
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $role  Required role slugs or names (any match grants access)
     */
    public function handle(Request $request, Closure $next, string $role, string ...$additionalRoles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $allowed = array_map('trim', [$role, ...$additionalRoles]);

        if (! $user->hasAnyRole($allowed)) {
            abort(403, 'Forbidden. Required role: '.implode(' or ', $allowed));
        }

        return $next($request);
    }
}
