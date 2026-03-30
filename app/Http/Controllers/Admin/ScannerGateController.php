<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScannerGateRequest;
use App\Http\Requests\Admin\UpdateScannerGateRequest;
use App\Models\ScannerGate;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Scanner\ScannerGateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScannerGateController extends Controller
{
    public function __construct(
        private readonly ScannerGateService $gateService,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $storageReady = $this->gateService->storageReady();
        $gates = $this->gateService->manageableGates();
        $latestGateUpdate = $gates->sortByDesc('updated_at')->first();

        return view('admin.gates.index', [
            'gates' => $gates,
            'storageReady' => $storageReady,
            'overview' => [
                'total' => $gates->count(),
                'primary' => $gates->first()?->name ?? '-',
                'latest_update' => $latestGateUpdate?->updated_at,
            ],
        ]);
    }

    public function store(StoreScannerGateRequest $request): RedirectResponse
    {
        if (! $this->gateService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $gate = ScannerGate::query()->create($request->validated());
        $this->gateService->clearCache();

        $this->auditLogger->log(
            auth('admin')->user(),
            'gate_create',
            'scanner_gate',
            (string) $gate->getKey(),
            [
                'name' => $gate->name,
                'sort_order' => $gate->sort_order,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.gates.index')
            ->with('status', 'New gate added successfully.');
    }

    public function update(UpdateScannerGateRequest $request, string $gate): RedirectResponse
    {
        if (! $this->gateService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $gateModel = $this->gateService->findManageableGateById($gate);

        if (! $gateModel) {
            return $this->redirectWithGateNotFoundError();
        }

        $before = [
            'name' => $gateModel->name,
            'sort_order' => $gateModel->sort_order,
        ];

        $gateModel->update($request->validated());
        $this->gateService->clearCache();

        $this->auditLogger->log(
            auth('admin')->user(),
            'gate_update',
            'scanner_gate',
            (string) $gateModel->getKey(),
            [
                'before' => $before,
                'after' => [
                    'name' => $gateModel->name,
                    'sort_order' => $gateModel->sort_order,
                ],
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.gates.index')
            ->with('status', 'Gate updated successfully.');
    }

    public function destroy(Request $request, string $gate): RedirectResponse
    {
        if (! $this->gateService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $gateModel = $this->gateService->findManageableGateById($gate);

        if (! $gateModel) {
            return $this->redirectWithGateNotFoundError();
        }

        if (ScannerGate::query()->count() <= 1) {
            return back()->withErrors([
                'error' => 'At least one gate must remain available for scanner staff.',
            ]);
        }

        $deletedGate = [
            'id' => (string) $gateModel->getKey(),
            'name' => $gateModel->name,
            'sort_order' => $gateModel->sort_order,
        ];

        $gateModel->delete();
        $this->gateService->clearCache();

        $this->auditLogger->log(
            auth('admin')->user(),
            'gate_delete',
            'scanner_gate',
            $deletedGate['id'],
            $deletedGate,
            $request->ip(),
        );

        return redirect()
            ->route('admin.gates.index')
            ->with('status', 'Gate deleted successfully.');
    }

    private function redirectWithStorageError(): RedirectResponse
    {
        return redirect()
            ->route('admin.gates.index')
            ->withErrors([
                'error' => $this->gateService->storageNotReadyMessage(),
            ]);
    }

    private function redirectWithGateNotFoundError(): RedirectResponse
    {
        return redirect()
            ->route('admin.gates.index')
            ->withErrors([
                'error' => 'The selected gate could not be found.',
            ]);
    }
}
