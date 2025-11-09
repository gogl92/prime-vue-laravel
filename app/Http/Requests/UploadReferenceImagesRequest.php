<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UploadReferenceImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated to upload reference images
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'images' => 'required|array|min:5|max:5',
            'images.*' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max per image
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
            'images.required' => 'Please upload reference images.',
            'images.array' => 'Images must be uploaded as an array.',
            'images.min' => 'You must upload exactly 5 reference images.',
            'images.max' => 'You must upload exactly 5 reference images.',
            'images.*.required' => 'Each image is required.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in JPEG, JPG, or PNG format.',
            'images.*.max' => 'Each image must not exceed 10MB.',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $userId = $this->route('userId');
        $user = User::findOrFail($userId);

        if ($user->referenceImages()->exists()) {
            logger()->info('User already has reference images');

            throw ValidationException::withMessages([
                'images' => ['Reference images have already been uploaded. Please delete existing images first.'],
            ]);
        }
    }
}
