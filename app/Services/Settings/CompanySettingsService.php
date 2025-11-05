<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Company;

class CompanySettingsService extends SettingsService
{
    /** @var string Model name */
    protected string $model = Company::class;
}
