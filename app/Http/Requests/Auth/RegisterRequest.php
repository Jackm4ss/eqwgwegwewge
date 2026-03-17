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
            'gender' => ['required', 'in:male,female,other'],
            'country' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agree_terms' => ['accepted'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
        ]);
    }
}
