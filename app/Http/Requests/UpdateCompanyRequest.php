<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class UpdateCompanyRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'street_1' => ['sometimes', 'required', 'string', 'max:100'],
            'street_2' => ['sometimes', 'required', 'string', 'max:45'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:60'],
            'zip' => ['sometimes', 'required', 'string', 'max:11'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'max:45', (new Phone())->country(['US', 'MX'])],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:100'],
            'tax_id' => ['sometimes', 'required', 'string', 'max:45'],
            'tax_name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
