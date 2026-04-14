<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLostFoundItemRequest;
use App\Http\Requests\Admin\UpdateLostFoundItemRequest;
use App\Services\Admin\AdminAuditLogger;
use App\Services\LostFoundItemService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LostFoundItemController extends Controller
{
    public function __construct(
        private readonly LostFoundItemService $lostFoundItemService,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->lostFoundItemService->sanitizeAdminFilters($request->query());

        return view('admin.lost-found.index', [
            'storageReady' => $this->lostFoundItemService->storageReady(),
            'storageNotReadyMessage' => $this->lostFoundItemService->storageNotReadyMessage(),
            'filters' => $filters,
            'items' => $this->lostFoundItemService->paginateForAdmin($filters),
            'statusOptions' => $this->lostFoundItemService->statusOptions(),
            'statusLabel' => fn (string $value): string => $this->lostFoundItemService->statusLabel($value),
            'statusBadgeClass' => fn (string $value): string => $this->lostFoundItemService->statusBadgeClass($value),
            'imageUrl' => fn (?string $path): ?string => $this->lostFoundItemService->publicImageUrl($path),
            'contactValue' => fn (?string $value): string => $this->lostFoundItemService->adminContactValue($value),
            'contactPhoneNumber' => fn (?string $value): string => $this->lostFoundItemService->adminContactPhoneNumber($value),
        ]);
    }

    public function create(): View
    {
        return view('admin.lost-found.create', [
            'storageReady' => $this->lostFoundItemService->storageReady(),
            'storageNotReadyMessage' => $this->lostFoundItemService->storageNotReadyMessage(),
            'statusOptions' => $this->lostFoundItemService->statusOptions(),
            'contactValue' => fn (?string $value): string => $this->lostFoundItemService->adminContactValue($value),
            'contactCountryCode' => fn (?string $value): string => $this->lostFoundItemService->adminContactCountryCode($value),
            'contactPhoneNumber' => fn (?string $value): string => $this->lostFoundItemService->adminContactPhoneNumber($value),
        ]);
    }

    public function store(StoreLostFoundItemRequest $request): RedirectResponse
    {
        if (! $this->lostFoundItemService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $item = $this->lostFoundItemService->store($request->validated());

        $this->auditLogger->log(
            auth('admin')->user(),
            'lost_found_item_create',
            'lost_found_item',
            (string) $item->getKey(),
            [
                'title' => $item->title,
                'status' => $item->status,
                'found_date' => optional($item->found_date)->format('Y-m-d'),
                'location_found' => $item->location_found,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.lost-found.index')
            ->with('status', 'Lost & found item created successfully.');
    }

    public function edit(string $lostFoundItem): View|RedirectResponse
    {
        if (! $this->lostFoundItemService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $item = $this->lostFoundItemService->findManageableItemById($lostFoundItem);

        if (! $item) {
            return $this->redirectWithNotFoundError();
        }

        return view('admin.lost-found.edit', [
            'item' => $item,
            'storageReady' => true,
            'storageNotReadyMessage' => $this->lostFoundItemService->storageNotReadyMessage(),
            'statusOptions' => $this->lostFoundItemService->statusOptions(),
            'imageUrl' => fn (?string $path): ?string => $this->lostFoundItemService->publicImageUrl($path),
            'contactValue' => fn (?string $value): string => $this->lostFoundItemService->adminContactValue($value),
            'contactCountryCode' => fn (?string $value): string => $this->lostFoundItemService->adminContactCountryCode($value),
            'contactPhoneNumber' => fn (?string $value): string => $this->lostFoundItemService->adminContactPhoneNumber($value),
        ]);
    }

    public function update(UpdateLostFoundItemRequest $request, string $lostFoundItem): RedirectResponse
    {
        if (! $this->lostFoundItemService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $item = $this->lostFoundItemService->findManageableItemById($lostFoundItem);

        if (! $item) {
            return $this->redirectWithNotFoundError();
        }

        $before = [
            'title' => $item->title,
            'status' => $item->status,
            'found_date' => optional($item->found_date)->format('Y-m-d'),
            'location_found' => $item->location_found,
        ];

        $updatedItem = $this->lostFoundItemService->update($item, $request->validated());

        $this->auditLogger->log(
            auth('admin')->user(),
            'lost_found_item_update',
            'lost_found_item',
            (string) $updatedItem->getKey(),
            [
                'before' => $before,
                'after' => [
                    'title' => $updatedItem->title,
                    'status' => $updatedItem->status,
                    'found_date' => optional($updatedItem->found_date)->format('Y-m-d'),
                    'location_found' => $updatedItem->location_found,
                ],
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.lost-found.index')
            ->with('status', 'Lost & found item updated successfully.');
    }

    public function destroy(Request $request, string $lostFoundItem): RedirectResponse
    {
        if (! $this->lostFoundItemService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $item = $this->lostFoundItemService->findManageableItemById($lostFoundItem);

        if (! $item) {
            return $this->redirectWithNotFoundError();
        }

        $deletedItem = $this->lostFoundItemService->delete($item);

        $this->auditLogger->log(
            auth('admin')->user(),
            'lost_found_item_delete',
            'lost_found_item',
            (string) $deletedItem['id'],
            $deletedItem,
            $request->ip(),
        );

        return redirect()
            ->route('admin.lost-found.index')
            ->with('status', 'Lost & found item deleted successfully.');
    }

    private function redirectWithStorageError(): RedirectResponse
    {
        return redirect()
            ->route('admin.lost-found.index')
            ->withErrors([
                'error' => $this->lostFoundItemService->storageNotReadyMessage(),
            ]);
    }

    private function redirectWithNotFoundError(): RedirectResponse
    {
        return redirect()
            ->route('admin.lost-found.index')
            ->withErrors([
                'error' => 'The requested lost & found item could not be found.',
            ]);
    }
}
