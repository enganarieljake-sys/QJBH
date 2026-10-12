<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
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
            'tenant_id' => ['required', 'exists:tenants,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'billing_month' => ['required', 'date_format:Y-m'],
            'electricity_amount' => ['required', 'numeric', 'min:0'],
            'water_amount' => ['required', 'numeric', 'min:0'],
            'other_charges' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(Bill::STATUSES)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled(['tenant_id', 'billing_month']) || ! preg_match('/^\d{4}-\d{2}$/', $this->input('billing_month'))) {
                return;
            }

            $billingMonth = Carbon::createFromFormat('Y-m', $this->input('billing_month'))->startOfMonth();

            $exists = Bill::where('tenant_id', $this->input('tenant_id'))
                ->whereDate('billing_month', $billingMonth)
                ->exists();

            if ($exists) {
                $validator->errors()->add('billing_month', 'This tenant already has a bill for the selected month.');
            }
        });
    }
}
