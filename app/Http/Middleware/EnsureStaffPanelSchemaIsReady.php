<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPanelSchemaIsReady
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->schemaIsReady()) {
            return $next($request);
        }

        $this->forgetRememberCookie();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Staff panel setup is incomplete. Run php artisan migrate and php artisan staff:seed --password=YOUR_PASSWORD first.',
            ], 503);
        }

        return response()->view('errors.staff-setup-required', [
            'loginPath' => '/'.trim((string) config('staff.path', 'staff'), '/').'/login',
        ], 503);
    }

    private function schemaIsReady(): bool
    {
        try {
            return Schema::hasTable('staff_users')
                && Schema::hasTable('scanner_stations');
        } catch (\Throwable) {
            return false;
        }
    }

    private function forgetRememberCookie(): void
    {
        $guard = Auth::guard('staff');

        if (! $guard instanceof SessionGuard) {
            return;
        }

        Cookie::queue(Cookie::forget($guard->getRecallerName()));
    }
}
