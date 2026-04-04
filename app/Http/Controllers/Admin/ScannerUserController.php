<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStoreAdminRequest;
use App\Http\Requests\Admin\AdminUpdateAdminRequest;
use App\Models\Admin;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminPresenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ScannerUserController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'presence', 'page', 'per_page']);
        $status = $this->normalizeStatus((string) ($filters['status'] ?? 'all'));
        $presenceFilter = $this->normalizePresence((string) ($filters['presence'] ?? 'all'));
        $perPage = $this->sanitizePerPage((int) ($filters['per_page'] ?? 10));
        $search = trim((string) ($filters['q'] ?? ''));

        /** @var AdminPresenceService $presence */
        $presence = app(AdminPresenceService::class);

        $scannerDirectoryQuery = Admin::query()
            ->where('role', 'scanner')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $nestedQuery) use ($like): void {
                    $nestedQuery
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(
                $status === 'active',
                fn (Builder $query): Builder => $query->where('is_active', true)
            )
            ->when(
                $status === 'inactive',
                fn (Builder $query): Builder => $query->where('is_active', false)
            );

        if ($presenceFilter !== 'all') {
            $matchingScannerIds = collect($presence->statuses(
                (clone $scannerDirectoryQuery)->pluck('id')->all(),
            ))
                ->filter(fn (bool $isOnline): bool => $presenceFilter === 'online' ? $isOnline : ! $isOnline)
                ->keys()
                ->all();

            $scannerDirectoryQuery->when(
                $matchingScannerIds === [],
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                fn (Builder $query): Builder => $query->whereKey($matchingScannerIds),
            );
        }

        $scanners = $scannerDirectoryQuery
            ->orderByDesc('is_active')
            ->orderByDesc('last_login_at')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $scannerSummaryQuery = Admin::query()->where('role', 'scanner');
        $latestLoginScanner = (clone $scannerSummaryQuery)
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->first();

        $visibleScannerIds = $scanners->getCollection()
            ->map(fn (Admin $scanner): string => (string) $scanner->getKey())
            ->all();

        return view('admin.admin-users.index', [
            'admins' => $scanners,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'presence' => $presenceFilter,
                'per_page' => $perPage,
            ],
            'presence' => [
                'statuses' => $presence->statuses($visibleScannerIds),
                'heartbeat_seconds' => $presence->heartbeatSeconds(),
                'ttl_seconds' => $presence->ttlSeconds(),
                'statuses_url' => route('admin.presence.statuses'),
            ],
            'summary' => [
                'total' => (clone $scannerSummaryQuery)->count(),
                'active' => (clone $scannerSummaryQuery)->where('is_active', true)->count(),
                'inactive' => (clone $scannerSummaryQuery)->where('is_active', false)->count(),
                'latest_login_admin' => $latestLoginScanner,
            ],
            'management' => $this->managementConfig(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-users.create', [
            'management' => $this->managementConfig(),
        ]);
    }

    public function store(AdminStoreAdminRequest $request): RedirectResponse
    {
        $scannerUser = Admin::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => 'scanner',
            'is_active' => (bool) $request->validated('is_active'),
        ]);

        $this->auditLogger->log(
            auth('admin')->user(),
            'scanner_user_create',
            'scanner',
            (string) $scannerUser->getKey(),
            [
                'name' => $scannerUser->name,
                'email' => $scannerUser->email,
                'is_active' => $scannerUser->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.scanner-users.index')
            ->with('status', 'Scanner account was created successfully.');
    }

    public function edit(Admin $scannerUser): View
    {
        $this->abortIfNotManagedScanner($scannerUser);

        return view('admin.admin-users.edit', [
            'adminUser' => $scannerUser,
            'management' => $this->managementConfig(),
        ]);
    }

    public function update(AdminUpdateAdminRequest $request, Admin $scannerUser): RedirectResponse
    {
        $this->abortIfNotManagedScanner($scannerUser);

        $attributes = $request->validated();

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $scannerUser->fill($attributes);
        $scannerUser->save();

        $this->auditLogger->log(
            auth('admin')->user(),
            'scanner_user_update',
            'scanner',
            (string) $scannerUser->getKey(),
            [
                'name' => $scannerUser->name,
                'email' => $scannerUser->email,
                'is_active' => $scannerUser->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.scanner-users.index')
            ->with('status', 'Scanner account was updated successfully.');
    }

    public function destroy(Request $request, Admin $scannerUser): RedirectResponse
    {
        $this->abortIfNotManagedScanner($scannerUser);

        $currentAdmin = auth('admin')->user();

        if ($currentAdmin?->is($scannerUser)) {
            return redirect()
                ->route('admin.scanner-users.index')
                ->withErrors([
                    'error' => 'You cannot delete the account currently signed in.',
                ]);
        }

        $metadata = [
            'name' => $scannerUser->name,
            'email' => $scannerUser->email,
        ];

        try {
            $scannerUser->delete();
        } catch (\Throwable $exception) {
            throw new RuntimeException('The scanner account could not be deleted.', previous: $exception);
        }

        $this->auditLogger->log(
            $currentAdmin,
            'scanner_user_delete',
            'scanner',
            (string) $scannerUser->getKey(),
            $metadata,
            $request->ip(),
        );

        return redirect()
            ->route('admin.scanner-users.index')
            ->with('status', 'Scanner account was deleted successfully.');
    }

    private function managementConfig(): array
    {
        return [
            'badge' => 'Scanner Management',
            'list_title' => 'List User Scanner',
            'list_description' => '',
            'summary_total_label' => 'Total scanners',
            'index_route' => 'admin.scanner-users.index',
            'search_label' => 'Search scanner',
            'search_placeholder' => 'Search by scanner name or email',
            'directory_title' => 'Scanner Account Directory',
            'directory_count_noun' => 'scanner accounts',
            'create_route' => 'admin.scanner-users.create',
            'create_button_label' => 'Create Scanner',
            'edit_route' => 'admin.scanner-users.edit',
            'destroy_route' => 'admin.scanner-users.destroy',
            'empty_state' => 'No scanner accounts matched the current filters.',
            'presence_footer_resource_label' => 'scanner accounts',
            'delete_dialog_title' => 'Delete this scanner account?',
            'delete_dialog_subject' => 'this scanner',
            'delete_dialog_directory' => 'scanner directory',
            'delete_dialog_access' => 'staff scanner portal',
            'delete_confirm_label' => 'Yes, delete scanner',
            'delete_cancel_label' => 'Keep account',
            'create_page_title' => 'Create Scanner',
            'create_heading' => 'Create Scanner',
            'create_description' => 'Add a new local scanner account so the team can sign in to the staff scanner portal with its own credentials.',
            'back_to_list_label' => 'Back to List User Scanner',
            'create_card_title' => 'New Scanner Profile',
            'create_card_description' => 'Create a new scanner account in the local SQL admin table.',
            'store_route' => 'admin.scanner-users.store',
            'role_display_value' => 'Scanner',
            'role_display_help' => 'This page creates staff scanner accounts only.',
            'create_submit_label' => 'Create Scanner',
            'notes_items' => [
                'Use a unique email address for each scanner account.',
                'Set the initial password to at least 8 characters.',
                'Inactive scanner accounts stay listed but cannot access the staff scanner portal.',
            ],
            'edit_page_title' => 'Edit Scanner',
            'edit_heading' => 'Edit Scanner',
            'edit_description' => 'Update the local scanner profile, email identity, and account status from one place.',
            'edit_card_title' => 'Scanner Profile',
            'edit_card_description' => 'Edit the main fields used to sign in to the staff scanner portal.',
            'update_route' => 'admin.scanner-users.update',
            'edit_role_display_help' => 'This page only manages scanner-role accounts.',
            'snapshot_id_label' => 'Scanner ID',
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, ['all', 'active', 'inactive'], true) ? $normalized : 'all';
    }

    private function normalizePresence(string $presence): string
    {
        $normalized = strtolower(trim($presence));

        return in_array($normalized, ['all', 'online', 'offline'], true) ? $normalized : 'all';
    }

    private function sanitizePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    private function abortIfNotManagedScanner(Admin $scannerUser): void
    {
        abort_unless((string) $scannerUser->role === 'scanner', 404);
    }
}
