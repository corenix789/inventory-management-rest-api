<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission, string ...$additionalPermissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $permissions = array_map('trim', [$permission, ...$additionalPermissions]);

        if (count($permissions) === 1 && ! str_contains($permission, '.')) {
            $permissions = [$permission.'.'.$this->actionFor($request)];
        }

        foreach ($permissions as $requiredPermission) {
            if ($user->hasPermission($requiredPermission)) {
                return $next($request);
            }
        }

        abort(403, 'Forbidden. Required permission: '.implode(' or ', $permissions));
    }

    private function actionFor(Request $request): string
    {
        return match ($request->route()?->getActionMethod()) {
            'index', 'permissions' => 'view',
            'show' => 'view-details',
            'store' => 'create',
            'update' => 'edit',
            'destroy', 'forceDelete' => 'delete',
            'restore' => 'edit',
            default => throw new \LogicException('No permission mapping exists for this route action.'),
        };
    }
}
