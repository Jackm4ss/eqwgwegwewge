<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffScannerPostRequest;
use App\Models\Admin;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;

class StaffScannerSessionController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json([
            'user' => [
                'id' => (string) $admin->getKey(),
                'name' => (string) $admin->name,
                'email' => (string) $admin->email,
                'role' => (string) $admin->role,
            ],
            'scanner_post' => session((string) config('scanner.session_post_key', 'staff.scanner_post')),
            'posts' => array_values(config('scanner.posts', ['Gate A'])),
            'available_posts' => array_values(config('scanner.posts', ['Gate A'])),
        ]);
    }

    public function updatePost(StaffScannerPostRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $scannerPost = (string) $request->validated('scanner_post');
        session([(string) config('scanner.session_post_key', 'staff.scanner_post') => $scannerPost]);

        $this->auditLogger->log(
            $admin,
            'scanner_post_selected',
            'staff',
            (string) $admin->getKey(),
            ['scanner_post' => $scannerPost],
            $request->ip(),
        );

        return response()->json([
            'message' => 'Scanner post selected.',
            'scanner_post' => $scannerPost,
        ]);
    }
}
