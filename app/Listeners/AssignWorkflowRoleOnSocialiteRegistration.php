<?php

namespace App\Listeners;

use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Spatie\Permission\Models\Role;

class AssignWorkflowRoleOnSocialiteRegistration
{
    /**
     * Handle the event when a user registers via Socialite.
     * Creates the workflow_user role if it doesn't exist and assigns it to the user.
     * Also creates all necessary workflow permissions.
     */
    public function handle(Registered $event): void
    {
        $user = $event->socialiteUser->getUser();

        // Create or retrieve the workflow_user role
        $role = Role::firstOrCreate(
            ['name' => 'workflow_user', 'guard_name' => 'web']
        );

        // Create workflow permissions if they don't exist
        $permissions = [
            'ViewAny:Workflow',
            'View:Workflow',
            'Create:Workflow',
            'Update:Workflow',
            'Delete:Workflow',
            'Restore:Workflow',
            'ForceDelete:Workflow',
            'ForceDeleteAny:Workflow',
            'RestoreAny:Workflow',
            'Replicate:Workflow',
            'Reorder:Workflow',
        ];

        foreach ($permissions as $permissionName) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
            $role->givePermissionTo($permission);
        }

        // Assign role to user
        $user->assignRole($role);
    }
}
