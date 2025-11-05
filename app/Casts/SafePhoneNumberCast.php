<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use Exception;

/**
 * Safe phone number cast that handles invalid phone numbers gracefully
 * instead of throwing exceptions
 *
 * @implements CastsAttributes<PhoneNumber|null, string|null>
 */
class SafePhoneNumberCast implements CastsAttributes
{
    /**
     * @var array<int, string>
     */
    protected array $countries;

    /**
     * @param string ...$countries
     */
    public function __construct(string ...$countries)
    {
        $this->countries = empty($countries) ? ['MX', 'US'] : array_values($countries);
    }

    /**
     * Cast the given value to a PhoneNumber instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }

        try {
            return new PhoneNumber($value, $this->countries);
        } catch (Exception $e) {
            // Log the error but don't throw exception
            Log::warning("Failed to parse phone number for {$model->getTable()}.{$key}: {$value}", [
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            // Return null instead of throwing exception
            return null;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // If already a PhoneNumber instance, format it
        if ($value instanceof PhoneNumber) {
            return $value->formatE164();
        }

        // Try to parse and format the phone number
        try {
            $phone = new PhoneNumber($value, $this->countries);
            return $phone->formatE164();
        } catch (Exception $e) {
            // Log the error
            Log::warning("Failed to format phone number for storage: {$value}", [
                'model' => get_class($model),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Store the raw value instead of throwing exception
            // This allows the validation layer to catch it
            return is_string($value) ? $value : null;
        }
    }
}
