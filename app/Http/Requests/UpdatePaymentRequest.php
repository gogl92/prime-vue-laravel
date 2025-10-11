<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['sometimes', 'integer', 'exists:invoices,id'],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method' => [
                'sometimes',
                'string',
                Rule::in(['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash', 'check'])
            ],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'completed', 'failed', 'refunded'])
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_id.exists' => 'The selected invoice does not exist.',
            'amount.min' => 'Payment amount must be at least $0.01.',
            'amount.max' => 'Payment amount cannot exceed $999,999.99.',
            'payment_method.in' => 'Invalid payment method selected.',
            'status.in' => 'Invalid payment status selected.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'paid_at.date' => 'Paid at must be a valid date.',
        ];
    }
}
