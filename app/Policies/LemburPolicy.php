<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lembur;
use Illuminate\Auth\Access\HandlesAuthorization;

class LemburPolicy
{
    use HandlesAuthorization;

    /**
     * Helper: cek izin format baru (underscore) dan format lama (::).
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
            'view_any_lembur',
            'view_any_absensi::lembur',
            'view_limit_lembur',
            'view_limit_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'view_lembur',
            'view_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAny($user, [
            'create_lembur',
            'create_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'update_lembur',
            'update_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'delete_lembur',
            'delete_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'delete_any_lembur',
            'delete_any_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'force_delete_lembur',
            'force_delete_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'force_delete_any_lembur',
            'force_delete_any_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'restore_lembur',
            'restore_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasAny($user, [
            'restore_any_lembur',
            'restore_any_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Lembur $lembur): bool
    {
        return $this->hasAny($user, [
            'replicate_lembur',
            'replicate_absensi::lembur',
        ]);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasAny($user, [
            'reorder_lembur',
            'reorder_absensi::lembur',
        ]);
    }
}
