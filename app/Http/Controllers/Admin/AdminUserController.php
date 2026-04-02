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

class AdminUserController extends Controller
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

        $adminDirectoryQuery = Admin::query()
            ->where('role', 'admin')
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
            $matchingAdminIds = collect($presence->statuses(
                (clone $adminDirectoryQuery)->pluck('id')->all(),
            ))
                ->filter(fn (bool $isOnline): bool => $presenceFilter === 'online' ? $isOnline : ! $isOnline)
                ->keys()
                ->all();

            $adminDirectoryQuery->when(
                $matchingAdminIds === [],
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                fn (Builder $query): Builder => $query->whereKey($matchingAdminIds),
            );
        }

        $admins = $adminDirectoryQuery
            ->orderByDesc('is_active')
            ->orderByDesc('last_login_at')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $adminSummaryQuery = Admin::query()->where('role', 'admin');
        $latestLoginAdmin = (clone $adminSummaryQuery)
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->first();

        $visibleAdminIds = $admins->getCollection()
            ->map(fn (Admin $admin): string => (string) $admin->getKey())
            ->all();

        return view('admin.admin-users.index', [
            'admins' => $admins,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'presence' => $presenceFilter,
                'per_page' => $perPage,
            ],
            'presence' => [
                'statuses' => $presence->statuses($visibleAdminIds),
                'heartbeat_seconds' => $presence->heartbeatSeconds(),
                'ttl_seconds' => $presence->ttlSeconds(),
                'statuses_url' => route('admin.presence.statuses'),
            ],
            'summary' => [
                'total' => (clone $adminSummaryQuery)->count(),
                'active' => (clone $adminSummaryQuery)->where('is_active', true)->count(),
                'inactive' => (clone $adminSummaryQuery)->where('is_active', false)->count(),
                'latest_login_admin' => $latestLoginAdmin,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-users.create');
    }

    public function store(AdminStoreAdminRequest $request): RedirectResponse
    {
        $adminUser = Admin::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => 'admin',
            'is_active' => (bool) $request->validated('is_active'),
        ]);

        $this->auditLogger->log(
            auth('admin')->user(),
            'admin_user_create',
            'admin',
            (string) $adminUser->getKey(),
            [
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'is_active' => $adminUser->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.admin-users.index')
            ->with('status', 'Admin account was created successfully.');
    }

    public function edit(Admin $adminUser): View
    {
        $this->abortIfNotManagedAdmin($adminUser);

        return view('admin.admin-users.edit', [
            'adminUser' => $adminUser,
        ]);
    }

    public function update(AdminUpdateAdminRequest $request, Admin $adminUser): RedirectResponse
    {
        $this->abortIfNotManagedAdmin($adminUser);

        $attributes = $request->validated();

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $adminUser->fill($attributes);
        $adminUser->save();

        $this->auditLogger->log(
            auth('admin')->user(),
            'admin_user_update',
            'admin',
            (string) $adminUser->getKey(),
            [
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'is_active' => $adminUser->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.admin-users.index')
            ->with('status', 'Admin account was updated successfully.');
    }

    public function destroy(Request $request, Admin $adminUser): RedirectResponse
    {
        $this->abortIfNotManagedAdmin($adminUser);

        $currentAdmin = auth('admin')->user();

        if ($currentAdmin?->is($adminUser)) {
            return redirect()
                ->route('admin.admin-users.index')
                ->withErrors([
                    'error' => 'You cannot delete the account currently signed in.',
                ]);
        }

        $metadata = [
            'name' => $adminUser->name,
            'email' => $adminUser->email,
        ];

        try {
            $adminUser->delete();
        } catch (\Throwable $exception) {
            throw new RuntimeException('The admin account could not be deleted.', previous: $exception);
        }

        $this->auditLogger->log(
            $currentAdmin,
            'admin_user_delete',
            'admin',
            (string) $adminUser->getKey(),
            $metadata,
            $request->ip(),
        );

        return redirect()
            ->route('admin.admin-users.index')
            ->with('status', 'Admin account was deleted successfully.');
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

    private function abortIfNotManagedAdmin(Admin $adminUser): void
    {
        abort_unless((string) $adminUser->role === 'admin', 404);
    }
}
