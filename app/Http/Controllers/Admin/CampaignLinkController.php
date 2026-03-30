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
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignLinkController extends Controller
{
    public function __construct(
        private readonly CampaignLinkService $campaignLinkService,
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View|RedirectResponse|StreamedResponse
    {
        if ($request->query('export') === 'csv') {
            return $this->exportCsv($request);
        }

        $storageReady = $this->campaignLinkService->storageReady();
        $filters = $this->campaignLinkService->sanitizeFilters($request->query());
        $links = $this->campaignLinkService->manageableLinks($filters);
        $presentedLinks = $links->map(
            fn (CampaignLink $campaignLink): array => $this->campaignLinkService->present($campaignLink)
        );
        $oldInput = $request->session()->getOldInput();

        return view('admin.campaign-links.index', [
            'links' => $presentedLinks,
            'storageReady' => $storageReady,
            'summary' => $this->campaignLinkService->dashboardSummary(),
            'analyticsSummary' => $this->campaignLinkService->analyticsSummary($filters),
            'filters' => $filters,
            'destinationOptions' => $this->campaignLinkService->destinationOptions(),
            'sourceSuggestions' => $this->campaignLinkService->sourceSuggestions(),
            'sourceFilterOptions' => $this->campaignLinkService->sourceFilterOptions(),
            'mediumSuggestions' => $this->campaignLinkService->mediumSuggestions(),
            'homepageUrl' => $this->campaignLinkService->homepageUrl(),
            'registerUrl' => $this->campaignLinkService->registerUrl(),
            'createForm' => $this->campaignLinkService->present([
                'name' => old('name', ''),
                'slug' => old('slug', ''),
                'destination' => old('destination', 'homepage'),
                'source' => old('source', 'instagram'),
                'medium' => old('medium', 'bio'),
                'campaign' => old('campaign', 'songkran2026'),
                'utm_content' => old('utm_content', ''),
                'notes' => old('notes', ''),
                'is_active' => ($oldInput['_create_campaign_link'] ?? null) === '1'
                    ? array_key_exists('is_active', $oldInput)
                    : true,
                'visit_count' => 0,
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
                'slug' => $campaignLink->slug,
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
            'slug',
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
                    'slug',
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
            'slug',
            'destination',
            'source',
            'medium',
            'campaign',
            'utm_content',
            'is_active',
            'visit_count',
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

    private function exportCsv(Request $request): RedirectResponse|StreamedResponse
    {
        if (! $this->campaignLinkService->storageReady()) {
            return $this->redirectWithStorageError();
        }

        $filters = $this->campaignLinkService->sanitizeFilters($request->query());
        $rows = $this->campaignLinkService->exportRows($filters);
        $filename = 'campaign-links-'.now()->format('Ymd-His').'.csv';

        $this->auditLogger->log(
            auth('admin')->user(),
            'campaign_link_export',
            'campaign_link',
            'campaign-links',
            [
                'format' => 'csv',
                'rows' => count($rows),
                'filters' => $filters,
            ],
            $request->ip(),
        );

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Name',
                'Slug',
                'Short URL',
                'Final URL',
                'Destination',
                'Source',
                'Medium',
                'Campaign',
                'UTM Content',
                'Active',
                'Visit Count',
                'Last Visited At',
                'Notes',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    data_get($row, 'name'),
                    data_get($row, 'slug'),
                    data_get($row, 'short_url'),
                    data_get($row, 'final_url'),
                    data_get($row, 'destination_label'),
                    data_get($row, 'source'),
                    data_get($row, 'medium'),
                    data_get($row, 'campaign'),
                    data_get($row, 'utm_content'),
                    data_get($row, 'is_active') ? 'Yes' : 'No',
                    data_get($row, 'visit_count', 0),
                    optional(data_get($row, 'last_visited_at'))->toDateTimeString()
                        ?? data_get($row, 'last_visited_at'),
                    data_get($row, 'notes'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
