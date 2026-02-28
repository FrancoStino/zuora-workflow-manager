<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatThread;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChatThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the given user may view any ChatThread records.
     *
     * @param  AuthUser  $authUser  The authenticated user to check.
     * @return bool `true` if the user has the `ViewAny:ChatThread` permission, `false` otherwise.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChatThread');
    }

    /**
     * Determine whether the authenticated user may view the given chat thread.
     *
     * @param  AuthUser  $authUser  The authenticated user performing the action.
     * @param  ChatThread  $chatThread  The chat thread to check access against.
     * @return bool `true` if the user has the 'View:ChatThread' permission, `false` otherwise.
     */
    public function view(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('ViewAny:ChatThread')
            || ($authUser->can('View:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the authenticated user can create a ChatThread.
     *
     * @param  AuthUser  $authUser  The authenticated user.
     * @return bool `true` if the user has the 'Create:ChatThread' permission, `false` otherwise.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChatThread');
    }

    /**
     * Determine whether the authenticated user may update the given chat thread.
     *
     * @return bool `true` if the user has the 'Update:ChatThread' permission, `false` otherwise.
     */
    public function update(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('UpdateAny:ChatThread')
            || ($authUser->can('Update:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the given authenticated user may delete the specified chat thread.
     *
     * @param  AuthUser  $authUser  The authenticated user performing the action.
     * @param  ChatThread  $chatThread  The chat thread to evaluate deletion for.
     * @return bool `true` if the user has permission to delete chat threads, `false` otherwise.
     */
    public function delete(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('DeleteAny:ChatThread')
            || ($authUser->can('Delete:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the authenticated user can restore the given chat thread.
     *
     * @param  AuthUser  $authUser  The authenticated user performing the action.
     * @param  ChatThread  $chatThread  The chat thread instance to be restored.
     * @return bool `true` if the user has the `Restore:ChatThread` permission, `false` otherwise.
     */
    public function restore(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('RestoreAny:ChatThread')
            || ($authUser->can('Restore:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the authenticated user can permanently delete the given chat thread.
     *
     * @param  AuthUser  $authUser  The authenticated user attempting the action.
     * @param  ChatThread  $chatThread  The chat thread being targeted.
     * @return bool `true` if the user has the permission "ForceDelete:ChatThread", `false` otherwise.
     */
    public function forceDelete(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('ForceDeleteAny:ChatThread')
            || ($authUser->can('ForceDelete:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the authenticated user may permanently delete any ChatThread.
     *
     * @param  AuthUser  $authUser  The authenticated user performing the check.
     * @return bool `true` if the user has permission to permanently delete any ChatThread, `false` otherwise.
     */
    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChatThread');
    }

    /**
     * Determine whether the authenticated user may restore any ChatThread.
     *
     * @param  AuthUser  $authUser  The authenticated user to check.
     * @return bool `true` if the user has the 'RestoreAny:ChatThread' permission, `false` otherwise.
     */
    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChatThread');
    }

    /**
     * Determine whether the authenticated user may replicate the given chat thread.
     *
     * @param  AuthUser  $authUser  The authenticated user.
     * @param  ChatThread  $chatThread  The chat thread to replicate.
     * @return bool `true` if the user has permission to replicate chat threads, `false` otherwise.
     */
    public function replicate(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('ReplicateAny:ChatThread')
            || ($authUser->can('Replicate:ChatThread') && $chatThread->user_id === $authUser->getAuthIdentifier());
    }

    /**
     * Determine whether the user can reorder chat thread resources.
     *
     * @param  AuthUser  $authUser  The authenticated user performing the action.
     * @return bool `true` if the user has the 'Reorder:ChatThread' permission, `false` otherwise.
     */
    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChatThread');
    }
}
