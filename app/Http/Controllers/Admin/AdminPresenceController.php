<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPresenceController extends Controller
{
    public function heartbeat(Request $request, AdminPresenceService $presence): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $presence->markOnline($admin);

        return response()->json([
            'ok' => true,
            'admin_id' => (string) $admin->getKey(),
            'heartbeat_seconds' => $presence->heartbeatSeconds(),
            'ttl_seconds' => $presence->ttlSeconds(),
        ]);
    }

    public function offline(Request $request, AdminPresenceService $presence): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $presence->markOffline($admin);

        return response()->json([
            'ok' => true,
            'admin_id' => (string) $admin->getKey(),
            'last_seen_at' => $presence->lastSeenTimestamps([(string) $admin->getKey()])[(string) $admin->getKey()] ?? null,
        ]);
    }

    public function statuses(Request $request, AdminPresenceService $presence): JsonResponse
    {
        $adminIds = collect($request->input('ids', []))
            ->map(fn (mixed $adminId): string => trim((string) $adminId))
            ->filter()
            ->take(100)
            ->values()
            ->all();

        return response()->json([
            'statuses' => $presence->statuses($adminIds),
            'last_seen_at' => $presence->lastSeenTimestamps($adminIds),
            'server_time' => now()->toISOString(),
            'ttl_seconds' => $presence->ttlSeconds(),
        ]);
    }
}
