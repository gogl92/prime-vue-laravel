<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class StoreCompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'street_1' => ['required', 'string', 'max:100'],
            'street_2' => ['required', 'string', 'max:45'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:60'],
            'zip' => ['required', 'string', 'max:11'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:45', (new Phone())->country(['US', 'MX'])],
            'email' => ['required', 'string', 'email', 'max:100'],
            'tax_id' => ['required', 'string', 'max:45'],
            'tax_name' => ['required', 'string', 'max:255'],
        ];
    }
}
