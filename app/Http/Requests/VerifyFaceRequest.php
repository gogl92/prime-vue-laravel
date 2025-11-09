<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class VerifyFaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please provide an image for verification.',
            'image.image' => 'The file must be a valid image.',
            'image.mimes' => 'The image must be in JPEG, JPG, or PNG format.',
            'image.max' => 'The image must not exceed 10MB.',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $userId = $this->route('userId');
        $user = \App\Models\User::findOrFail($userId);

        // Check if user has completed onboarding
        if ($user->referenceImages()->count() !== 5) {
            throw ValidationException::withMessages([
                'user' => ['User has not completed facial recognition onboarding.'],
            ]);
        }
    }
}
