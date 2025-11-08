<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\Builder;

class UserController extends BaseOrionController
{
    /** @var class-string<User> */
    protected $model = User::class;

    /** @var class-string<StoreUserRequest> */
    protected $storeRequest = StoreUserRequest::class;

    /** @var class-string<UpdateUserRequest> */
    protected $updateRequest = UpdateUserRequest::class;

    /**
     * The relations that are allowed to be included together with a resource.
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        return ['currentCompany', 'currentBranch', 'roles'];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['first_name', 'last_name', 'username', 'email', 'current_company_id', 'current_branch_id'];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['first_name', 'last_name', 'username', 'email', 'created_at', 'updated_at'];
    }

    /**
     * The attributes that are used for searching.
     *
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['first_name', 'last_name', 'second_last_name', 'username', 'email', 'phone'];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param \Illuminate\Http\Request $request
     * @param array<int, string> $requestedRelations
     * @return Builder<User>
     */
    protected function buildIndexFetchQuery($request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Eager load roles relationship
        $query->with('roles');

        return $query;
    }

    /**
     * Runs the given query for fetching entity in show method.
     *
     * @param \Illuminate\Http\Request $request
     * @param array<int, string> $requestedRelations
     * @return Builder<User>
     */
    protected function buildShowFetchQuery($request, array $requestedRelations): Builder
    {
        $query = parent::buildShowFetchQuery($request, $requestedRelations);

        // Eager load roles relationship
        $query->with('roles');

        return $query;
    }

    /**
     * Fills attributes on the given entity and persists it into database.
     *
     * @param \Illuminate\Http\Request $request
     * @param User $entity
     * @param array<string, mixed> $attributes
     */
    protected function performStore($request, $entity, array $attributes): void
    {
        // Remove roles from attributes as they'll be handled separately
        $roles = $attributes['roles'] ?? [];
        unset($attributes['roles']);

        // Remove password_confirmation from attributes
        unset($attributes['password_confirmation']);

        $entity->fill($attributes);
        $entity->save();

        // Sync roles if provided
        if (!empty($roles)) {
            $entity->syncRoles($roles);
        }
    }

    /**
     * Fills attributes on the given entity and persists it into database.
     *
     * @param \Illuminate\Http\Request $request
     * @param User $entity
     * @param array<string, mixed> $attributes
     */
    protected function performUpdate($request, $entity, array $attributes): void
    {
        // Remove roles from attributes as they'll be handled separately
        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        // Remove password_confirmation from attributes
        unset($attributes['password_confirmation']);

        // Only update password if provided
        if (empty($attributes['password'])) {
            unset($attributes['password']);
        }

        $entity->fill($attributes);
        $entity->save();

        // Sync roles if provided
        if ($roles !== null) {
            $entity->syncRoles($roles);
        }
    }

    /**
     * Authorize index action.
     *
     * @return void
     */
    protected function authorizeIndex(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * Authorize store action.
     *
     * @return void
     */
    protected function authorizeStore(): void
    {
        $this->authorize('create', User::class);
    }

    /**
     * Authorize show action.
     *
     * @param User $entity
     * @return void
     */
    protected function authorizeShow($entity): void
    {
        $this->authorize('view', [$entity]);
    }

    /**
     * Authorize update action.
     *
     * @param User $entity
     * @return void
     */
    protected function authorizeUpdate($entity): void
    {
        $this->authorize('update', [$entity]);
    }

    /**
     * Authorize destroy action.
     *
     * @param User $entity
     * @return void
     */
    protected function authorizeDestroy($entity): void
    {
        $this->authorize('delete', [$entity]);
    }
}
