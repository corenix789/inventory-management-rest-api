<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Usage in routes:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,manager')          // any of these roles
 *   ->middleware('permission:products.create')
 *   ->middleware('permission:products.create,products.edit')  // any of these permissions
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $type  'role' or 'permission'
     * @param  string  $value Comma-separated list of roles/permissions (any match grants access)
     */
    public function handle(Request $request, Closure $next, string $type = 'role', string $value = ''): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $allowed = array_map('trim', explode(',', $value));

        if ($type === 'role') {
            // Check if user has any of the required roles
            $hasRole = $user->roles()
                            ->whereIn('slug', $allowed)
                            ->orWhereIn('name', $allowed)
                            ->exists();

            if (! $hasRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Required role: ' . implode(' or ', $allowed),
                ], 403);
            }
        } elseif ($type === 'permission') {
            // Check if user has any of the required permissions (through their roles)
            $hasPermission = false;

            foreach ($allowed as $perm) {
                if ($user->hasPermission($perm)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (! $hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Required permission: ' . implode(' or ', $allowed),
                ], 403);
            }
        }

        return $next($request);
    }
}
