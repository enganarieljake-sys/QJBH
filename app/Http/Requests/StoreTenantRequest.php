<?php

namespace App\Http\Requests;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('deposit_amount') && blank($this->input('deposit_amount'))) {
            $this->merge(['deposit_amount' => 0]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id', 'unique:tenants,user_id'],
            'room_id' => ['nullable', 'required_if:status,active', 'exists:rooms,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'emergency_contact' => ['required', 'string', 'max:255'],
            'valid_id_reference' => ['nullable', 'string', 'max:255'],
            'move_in_date' => ['required', 'date'],
            'lease_end_date' => ['nullable', 'date', 'after_or_equal:move_in_date'],
            'status' => ['required', Rule::in(Tenant::STATUSES)],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'deposit_status' => ['nullable', Rule::in(['none', 'held'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') !== 'active' || ! $this->filled('room_id')) {
                return;
            }

            $room = Room::find($this->input('room_id'));

            if ($room && ! $room->hasAvailableCapacity()) {
                $validator->errors()->add('room_id', 'This room is under maintenance or has reached its active tenant capacity.');
            }
        });
    }
}
