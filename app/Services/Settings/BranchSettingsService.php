<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Branch;

class BranchSettingsService extends SettingsService
{
    /** @var string Model name */
    protected string $model = Branch::class;
}
