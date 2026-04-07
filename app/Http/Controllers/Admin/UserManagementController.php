<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateUserRequest;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminUserManagementSyncStatusFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly AdminPanelService $adminPanel,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'q',
            'page',
            'per_page',
            'country',
            'identity_type',
            'verification_status',
            'attendance_status',
            'email_typo',
        ]);
        $pageData = $this->adminPanel->userManagementPage($filters);

        return view('admin.users.index', [
            'users' => $pageData['users'],
            'overview' => $pageData['overview'],
            'filterOptions' => $pageData['filter_options'],
            'filters' => $filters,
            'syncStatus' => is_array($pageData['sync_status'] ?? null)
                ? $pageData['sync_status']
                : app(AdminUserManagementSyncStatusFactory::class)->live('legacy_request'),
            'firestoreAvailable' => $this->adminPanel->firestoreAvailable(),
        ]);
    }

    public function edit(string $userId): View|Response
    {
        $user = $this->adminPanel->findUser($userId);

        if ($user === null) {
            abort(404);
        }

        return view('admin.users.edit', [
            'user' => $user,
            'syncStatus' => app(AdminUserManagementSyncStatusFactory::class)->live('live_document'),
            'firestoreAvailable' => $this->adminPanel->firestoreAvailable(),
        ]);
    }

    public function update(AdminUpdateUserRequest $request, string $userId): RedirectResponse
    {
        try {
            $user = $this->adminPanel->updateUserByAdmin($userId, $request->validated());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['error' => $exception->getMessage()]);
        }

        $this->auditLogger->log(
            auth('admin')->user(),
            'user_edit',
            'user',
            $userId,
            [
                'email' => $user['email'],
                'account_status' => $user['account_status'],
                'verification_status' => $user['verification_status'],
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.users.edit', $userId)
            ->with('status', 'Participant data was updated successfully.');
    }

    public function destroy(Request $request, string $userId): RedirectResponse
    {
        try {
            $result = $this->adminPanel->deleteUserByAdmin($userId);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        $this->auditLogger->log(
            auth('admin')->user(),
            'user_delete',
            'user',
            $userId,
            [
                'full_name' => data_get($result, 'user.full_name'),
                'email' => data_get($result, 'user.email'),
                'ticket_id' => data_get($result, 'ticket.ticket_id', data_get($result, 'user.ticket_id')),
                'ticket_code' => data_get($result, 'ticket.ticket_code'),
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Participant was deleted successfully.');
    }

    public function resetQr(Request $request, string $userId): RedirectResponse
    {
        try {
            $result = $this->adminPanel->resetQrCode($userId);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        $this->auditLogger->log(
            auth('admin')->user(),
            'qr_reset',
            'ticket',
            (string) ($result['ticket']['ticket_id'] ?? $userId),
            [
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
            ],
            $request->ip(),
        );

        return back()->with('status', 'The participant QR and attendance status were reset successfully.');
    }

    public function regenerateQr(Request $request, string $userId): RedirectResponse
    {
        try {
            $result = $this->adminPanel->regenerateQrCode($userId);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }

        $this->auditLogger->log(
            auth('admin')->user(),
            'qr_regenerate',
            'ticket',
            (string) ($result['ticket']['ticket_id'] ?? $userId),
            [
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
                'qr_version' => $result['ticket']['qr_version'] ?? null,
            ],
            $request->ip(),
        );

        return back()->with('status', 'The participant QR code was regenerated successfully.');
    }
}
