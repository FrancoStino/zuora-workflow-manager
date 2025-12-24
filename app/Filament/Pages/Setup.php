<?php

namespace App\Filament\Pages;

use App\Exceptions\SetupException;
use App\Models\User;
use App\Rules\ValidateDomain;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
        if ($this->isSetupCompleted() && !request()->has('reset')) {
            $this->redirectBasedOnAuthStatus();

            return;
        }

        $this->form->fill();
    }

    /**
     * Check if application setup is already completed.
     */
    private function isSetupCompleted(): bool
    {
        $setupRecord = DB::table('setup_completed')->first();

        return $setupRecord && $setupRecord->completed;
    }

    /**
     * Redirect user based on authentication status.
     */
    private function redirectBasedOnAuthStatus(): void
    {
        $redirectPath = Auth::check() ? '/' : '/login';
        $this->redirect($redirectPath);
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
                        ->schema([
                            TextEntry::make('oauth_info')
                                ->state(new HtmlString('Configure Google OAuth to allow users to login with their Google account. You need to create a Google OAuth application in the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-primary-600 underline">Google Cloud Console</a>.')),
                            Toggle::make('oauth_enabled')
                                ->label('Enable Google OAuth')
                                ->default(false)
                                ->live(),
                            TextInput::make('oauth_google_client_id')
                                ->label('Google Client ID')
                                ->required(fn(Get $get): bool => $get('oauth_enabled'))
                                ->visible(fn(Get $get): bool => $get('oauth_enabled'))
                                ->maxLength(255)
                                ->placeholder('xxxxx.apps.googleusercontent.com'),
                            TextInput::make('oauth_google_client_secret')
                                ->label('Google Client Secret')
                                ->required(fn(Get $get): bool => $get('oauth_enabled'))
                                ->visible(fn(Get $get): bool => $get('oauth_enabled'))
                                ->password()
                                ->revealable()
                                ->maxLength(255),
                            TextInput::make('oauth_google_redirect_url')
                                ->label('Google Redirect URL')
                                ->required(fn(Get $get): bool => $get('oauth_enabled'))
                                ->visible(fn(Get $get): bool => $get('oauth_enabled'))
                                ->url()
                                ->maxLength(255)
                                ->placeholder(url('/oauth/callback/google'))
                                ->helperText('Use this URL in your Google OAuth application configuration'),
                            Toggle::make('allowed_domains_checkbox')
                                ->label('Restrict access to specific domains?')
                                ->visible(fn(Get $get): bool => $get('oauth_enabled'))
                                ->live(),
                            TagsInput::make('oauth_domains')
                                ->label('Allowed Email Domains')
                                ->placeholder('example.com')
                                ->helperText('Enter domains (e.g., example.com, company.com). Press Enter or comma to add. Leave empty to allow all domains.')
                                ->prefix('https://(www.)?')
                                ->splitKeys(['Tab', ','])
                                ->trim()
                                ->rules(['array', new ValidateDomain()])
                                ->required(fn(Get $get): bool => $get('allowed_domains_checkbox'))
                                ->suffixIcon(Heroicon::GlobeAlt)
                                ->visible(fn(Get $get): bool => $get('oauth_enabled') && $get('allowed_domains_checkbox'))
                                ->reorderable(),
                        ]),
                    Step::make('Admin Account')
                        ->description('Create your administrator account')
                        ->columns(1)
                        ->schema([
                            TextInput::make('name')
                                ->label('First Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('surname')
                                ->label('Surname')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('admin_default_email')
                                ->label('Admin Default Email')
                                ->email()
                                ->required()
                                ->unique(User::class, 'email')
                                ->maxLength(255),
                            TextInput::make('admin_password')
                                ->label('Admin Password')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->helperText('Optional: Set a password for admin account (leave empty if using OAuth)'),
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
    public function completeSetup(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $user = $this->createAdminUser($data);
            $this->generateShieldRolesIfNeeded($user);
            $this->saveOAuthConfiguration($data);
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
     * @param array<string, mixed> $data Setup form data
     */
    private function createAdminUser(array $data): User
    {
        $user = User::where('email', $data['admin_default_email'])->first();

        if ($user) {
            $user->update([
                'name' => $data['name'],
                'surname' => $data['surname'],
            ]);

            if (!empty($data['admin_password'])) {
                $user->update(['password' => bcrypt($data['admin_password'])]);
            }

            return $user;
        }

        return User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['admin_default_email'],
            'password' => !empty($data['admin_password']) ? bcrypt($data['admin_password']) : null,
        ]);
    }

    /**
     * Generate Shield roles and permissions if they don't exist.
     *
     * @param User $user The admin user to assign super-admin role
     *
     * @throws SetupException
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
            Log::error('Failed to generate Shield roles: ' . $e->getMessage());
            throw new SetupException('Could not generate Shield roles. ' . $e->getMessage());
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

            if (!$role->hasPermissionTo($permission)) {
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
     * @param array<string, mixed> $data Setup form data
     */
    private function saveOAuthConfiguration(array $data): void
    {
        $now = now();

        // Update OAuth enabled status
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'oauth_enabled')
            ->update([
                'payload' => json_encode($data['oauth_enabled'] ?? false),
                'updated_at' => $now,
            ]);

        // Only save OAuth credentials if OAuth is enabled
        if (!empty($data['oauth_enabled'])) {
            // Update Google Client ID
            DB::table('settings')
                ->where('group', 'general')
                ->where('name', 'oauth_google_client_id')
                ->update([
                    'payload' => json_encode($data['oauth_google_client_id'] ?? ''),
                    'updated_at' => $now,
                ]);

            // Update Google Client Secret
            DB::table('settings')
                ->where('group', 'general')
                ->where('name', 'oauth_google_client_secret')
                ->update([
                    'payload' => json_encode($data['oauth_google_client_secret'] ?? ''),
                    'updated_at' => $now,
                ]);

            // Update Google Redirect URL
            DB::table('settings')
                ->where('group', 'general')
                ->where('name', 'oauth_google_redirect_url')
                ->update([
                    'payload' => json_encode($data['oauth_google_redirect_url'] ?? ''),
                    'updated_at' => $now,
                ]);

            // Update allowed domains (TagsInput always returns an array)
            DB::table('settings')
                ->where('group', 'general')
                ->where('name', 'oauth_allowed_domains')
                ->update([
                    'payload' => json_encode($data['oauth_domains'] ?? []),
                    'updated_at' => $now,
                ]);

        // Update admin default email in settings
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'admin_default_email')
            ->update([
                'payload' => json_encode($data['admin_default_email']),
                'updated_at' => $now,
            ]);
        }
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
     * @param string $message Error message
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
