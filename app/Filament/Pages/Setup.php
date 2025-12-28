<?php

namespace App\Filament\Pages;

use App\Exceptions\SetupException;
use App\Filament\Concerns\HasGeneralSettingsSchema;
use App\Models\User;
use App\Settings\GeneralSettings;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class Setup extends Page implements HasForms
{
    use HasGeneralSettingsSchema;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $routePath = 'setup';

    public ?array $data = [];

    public function getView(): string
    {
        return 'filament.pages.setup';
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function mount(): void
    {
        // Il middleware AuthenticateWithSetupBypass gestisce il redirect
        // se il setup è completato, quindi qui arriviamo solo se:
        // 1. Setup non completato, oppure
        // 2. Setup completato + parametro ?reset presente
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('Welcome')
                        ->description('Welcome to Zuora Workflows Setup')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextEntry::make('welcome')
                                        ->state(new HtmlString('Welcome to Zuora Workflows! This wizard will help you set up your application. You will create the first administrator account and configure OAuth and Zuora settings.')),
                                ]),
                        ]),
                    Step::make('OAuth Configuration')
                        ->description('Configure Google OAuth settings')
                        ->columns(1)
                        ->schema($this->getOAuthFields()), // Reuse OAuth schema components
                    Step::make('Admin Account')
                        ->description('Create your administrator account')
                        ->columns(1)
                        ->schema([
                            ...$this->getApplicationFields(),
                        ]),
                    Step::make('Summary')
                        ->description('Review and complete the setup')
                        ->schema([
                            TextEntry::make('summary')
                                ->state(new HtmlString('You are about to complete the setup. Please review the information and click "Complete Setup" to finalize the process.')),
                        ]),
                ])
                    ->submitAction(
                        Action::make('completeSetup')
                            ->label('Complete Setup')
                            ->action('completeSetup')
                    )
                    ->columnSpan('full'),
            ])
            ->statePath('data');
    }

    /**
     * Complete the setup process by creating user, assigning roles, and finalizing configuration.
     * Orchestrates all setup steps with transactional integrity.
     *
     * @throws SetupException|Throwable
     */
    public function completeSetup(GeneralSettings $settings): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $user = $this->createAdminUser($data);
            $this->generateShieldRolesIfNeeded($user);
            $this->saveOAuthConfiguration($data, $settings);
            $this->markSetupAsCompleted();

            DB::commit();

            $this->logSetupCompletion($user);
            $this->notifySuccess();
            Auth::login($user);

            $this->redirect('/');

        } catch (SetupException $e) {
            DB::rollBack();
            $this->notifyFailure($e->getMessage());
        }
    }

    /**
     * Create the initial admin user with provided credentials.
     *
     * @param  array<string, mixed>  $data  Setup form data
     */
    private function createAdminUser(array $data): User
    {
        $user = User::where('email', $data['admin_default_email'])->first();

        if ($user) {
            $user->update([
                'name' => $data['name'],
                'surname' => $data['surname'],
            ]);

            if (! empty($data['admin_password'])) {
                $user->update(['password' => bcrypt($data['admin_password'])]);
            }

            return $user;
        }

        return User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['admin_default_email'],
            'password' => ! empty($data['admin_password']) ? bcrypt($data['admin_password']) : null,
        ]);
    }

    /**
     * Generate Shield roles and permissions if they don't exist.
     *
     * @param  User  $user  The admin user to assign super-admin role
     *
     * @throws SetupException|BindingResolutionException
     */
    private function generateShieldRolesIfNeeded(User $user): void
    {
        if (Role::count() > 0) {
            return;
        }

        Log::info('Generating Shield roles and permissions.');

        try {

            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--option' => 'policies_and_permissions',
            ]);

            // Generate roles and permissions for both panels
            Artisan::call('shield:super-admin', [
                '--user' => $user->id,
                '--panel' => 'admin',
            ]);

            Log::info('Shield roles generated successfully.');

            $this->createWorkflowUserRole();

            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        } catch (Exception $e) {
            Log::error('Failed to generate Shield roles: '.$e->getMessage());
            throw new SetupException('Could not generate Shield roles. '.$e->getMessage());
        }
    }

    /**
     * Create the workflow_user role with necessary permissions.
     */
    private function createWorkflowUserRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'workflow_user', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );

        // Assign workflow permissions to the role
        $permissions = [
            'ViewAny:Workflow',
            'View:Workflow',
            'ViewAny:Task',
            'View:Task',
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

        Log::info('Workflow user role created with permissions.', [
            'role_id' => $role->id,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Save OAuth configuration to settings.
     *
     * @param  array<string, mixed>  $data  Setup form data
     */
    private function saveOAuthConfiguration(array $data, GeneralSettings $settings): void
    {
        $settings->oauth_enabled = $data['oauth_enabled'] ?? false;

        if ($settings->oauth_enabled) {
            $settings->oauth_google_client_id = $data['oauth_google_client_id'] ?? '';
            $settings->oauth_google_client_secret = $data['oauth_google_client_secret'] ?? '';
            $settings->oauth_allowed_domains = $data['oauth_allowed_domains'] ?? [];
        }

        $settings->admin_default_email = $data['admin_default_email'];

        $settings->save();
    }

    /**
     * Mark the setup process as completed in the database.
     */
    private function markSetupAsCompleted(): void
    {
        DB::table('setup_completed')->update([
            'completed' => true,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log setup completion details for audit trail.
     */
    private function logSetupCompletion(User $user): void
    {
        Log::info('Setup completed successfully', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'roles' => $user->getRoleNames()->toArray(),
            'permissions_count' => $user->getAllPermissions()->count(),
        ]);
    }

    /**
     * Send success notification to user.
     */
    private function notifySuccess(): void
    {
        Notification::make()
            ->title('Setup completed successfully!')
            ->success()
            ->send();
    }

    /**
     * Send error notification with failure message.
     *
     * @param  string  $message  Error message
     */
    private function notifyFailure(string $message): void
    {
        Notification::make()
            ->title('Setup failed')
            ->body($message)
            ->danger()
            ->send();
    }
}
