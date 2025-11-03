<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\User;
use Rawilk\Settings\Models\Setting;

class SettingsService {
    /** @var string Model name */
    protected string $model = User::class;

    /**
     * Retrieves model-specific settings based on the model ID.
     *
     * @param int|string $modelId The ID of the model.
     * @return array<string, mixed> An associative array of settings.
     */
    public function index(int|string $modelId): array {
        $model = $this->model::findOrFail($modelId);
        $modelClass = $model::class;

        $pattern = sprintf('%%%s%%s:2:"id";i:%d%%', addslashes($modelClass), $modelId);
        $allSettings = Setting::where('key', 'LIKE', $pattern)->get();
        $modelSettings = collect($allSettings)->filter(function ($key) use ($modelClass) {
            return str_contains($key->key, $modelClass);
        });

        $result = [];
        foreach ($modelSettings as $key) {
            preg_match('/^[^:]+/', $key->key, $matches);
            $result[$matches[0]] = $model->settings()->get($matches[0]);
        }

        return $result;
    }

    /**
     * Stores a new setting for the specified model.
     *
     * This method saves individual settings for the model identified by the given ID.
     * If multiple resources are provided, it saves each resource's key-value pair.
     *
     * @param int|string $modelId The ID of the model to store the setting for.
     * @param array<string, mixed> $setting The setting data to be stored, which can include:
     *     - 'key': The key of the setting.
     *     - 'value': The value of the setting.
     *     - 'resources': An optional array of resources, each containing:
     *         - 'key': The resource key.
     *         - 'value': The resource value.
     *
     * @return array<string, mixed> The stored resource(s) or the value of the setting stored.
     */
    public function store(int|string $modelId, mixed $setting): mixed {

      $model = $this->model::findOrFail($modelId);

      $externalSettings = $model->getExternalSettings();

      if (isset($setting['resources'])) {
          foreach ($setting["resources"] as $resource) {
              if (isset($resource['key'], $resource['value'])) {
                  if ($this->isExternalSetting($resource['key'], $externalSettings)) {
                      $this->saveExternalSetting($model, $resource, $externalSettings);
                  }

                  $model->settings()->set($resource['key'], $resource['value']);
              }
          }
          return $setting["resources"];
      }

      if ($this->isExternalSetting($setting['key'], $externalSettings)) {
          $this->saveExternalSetting($model, $setting, $externalSettings);
      }

      $model->settings()->set($setting['key'], $setting['value']);

      return $model->settings()->get($setting['key']);
    }

    /**
     * Updates a specific setting for the model.
     *
     * Updates a setting identified by a key for a given model ID.
     *
     * @param int|string $modelId The ID of the model.
     * @param string $key The key of the setting to update.
     * @param mixed $value The new value for the setting.
     * @return mixed The updated value of the setting.
     */
    public function update(int|string $modelId, string $key, mixed $value): mixed {
        $model = $this->model::findOrFail($modelId);
        $externalSettings = $this->model::getExternalSettings();

        $model->settings()->set($key, $value);
        return $model->settings()->get($key);
    }

    /**
     * Retrieves a specific setting by key.
     *
     * Gets the value of a specific setting for a model by its ID and key.
     *
     * @param int|string $modelId The ID of the model.
     * @param string $key The key of the setting to retrieve.
     * @return mixed The value of the requested setting.
     */
    public function show(int|string $modelId, string $key): mixed {
        $model = $this->model::findOrFail($modelId);
        return $model->settings()->get($key);
    }

    /**
     * Deletes a specific setting by key.
     *
     * Removes a setting associated with the given model ID and key.
     *
     * @param int|string $modelId The ID of the model.
     * @param string $key The key of the setting to delete.
     * @return bool|null Returns true if the setting is deleted successfully, null otherwise.
     */
    public function destroy(int|string $modelId, string $key) {
        $model = $this->model::findOrFail($modelId);
        return $model->settings()->forget($key);
    }

    /**
     * Checks if a given key is part of the exception settings.
     *
     * @param string $key The key of the setting.
     * @param array<string, array> $externalSettings The list of exception settings.
     * @return bool True if the key is an exception, false otherwise.
     */
    private function isExternalSetting(string $key, array $externalSettings): bool {
        return key_exists($key, $externalSettings);
    }

    /**
     * Applies an exception for a setting if it exists in the exception list.
     *
     * Modifies the setting model by updating a specific field for an exception setting.
     *
     * @param mixed $model The model instance.
     * @param mixed $setting The setting data.
     * @param array<string, array> $externalSettings The list of exception settings.
     * @return void
     */
    private function saveExternalSetting(mixed $model, mixed $setting, mixed $externalSettings): void {
        $settingModel = $externalSettings[$setting['key']]['model']::findOrFail(
            $model[$externalSettings[$setting['key']]['relation_field_id']]
        );

        $settingModel[$externalSettings[$setting['key']]['field']] = $setting['value'];
        $settingModel->save();
    }
}
