<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncomingReportEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_email' => ['required', 'email'],
            'to_email' => ['nullable', 'email'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body_text' => ['required', 'string'],
            'body_html' => ['nullable', 'string'],
            'received_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
