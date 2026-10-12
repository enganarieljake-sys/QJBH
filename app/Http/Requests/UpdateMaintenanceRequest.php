<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest as MaintenanceRequestModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(MaintenanceRequestModel::PRIORITIES)],
            'status' => ['required', Rule::in(MaintenanceRequestModel::STATUSES)],
            'resolution_details' => ['nullable', 'required_if:status,resolved', 'string', 'max:5000'],
            'completion_date' => ['nullable', 'required_if:status,resolved', 'date'],
        ];
    }
}
