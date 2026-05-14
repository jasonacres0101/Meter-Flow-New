<?php

namespace App\Http\Requests;

use App\Support\ParserRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineModelRequest extends FormRequest
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
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'manufacturer_name' => ['nullable', 'string', 'max:255', 'required_without:manufacturer_id'],
            'model_name' => ['required', 'string', 'max:255'],
            'parser_type' => ['required', Rule::in(ParserRegistry::keys())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
