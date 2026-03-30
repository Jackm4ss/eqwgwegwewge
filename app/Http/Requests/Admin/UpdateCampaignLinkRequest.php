<?php

namespace App\Http\Requests\Admin;

use App\Models\CampaignLink;
use App\Services\Admin\CampaignLinkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var CampaignLinkService $campaignLinkService */
        $campaignLinkService = app(CampaignLinkService::class);

        $nameRules = [
            'required',
            'string',
            'max:120',
        ];

        if ($campaignLinkService->storageReady()) {
            $nameRules[] = Rule::unique(CampaignLink::class, 'name')->ignore($this->route('campaignLink'));
        }

        return [
            'name' => $nameRules,
            'destination' => ['required', Rule::in(array_keys($campaignLinkService->destinationOptions()))],
            'source' => ['required', 'string', 'max:120'],
            'medium' => ['required', 'string', 'max:120'],
            'campaign' => ['required', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var CampaignLinkService $campaignLinkService */
        $campaignLinkService = app(CampaignLinkService::class);

        $this->merge([
            'name' => $campaignLinkService->normalizeName($this->input('name')),
            'destination' => $campaignLinkService->normalizeDestination($this->input('destination')),
            'source' => $campaignLinkService->normalizeToken($this->input('source')),
            'medium' => $campaignLinkService->normalizeToken($this->input('medium')),
            'campaign' => $campaignLinkService->normalizeToken($this->input('campaign')),
            'utm_content' => $campaignLinkService->normalizeToken($this->input('utm_content')),
            'notes' => $campaignLinkService->normalizeNotes($this->input('notes')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
