<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if ($admin === null) {
            abort(403);
        }

        $normalizedRoles = array_values(array_filter(array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles,
        )));
        $currentRole = strtolower((string) $admin->role);

        if ($normalizedRoles === [] || in_array($currentRole, $normalizedRoles, true)) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $isStaffApiRoute = str_starts_with($routeName, 'staff.');

        if ($request->expectsJson() || $isStaffApiRoute) {
            return new JsonResponse([
                'message' => 'You are not allowed to access this area.',
            ], 403);
        }

        $redirect = $currentRole === 'scanner'
            ? route('staff.home')
            : route('admin.dashboard');

        return new RedirectResponse($redirect);
    }
}
