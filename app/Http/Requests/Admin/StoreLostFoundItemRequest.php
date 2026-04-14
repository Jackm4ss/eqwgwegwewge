<?php

namespace App\Http\Requests\Admin;

use App\Models\LostFoundItem;
use App\Support\LostFoundContactFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLostFoundItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'location_found' => ['required', 'string', 'max:255'],
            'found_date' => ['required', 'date'],
            'contact_country_code' => ['required', 'string', Rule::in([LostFoundContactFormatter::DEFAULT_COUNTRY_CODE])],
            'contact_phone_number' => ['required', 'string', 'max:30', 'regex:/^\d{6,20}$/'],
            'contact_info' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(LostFoundItem::statuses())],
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('title'))),
            'description' => trim((string) $this->input('description')),
            'location_found' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('location_found'))),
            'found_date' => trim((string) $this->input('found_date')),
            'contact_country_code' => trim((string) $this->input('contact_country_code', LostFoundContactFormatter::DEFAULT_COUNTRY_CODE)),
            'contact_phone_number' => preg_replace('/\D+/u', '', (string) $this->input('contact_phone_number')) ?? '',
            'contact_info' => LostFoundContactFormatter::normalizeFromParts(
                (string) $this->input('contact_country_code', LostFoundContactFormatter::DEFAULT_COUNTRY_CODE),
                (string) $this->input('contact_phone_number'),
            ),
            'status' => trim((string) $this->input('status')),
        ]);
    }
}
