<?php

namespace App\Http\Requests\Staff;

use App\Services\Scanner\ScannerGateService;
use Illuminate\Foundation\Http\FormRequest;

class StaffLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
            'scanner_device_profile' => ['required', 'string', 'in:laptop,android,iphone'],
            'scanner_post' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! app(ScannerGateService::class)->exists((string) $value)) {
                        $fail('Selected gate is no longer available.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var ScannerGateService $gateService */
        $gateService = app(ScannerGateService::class);

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'remember' => $this->boolean('remember'),
            'scanner_device_profile' => trim(strtolower((string) $this->input('scanner_device_profile'))),
            'scanner_post' => $gateService->normalizeName($this->input('scanner_post')),
        ]);
    }
}
