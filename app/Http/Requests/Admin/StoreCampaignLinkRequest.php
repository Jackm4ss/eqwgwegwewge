<?php

namespace App\Http\Requests\Admin;

use App\Models\CampaignLink;
use App\Services\Admin\CampaignLinkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignLinkRequest extends FormRequest
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

        $slugRules = [
            'required',
            'string',
            'max:120',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn($campaignLinkService->reservedSlugs()),
        ];

        if ($campaignLinkService->storageReady()) {
            $nameRules[] = Rule::unique(CampaignLink::class, 'name');
            $slugRules[] = Rule::unique(CampaignLink::class, 'slug');
        }

        return [
            'name' => $nameRules,
            'slug' => $slugRules,
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
            'slug' => $campaignLinkService->normalizeSlug($this->input('slug')),
            'destination' => $campaignLinkService->normalizeDestination($this->input('destination')),
            'source' => $campaignLinkService->normalizeToken($this->input('source')),
            'medium' => $campaignLinkService->normalizeToken($this->input('medium')),
            'campaign' => $campaignLinkService->normalizeToken($this->input('campaign')),
            'utm_content' => $campaignLinkService->normalizeToken($this->input('utm_content')),
            'notes' => $campaignLinkService->normalizeNotes($this->input('notes')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Please enter the public slug after the slash.',
            'slug.regex' => 'The public slug must use lowercase letters, numbers, and hyphens only.',
            'slug.not_in' => 'That public slug is reserved by another route. Please choose a different slug.',
            'slug.unique' => 'That public slug is already in use.',
        ];
    }
}
