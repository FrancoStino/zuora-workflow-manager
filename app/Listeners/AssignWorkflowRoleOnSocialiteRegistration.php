<?php

namespace App\Listeners;

use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignWorkflowRoleOnSocialiteRegistration
{
    /**
     * Handle the event when a user registers via Socialite.
     */
    public function handle(Registered $event): void
    {
        $user = $event->socialiteUser->getUser();

        // Create or retrieve the "workflow_user" role
        $role = Role::firstOrCreate(
            ['name' => 'workflow_user', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );

        // Assign workflow permissions to the role
        $this->assignWorkflowPermissions($role);

        // Assign the role to the user
        $user->assignRole($role);
    }

    /**
     * Assign workflow-related permissions to the role.
     */
    private function assignWorkflowPermissions(Role $role): void
    {
        $permissions = [
            'ViewAny:Workflow',
            'View:Workflow',
            //            'Create:Workflow',
            //            'Update:Workflow',
            //            'Delete:Workflow',
            //            'Restore:Workflow',
            //            'ForceDelete:Workflow',
            //            'ForceDeleteAny:Workflow',
            //            'RestoreAny:Workflow',
            //            'Replicate:Workflow',
            //            'Reorder:Workflow',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
