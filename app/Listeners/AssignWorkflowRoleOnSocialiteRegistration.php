<?php

namespace App\Listeners;

use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Spatie\Permission\Models\Role;

class AssignWorkflowRoleOnSocialiteRegistration
{
    /**
     * Handle the event when a user registers via Socialite.
     * Assigns the existing workflow_user role to the newly registered user.
     */
    public function handle(Registered $event): void
    {
        $user = $event->socialiteUser->getUser();

        // Retrieve the workflow_user role (created during setup)
        $role = Role::where('name', 'workflow_user')->where('guard_name', 'web')->first();

        if ($role) {
            $user->assignRole($role);
        }
    }
}
