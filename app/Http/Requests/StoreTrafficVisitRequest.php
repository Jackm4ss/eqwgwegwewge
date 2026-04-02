<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrafficVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'traffic_source' => ['nullable', 'string', 'max:120'],
            'traffic_source_detail' => ['nullable', 'string', 'max:120'],
            'traffic_medium' => ['nullable', 'string', 'max:40'],
            'traffic_campaign' => ['nullable', 'string', 'max:120'],
            'traffic_referrer_host' => ['nullable', 'string', 'max:120'],
            'traffic_landing_path' => ['nullable', 'string', 'max:500'],
            'traffic_captured_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'traffic_source' => $this->normalizeTrafficToken((string) $this->input('traffic_source')),
            'traffic_source_detail' => $this->normalizeOptionalValue((string) $this->input('traffic_source_detail')),
            'traffic_medium' => $this->normalizeTrafficToken((string) $this->input('traffic_medium')),
            'traffic_campaign' => $this->normalizeOptionalValue((string) $this->input('traffic_campaign')),
            'traffic_referrer_host' => $this->normalizeOptionalLowercaseValue((string) $this->input('traffic_referrer_host')),
            'traffic_landing_path' => $this->normalizeOptionalValue((string) $this->input('traffic_landing_path')),
            'traffic_captured_at' => $this->normalizeOptionalValue((string) $this->input('traffic_captured_at')),
        ]);
    }

    private function normalizeOptionalValue(string $value): ?string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeOptionalLowercaseValue(string $value): ?string
    {
        $normalized = $this->normalizeOptionalValue($value);

        return $normalized !== null ? strtolower($normalized) : null;
    }

    private function normalizeTrafficToken(string $value): ?string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : null;
    }
}
