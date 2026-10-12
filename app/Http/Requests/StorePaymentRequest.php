<?php

namespace App\Http\Requests;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'bill_id' => ['required', 'exists:bills,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(Payment::METHODS)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bill = Bill::with('payments')->find($this->input('bill_id'));

            if (! $bill) {
                return;
            }

            if ((int) $this->input('tenant_id') !== (int) $bill->tenant_id) {
                $validator->errors()->add('tenant_id', 'The selected tenant does not match the bill.');
            }

            if ((float) $this->input('amount') > $bill->balance) {
                $validator->errors()->add('amount', 'Payment amount cannot be greater than the remaining balance.');
            }
        });
    }
}
