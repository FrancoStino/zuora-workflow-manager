<?php

namespace App\Listeners;

use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Spatie\Permission\Models\Role;

class AssignWorkflowRoleOnSocialiteRegistration
{
    /**
     * Assigns a workflow role and its permissions to a newly registered Socialite user.
     *
     * Ensures the 'workflow_user' role and the required Workflow permissions exist (guard 'web'),
     * then attaches those permissions to the role and assigns the role to the user carried by the event.
     *
     * @param Registered $event The Socialite registration event containing the created user.
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