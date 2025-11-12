<?php

namespace App\Filament\Pages;

use App\Exceptions\SetupException;
use App\Models\AppSetting;
use App\Models\User;
use App\Rules\ValidateDomain;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Setup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

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
        if ($this->isSetupCompleted()) {
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
            ->columns(1)
            ->schema([
                Wizard::make([
                    Step::make('Welcome')
                        ->description('Welcome to Zuora Workflows Setup')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Placeholder::make('welcome')
                                        ->content(new HtmlString('Welcome to Zuora Workflows! This wizard will help you set up your application. You will create the first administrator account and configure OAuth and Zuora settings.')),
                                ]),
                        ]),
                    Step::make('OAuth Configuration')
                        ->description('Configure OAuth allowed domains')
                        ->columns(1)
                        ->schema([
                            Placeholder::make('oauth_info')
                                ->content(new HtmlString('Configure which email domains are allowed to login via Google OAuth. Leave empty to allow all domains.')),
                            TagsInput::make('oauth_domains')
                                ->label('Allowed Email Domains')
                                ->placeholder('example.com, company.com')
                                ->helperText('Enter comma-separated domains (e.g., example.com, company.com). Leave empty to allow all domains.')
                                ->separator(',')
                                ->splitKeys(['Tab', ' ', ','])
                                ->trim()
                                ->rules(['array', new ValidateDomain])
                                ->required()
                                ->prefix('https://(www.)?')
                                ->suffixIcon(Heroicon::GlobeAlt),
                        ]),
                    Step::make('Admin Account')
                        ->description('Create your administrator account')
                        ->columns(1)
                        ->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique(User::class, 'email')
                                ->maxLength(255),
                        ]),
                    Step::make('Summary')
                        ->description('Review and complete the setup')
                        ->schema([
                            Placeholder::make('summary')
                                ->content(new HtmlString('You are about to complete the setup. Please review the information and click "Complete Setup" to finalize the process.')),
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
     * @throws SetupException
     */
    public function completeSetup(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $user = $this->createAdminUser($data);
            $this->generateShieldRolesIfNeeded();
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
     * @param  array<string, mixed>  $data  Setup form data
     */
    private function createAdminUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => null,
        ]);
    }

    /**
     * Generate Shield roles and permissions if they don't exist.
     *
     * @throws SetupException
     */
    private function generateShieldRolesIfNeeded(): void
    {
        if (Role::count() > 0) {
            return;
        }

        Log::info('Generating Shield roles and permissions.');

        try {

            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--option' => 'policies_and_permission',
            ]);

            // Generate roles and permissions for both panels
            Artisan::call('shield:super-admin', [
                '--user' => 1,
                '--panel' => 'admin',
            ]);

            Log::info('Shield roles generated successfully.');
            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        } catch (\Exception $e) {
            Log::error('Failed to generate Shield roles: '.$e->getMessage());
            throw new SetupException('Could not generate Shield roles. '.$e->getMessage());
        }
    }

    /**
     * Save OAuth domain configuration if provided.
     *
     * @param  array<string, mixed>  $data  Setup form data
     */
    private function saveOAuthConfiguration(array $data): void
    {
        if (empty($data['oauth_domains'])) {
            return;
        }

        AppSetting::setOAuthDomains($data['oauth_domains']);
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
