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
            'report_type' => ['required', 'in:incident_security,lost_item,lost_locker_card,medical_attention,ticket_registration,others'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'identity_type' => ['required', 'in:national_id,passport'],
            'identity_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:120'],
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
            'identity_type' => 'document type',
            'identity_number' => match ((string) $this->input('identity_type')) {
                'national_id' => 'Malaysia IC (MyKad) Number',
                'passport' => 'Passport Number',
                default => 'document number',
            },
            'email' => 'E-mail',
            'incident_date' => 'incident date',
            'incident_time' => 'incident time',
            'staff_name' => 'staff name',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $recaptcha = app(RecaptchaService::class);

            if (! $recaptcha->shouldVerify()) {
                return;
            }

            $recaptchaToken = trim((string) $this->input('recaptcha_token'));

            if ($recaptchaToken === '') {
                $validator->errors()->add('recaptcha_token', 'Please complete the reCAPTCHA verification.');

                return;
            }

            $isVerified = $recaptcha->verify($recaptchaToken, $this->ip());

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
            'identity_type' => strtolower(trim((string) $this->input('identity_type'))),
            'identity_number' => strtoupper(trim((string) $this->input('identity_number'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'incident_date' => trim((string) $this->input('incident_date')),
            'incident_time' => trim((string) $this->input('incident_time')),
            'chronology' => trim((string) $this->input('chronology')),
            'staff_name' => trim((string) $this->input('staff_name')),
            'recaptcha_token' => trim((string) $this->input('recaptcha_token')),
        ]);
    }
}
