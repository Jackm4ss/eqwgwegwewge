<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCampaignLinkRequest;
use App\Http\Requests\Admin\UpdateCampaignLinkRequest;
use App\Models\CampaignLink;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\CampaignLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampaignLinkController extends Controller
{
    public function __construct(
        private readonly CampaignLinkService $campaignLinkService,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $storageReady = $this->campaignLinkService->storageReady();
        $links = $this->campaignLinkService->manageableLinks();
        $presentedLinks = $links->map(
            fn (CampaignLink $campaignLink): array => $this->campaignLinkService->present($campaignLink)
        );

        return view('admin.campaign-links.index', [
            'links' => $presentedLinks,
            'storageReady' => $storageReady,
            'summary' => $this->campaignLinkService->dashboardSummary(),
            'destinationOptions' => $this->campaignLinkService->destinationOptions(),
            'sourceSuggestions' => $this->campaignLinkService->sourceSuggestions(),
            'mediumSuggestions' => $this->campaignLinkService->mediumSuggestions(),
            'createPreview' => $this->campaignLinkService->present([
                'name' => old('name', ''),
                'destination' => old('destination', 'homepage'),
                'source' => old('source', 'instagram'),
                'medium' => old('medium', 'bio'),
                'campaign' => old('campaign', 'april2026'),
                'utm_content' => old('utm_content', ''),
                'notes' => old('notes', ''),
                'is_active' => old('is_active', true),
            ]),
        ]);
    }

    public function store(StoreCampaignLinkRequest $request): RedirectResponse
    {
        if (! $this->campaignLinkService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $campaignLink = CampaignLink::query()->create($request->validated());

        $this->auditLogger->log(
            auth('admin')->user(),
            'campaign_link_create',
            'campaign_link',
            (string) $campaignLink->getKey(),
            [
                'name' => $campaignLink->name,
                'destination' => $campaignLink->destination,
                'source' => $campaignLink->source,
                'medium' => $campaignLink->medium,
                'campaign' => $campaignLink->campaign,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Campaign link created successfully.');
    }

    public function update(UpdateCampaignLinkRequest $request, string $campaignLink): RedirectResponse
    {
        if (! $this->campaignLinkService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $campaignLinkModel = $this->campaignLinkService->findManageableLinkById($campaignLink);

        if (! $campaignLinkModel) {
            return $this->redirectWithNotFoundError();
        }

        $before = $campaignLinkModel->only([
            'name',
            'destination',
            'source',
            'medium',
            'campaign',
            'utm_content',
            'is_active',
        ]);

        $campaignLinkModel->update($request->validated());

        $this->auditLogger->log(
            auth('admin')->user(),
            'campaign_link_update',
            'campaign_link',
            (string) $campaignLinkModel->getKey(),
            [
                'before' => $before,
                'after' => $campaignLinkModel->only([
                    'name',
                    'destination',
                    'source',
                    'medium',
                    'campaign',
                    'utm_content',
                    'is_active',
                ]),
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Campaign link updated successfully.');
    }

    public function destroy(Request $request, string $campaignLink): RedirectResponse
    {
        if (! $this->campaignLinkService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $campaignLinkModel = $this->campaignLinkService->findManageableLinkById($campaignLink);

        if (! $campaignLinkModel) {
            return $this->redirectWithNotFoundError();
        }

        $deletedLink = $campaignLinkModel->only([
            'name',
            'destination',
            'source',
            'medium',
            'campaign',
            'utm_content',
            'is_active',
        ]);

        $campaignLinkModel->delete();

        $this->auditLogger->log(
            auth('admin')->user(),
            'campaign_link_delete',
            'campaign_link',
            $campaignLink,
            $deletedLink,
            $request->ip(),
        );

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Campaign link deleted successfully.');
    }

    private function redirectWithStorageError(): RedirectResponse
    {
        return redirect()
            ->route('admin.campaign-links.index')
            ->withErrors([
                'error' => $this->campaignLinkService->storageNotReadyMessage(),
            ]);
    }

    private function redirectWithNotFoundError(): RedirectResponse
    {
        return redirect()
            ->route('admin.campaign-links.index')
            ->withErrors([
                'error' => 'The selected campaign link could not be found.',
            ]);
    }
}
