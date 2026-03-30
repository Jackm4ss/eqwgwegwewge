<?php

namespace App\Http\Controllers;

use App\Services\Admin\CampaignLinkService;
use Illuminate\Http\RedirectResponse;

class CampaignShortLinkRedirectController extends Controller
{
    public function __construct(
        private readonly CampaignLinkService $campaignLinkService,
    ) {}

    public function __invoke(string $slug): RedirectResponse
    {
        $campaignLink = $this->campaignLinkService->findBySlug($slug);

        if (! $campaignLink || ! $campaignLink->is_active) {
            return redirect()->to($this->campaignLinkService->homepageUrl());
        }

        $this->campaignLinkService->recordVisit($campaignLink);

        return redirect()->to($this->campaignLinkService->finalUrl($campaignLink));
    }
}
