<?php

namespace App\Http\Requests;

use App\Services\Security\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'in:incident_security,lost_item,lost_locker_card,medical_attention,others'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'identity_number' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['required', 'date_format:H:i'],
            'chronology' => ['required', 'string', 'min:10', 'max:4000'],
            'staff_name' => ['nullable', 'string', 'max:120'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'report_type' => 'report type',
            'identity_number' => 'IC / Passport No.',
            'incident_date' => 'incident date',
            'incident_time' => 'incident time',
            'staff_name' => 'staff name',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! config('services.recaptcha.enabled')) {
                return;
            }

            $recaptchaToken = trim((string) $this->input('recaptcha_token'));

            if ($recaptchaToken === '') {
                $validator->errors()->add('recaptcha_token', 'Please complete the reCAPTCHA verification.');

                return;
            }

            $isVerified = app(RecaptchaService::class)->verify($recaptchaToken, $this->ip());

            if (! $isVerified) {
                $validator->errors()->add('recaptcha_token', 'reCAPTCHA verification failed. Please try again.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report_type' => strtolower(trim((string) $this->input('report_type'))),
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'identity_number' => trim((string) $this->input('identity_number')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'incident_date' => trim((string) $this->input('incident_date')),
            'incident_time' => trim((string) $this->input('incident_time')),
            'chronology' => trim((string) $this->input('chronology')),
            'staff_name' => trim((string) $this->input('staff_name')),
            'recaptcha_token' => trim((string) $this->input('recaptcha_token')),
        ]);
    }
}
