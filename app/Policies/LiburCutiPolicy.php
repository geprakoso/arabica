<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LiburCuti;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiburCutiPolicy
{
    use HandlesAuthorization;

    /**
     * Helper: cek izin format baru (underscore) dan lama (::).
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
            'view_any_libur_cuti',
            'view_any_absensi::libur::cuti',
            'view_limit_libur_cuti',
            'view_limit_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'view_libur_cuti',
            'view_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAny($user, [
            'create_libur_cuti',
            'create_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'update_libur_cuti',
            'update_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'delete_libur_cuti',
            'delete_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'delete_any_libur_cuti',
            'delete_any_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'force_delete_libur_cuti',
            'force_delete_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasAny($user, [
            'force_delete_any_libur_cuti',
            'force_delete_any_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'restore_libur_cuti',
            'restore_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasAny($user, [
            'restore_any_libur_cuti',
            'restore_any_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, LiburCuti $liburCuti): bool
    {
        return $this->hasAny($user, [
            'replicate_libur_cuti',
            'replicate_absensi::libur::cuti',
        ]);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasAny($user, [
            'reorder_libur_cuti',
            'reorder_absensi::libur::cuti',
        ]);
    }
}
