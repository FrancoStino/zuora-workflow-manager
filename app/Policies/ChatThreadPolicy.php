<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatThread;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChatThreadPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChatThread');
    }

    public function view(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('View:ChatThread');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChatThread');
    }

    public function update(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('Update:ChatThread');
    }

    public function delete(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('Delete:ChatThread');
    }

    public function restore(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('Restore:ChatThread');
    }

    public function forceDelete(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('ForceDelete:ChatThread');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChatThread');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChatThread');
    }

    public function replicate(AuthUser $authUser, ChatThread $chatThread): bool
    {
        return $authUser->can('Replicate:ChatThread');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChatThread');
    }
}
