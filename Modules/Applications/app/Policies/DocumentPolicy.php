<?php

namespace Modules\Applications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Document;
use Modules\IdentityAccess\Models\User;
use Modules\Teams\Models\TeamMember;

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
     * - Team members of application can also download files
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

        $ownerOfDocument = $document->owner_id;


        $ownerTeamIds = TeamMember::where('user_id', $ownerOfDocument)
            ->pluck('team_id')
            ->toArray();


        $areInSameTeam = TeamMember::whereIn('team_id', $ownerTeamIds)
            ->where('user_id', $user->id)
            ->exists();

        if ($areInSameTeam) {
            return true;
        }

        if ($user->isEvaluator()) {
            $ownerId = $document->owner_id;
            $ownerTeamIds = TeamMember::where('user_id', $ownerId)
                ->pluck('team_id')
                ->toArray();

            $isAssignedToEvaluator = DB::table('evaluation')
                ->join('application', 'evaluation.application_id', '=', 'application.id')
                ->join('commission_member', 'evaluation.commission_member_id', '=', 'commission_member.id')
                ->whereIn('application.team_id', $ownerTeamIds)
                ->where('commission_member.user_id', $user->id)
                ->exists();

            if ($isAssignedToEvaluator) {
                return true;
            }
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
