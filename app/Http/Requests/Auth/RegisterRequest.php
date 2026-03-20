<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'identity_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc,dns', 'max:120'],
            'phone_number' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:17'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
            'identity_number' => trim((string) $this->input('identity_number')),
        ]);
    }
}
