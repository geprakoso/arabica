<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValidationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValidationLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_validation::log');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('view_validation::log');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_validation::log');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('update_validation::log');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('delete_validation::log');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_validation::log');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('force_delete_validation::log');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_validation::log');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('restore_validation::log');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_validation::log');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ValidationLog $validationLog): bool
    {
        return $user->can('replicate_validation::log');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_validation::log');
    }
}
