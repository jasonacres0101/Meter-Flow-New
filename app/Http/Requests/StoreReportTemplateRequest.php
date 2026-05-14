<?php

namespace App\Http\Requests;

use App\Support\ParserRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportTemplateRequest extends FormRequest
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
            'machine_model_id' => ['required', 'exists:machine_models,id'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'sample_subject' => ['nullable', 'string', 'max:255'],
            'sample_body' => ['required', 'string'],
            'parser_type' => ['required', Rule::in(ParserRegistry::keys())],
            'parser_configuration' => ['nullable', 'json'],
            'incoming_report_email_id' => ['nullable', 'exists:incoming_report_emails,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
