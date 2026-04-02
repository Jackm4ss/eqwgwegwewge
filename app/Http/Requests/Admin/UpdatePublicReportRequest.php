<?php

namespace App\Http\Requests\Admin;

use App\Models\PublicReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_report_id' => ['nullable', 'integer'],
            'action_status' => [
                'required',
                'string',
                Rule::in(PublicReport::actionStatuses()),
            ],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action_status' => trim((string) $this->input('action_status')),
            'admin_note' => trim((string) $this->input('admin_note')),
        ]);
    }
}
