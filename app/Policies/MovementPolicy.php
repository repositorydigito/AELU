<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Movement;
use Illuminate\Auth\Access\HandlesAuthorization;

class MovementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_movement');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Movement $movement): bool
    {
        return $user->can('view_movement');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_movement');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Movement $movement): bool
    {
        return $user->can('update_movement');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Movement $movement): bool
    {
        return $user->can('delete_movement');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_movement');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Movement $movement): bool
    {
        return $user->can('force_delete_movement');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_movement');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Movement $movement): bool
    {
        return $user->can('restore_movement');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_movement');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Movement $movement): bool
    {
        return $user->can('replicate_movement');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_movement');
    }
}
