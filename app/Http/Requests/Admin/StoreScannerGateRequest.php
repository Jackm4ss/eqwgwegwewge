<?php

namespace App\Http\Requests\Admin;

use App\Models\ScannerGate;
use App\Services\Scanner\ScannerGateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScannerGateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ScannerGateService $gateService */
        $gateService = app(ScannerGateService::class);

        $nameRules = [
            'required',
            'string',
            'max:120',
        ];

        if ($gateService->storageReady()) {
            $nameRules[] = Rule::unique(ScannerGate::class, 'name');
        }

        return [
            'name' => $nameRules,
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var ScannerGateService $gateService */
        $gateService = app(ScannerGateService::class);

        $this->merge([
            'name' => $gateService->normalizeName($this->input('name')),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }
}
