<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffScannerPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scanner_post' => ['required', 'string', Rule::in(array_values(config('scanner.posts', ['Gate A'])))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scanner_post' => trim((string) $this->input('scanner_post')),
        ]);
    }
}
