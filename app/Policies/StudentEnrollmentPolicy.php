<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentEnrollment;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentEnrollmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('view_any_income') || $user->can('view_any_enrollment::batch');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('view_income') || $user->can('view_enrollment::batch');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Para crear inscripciones, necesita permisos de enrollment_batch O enrollment
        // NO necesita permisos de income
        return $user->can('create_enrollment::batch') || $user->can('create_enrollment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Para actualizar inscripciones, necesita permisos de enrollment_batch O enrollment
        // NO necesita permisos de income
        return $user->can('update_enrollment::batch') || $user->can('update_enrollment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Para eliminar inscripciones, necesita permisos de enrollment_batch O enrollment
        // NO necesita permisos de income
        return $user->can('delete_enrollment::batch') || $user->can('delete_enrollment');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('delete_any_income') || $user->can('delete_any_enrollment::batch');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('force_delete_income') || $user->can('force_delete_enrollment::batch');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('force_delete_any_income') || $user->can('force_delete_any_enrollment::batch');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('restore_income') || $user->can('restore_enrollment::batch');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('restore_any_income') || $user->can('restore_any_enrollment::batch');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, StudentEnrollment $studentEnrollment): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('replicate_income') || $user->can('replicate_enrollment::batch');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        // Permitir si el usuario tiene permisos de income O de enrollment_batch
        return $user->can('reorder_income') || $user->can('reorder_enrollment::batch');
    }
}
