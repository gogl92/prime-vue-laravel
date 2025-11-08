<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;

class RoleController extends BaseOrionController
{
    /** @var class-string<Role> */
    protected $model = Role::class;

    /**
     * The attributes that are used for filtering.
     *
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['name'];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['name', 'created_at'];
    }

    /**
     * The attributes that are used for searching.
     *
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['name'];
    }
}
