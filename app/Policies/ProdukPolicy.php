<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Produk;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProdukPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_master::data::produk');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Produk $produk): bool
    {
        return $user->can('view_master::data::produk');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_master::data::produk');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Produk $produk): bool
    {
        return $user->can('update_master::data::produk');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Produk $produk): bool
    {
        return $user->can('delete_master::data::produk');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_master::data::produk');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Produk $produk): bool
    {
        return $user->can('force_delete_master::data::produk');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_master::data::produk');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Produk $produk): bool
    {
        return $user->can('restore_master::data::produk');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_master::data::produk');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Produk $produk): bool
    {
        return $user->can('replicate_master::data::produk');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_master::data::produk');
    }
}
