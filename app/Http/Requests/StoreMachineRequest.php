<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
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
            'client_id' => ['required', 'exists:clients,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'machine_model_id' => ['required', 'exists:machine_models,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('machines', 'serial_number')->ignore($this->route('machine'))],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:32'],
            'subnet_mask' => ['nullable', 'ip'],
            'gateway' => ['nullable', 'ip'],
            'primary_dns' => ['nullable', 'ip'],
            'secondary_dns' => ['nullable', 'ip'],
            'network_vlan' => ['nullable', 'string', 'max:50'],
            'snmp_version' => ['nullable', 'string', 'max:50'],
            'snmp_community' => ['nullable', 'string', 'max:255'],
            'dhcp_enabled' => ['nullable', 'boolean'],
            'network_notes' => ['nullable', 'string'],
            'expected_report_sender_email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
