<?php

namespace Modules\Applications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Applications\Models\Document;
use Modules\IdentityAccess\Models\User;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the document.
     *
     * Access rules:
     * - Owner can always view their own documents
     * - Admin/SuperAdmin can view any document
     * - For "Interné" (internal) documents attached to applications:
     *   Users who are part of the application team can view them
     */

    public function view(User $user, Document $document): bool
    {
        // Owner or admin can always view
        if ($user->id === $document->owner_id || $user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isEvaluator()) {

            return $document->applications()
                ->whereHas('evaluations', function ($q) use ($user) {
                    $q->whereHas('commissionMember', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
                })
                ->exists();
        }


        if ($document->securityClassification &&
            $document->securityClassification->name === 'Interné') {
            return $document->applications()
                ->where('created_by', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create a document.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can upload documents
        return true;
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // Only owner or admin can update document
        return $user->id === $document->owner_id || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // Only owner or admin can delete document
        return $user->id === $document->owner_id || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
