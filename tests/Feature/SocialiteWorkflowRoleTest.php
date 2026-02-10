<?php

namespace Tests\Feature;

use App\Listeners\AssignWorkflowRoleOnSocialiteRegistration;
use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialiteWorkflowRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_role_is_assigned_on_socialite_registration(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Mock the Socialite OAuth user (Laravel\Socialite\Contracts\User)
        $mockOauthUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $mockOauthUser->shouldReceive('getAvatar')->andReturn(null);

        // Create a mock SocialiteUser (Filament Socialite internal user)
        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getUser')->andReturn($user);

        // Create the Registered event (provider, oauthUser, socialiteUser)
        $event = new Registered(
            'google',
            $mockOauthUser,
            $socialiteUser
        );

        // Call the listener
        $listener = new AssignWorkflowRoleOnSocialiteRegistration;
        $listener->handle($event);

        // Verify the role was created
        $this->assertTrue(Role::where('name', 'workflow_user')->exists());

        // Verify the user has the role
        $this->assertTrue($user->hasRole('workflow_user'));

        // Verify the role has workflow permissions
        $role = Role::findByName('workflow_user');
        $this->assertTrue($role->hasPermissionTo('ViewAny:Workflow'));
        $this->assertTrue($role->hasPermissionTo('View:Workflow'));
        $this->assertTrue($role->hasPermissionTo('Create:Workflow'));
        $this->assertTrue($role->hasPermissionTo('Update:Workflow'));
        $this->assertTrue($role->hasPermissionTo('Delete:Workflow'));
    }

    public function test_workflow_permissions_are_created(): void
    {
        $user = User::factory()->create();

        $mockOauthUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $mockOauthUser->shouldReceive('getAvatar')->andReturn(null);

        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getUser')->andReturn($user);

        $event = new Registered(
            'google',
            $mockOauthUser,
            $socialiteUser
        );

        $listener = new AssignWorkflowRoleOnSocialiteRegistration;
        $listener->handle($event);

        // Verify all permissions exist
        $expectedPermissions = [
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

        foreach ($expectedPermissions as $permission) {
            $this->assertTrue(
                \Spatie\Permission\Models\Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' does not exist"
            );
        }
    }

    public function test_multiple_registrations_do_not_duplicate_role(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $mockOauthUser1 = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $mockOauthUser1->shouldReceive('getAvatar')->andReturn(null);

        $socialiteUser1 = \Mockery::mock(SocialiteUser::class);
        $socialiteUser1->shouldReceive('getUser')->andReturn($user1);

        $event1 = new Registered('google', $mockOauthUser1, $socialiteUser1);

        $listener = new AssignWorkflowRoleOnSocialiteRegistration;
        $listener->handle($event1);

        $mockOauthUser2 = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $mockOauthUser2->shouldReceive('getAvatar')->andReturn(null);

        $socialiteUser2 = \Mockery::mock(SocialiteUser::class);
        $socialiteUser2->shouldReceive('getUser')->andReturn($user2);

        $event2 = new Registered('google', $mockOauthUser2, $socialiteUser2);
        $listener->handle($event2);

        // Verify only one role exists
        $this->assertEquals(1, Role::where('name', 'workflow_user')->count());

        // Verify both users have the role
        $this->assertTrue($user1->hasRole('workflow_user'));
        $this->assertTrue($user2->hasRole('workflow_user'));
    }
}
