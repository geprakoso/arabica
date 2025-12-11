<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbsensiPolicy
{
    use HandlesAuthorization;

    /**
     * Helper: cek izin baik format baru (underscore) maupun format lama (::).
     */
    protected function hasAny(User $user, array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($user->can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAny($user, [
            'view_any_absensi',
            'view_any_absensi::absensi',
            'view_limit_absensi',
            'view_limit_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'view_absensi',
            'view_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAny($user, [
            'create_absensi',
            'create_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'update_absensi',
            'update_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'delete_absensi',
            'delete_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'delete_any_absensi',
            'delete_any_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'force_delete_absensi',
            'force_delete_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'force_delete_any_absensi',
            'force_delete_any_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'restore_absensi',
            'restore_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasAny($user, [
            'restore_any_absensi',
            'restore_any_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Absensi $absensi): bool
    {
        return $this->hasAny($user, [
            'replicate_absensi',
            'replicate_absensi::absensi',
        ]);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasAny($user, [
            'reorder_absensi',
            'reorder_absensi::absensi',
        ]);
    }
}
