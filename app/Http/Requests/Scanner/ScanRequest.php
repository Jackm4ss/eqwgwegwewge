<?php

namespace App\Http\Requests\Scanner;

use Illuminate\Foundation\Http\FormRequest;

class ScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_payload' => ['required', 'string', 'max:2048'],
        ];
    }
}
