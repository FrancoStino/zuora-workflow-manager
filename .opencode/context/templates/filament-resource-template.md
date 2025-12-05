# Filament Resource Template

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\{ResourceName}\Pages;
use App\Filament\Resources\{ResourceName}\RelationManagers;
use App\Models\{ModelName};
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class {ResourceName}Resource extends Resource
{
    protected static ?string $model = {ModelName}::class;

    protected static ?string $navigationIcon = 'heroicon-o-{icon-name}';

    protected static ?string $navigationLabel = '{Navigation Label}';

    protected static ?string $modelLabel = '{Model Label}';

    protected static ?string $pluralModelLabel = '{Plural Model Label}';

    protected static ?string $navigationGroup = '{Navigation Group}';

    protected static ?int $navigationSort = {sort-order};

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Primary Information Section
                Forms\Components\Section::make('Primary Information')
                    ->description('Basic information about the {model label}')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter {model label} name')
                            ->helperText('This name will be displayed throughout the application'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Enter description')
                            ->helperText('Provide a detailed description'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'draft' => 'Draft',
                            ])
                            ->required()
                            ->default('active')
                            ->reactive(),
                    ])
                    ->columns(2),

                // Relationships Section
                Forms\Components\Section::make('Relationships')
                    ->description('Configure related entities')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Customer Name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Select the customer associated with this {model label}'),

                        Forms\Components\CheckboxList::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->columns(2)
                            ->helperText('Select relevant tags for categorization'),
                    ])
                    ->columns(2),

                // Configuration Section
                Forms\Components\Section::make('Configuration')
                    ->description('Advanced configuration options')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this {model label}'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->displayFormat('M d, Y H:i')
                            ->helperText('When this {model label} should be published'),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->addButtonLabel('Add metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Add custom metadata as key-value pairs'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ({ModelName} $record): string => $record->name),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'draft',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
                    ]),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('published_at')
                    ->form([
                        Forms\Components\DatePicker::make('published_from')
                            ->label('Published from'),
                        Forms\Components\DatePicker::make('published_until')
                            ->label('Published until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                
                // Custom Actions
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function ({ModelName} $record) {
                        $duplicate = $record->replicate();
                        $duplicate->name = $duplicate->name . ' (Copy)';
                        $duplicate->save();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate {Model Label}')
                    ->modalDescription('Are you sure you want to duplicate this {model label}?')
                    ->modalSubmitActionLabel('Yes, duplicate it'),

                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-download')
                    ->color('success')
                    ->action(function ({ModelName} $record) {
                        // Export logic here
                    })
                    ->form([
                        Forms\Components\Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'json' => 'JSON',
                                'csv' => 'CSV',
                                'pdf' => 'PDF',
                            ])
                            ->default('json')
                            ->required(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    
                    // Custom Bulk Actions
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'draft' => 'Draft',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['status' => $data['status']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-download')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('format')
                                ->label('Export Format')
                                ->options([
                                    'json' => 'JSON',
                                    'csv' => 'CSV',
                                ])
                                ->default('csv')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // Export logic here
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No {model labels} found')
            ->emptyStateDescription('Create your first {model label} to get started.')
            ->emptyStateIcon('heroicon-o-{icon-name}');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\{RelationName}RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\List{ResourceName}::route('/'),
            'create' => Pages\Create{ResourceName}::route('/create'),
            'view' => Pages\View{ResourceName}::route('/{record}'),
            'edit' => Pages\Edit{ResourceName}::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
```

## Page Templates

### List Page
```php
<?php

namespace App\Filament\Resources\{ResourceName}\Pages;

use App\Filament\Resources\{ResourceName}Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class List{ResourceName} extends ListRecords
{
    protected static string $resource = {ResourceName}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Import File')
                        ->acceptedFileTypes(['.csv', '.xlsx'])
                        ->helperText('Upload a CSV or Excel file to import {model labels}'),
                ])
                ->action(function (array $data) {
                    // Import logic here
                }),

            Actions\Action::make('export')
                ->label('Export All')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('format')
                        ->label('Export Format')
                        ->options([
                            'csv' => 'CSV',
                            'xlsx' => 'Excel',
                            'json' => 'JSON',
                        ])
                        ->default('csv')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Export logic here
                }),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'active' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            'inactive' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive')),
            'draft' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
        ];
    }
}
```

### Create/Edit Page
```php
<?php

namespace App\Filament\Resources\{ResourceName}\Pages;

use App\Filament\Resources\{ResourceName}Resource;
use Filament\Actions;
use Filament\Resources\Pages\{CreateRecord, EditRecord};

class Create{ResourceName} extends CreateRecord
{
    protected static string $resource = {ResourceName}Resource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '{Model Label} created successfully';
    }

    protected function afterCreate(): void
    {
        // Post-creation logic
        // Send notifications, trigger workflows, etc.
    }
}

class Edit{ResourceName} extends EditRecord
{
    protected static string $resource = {ResourceName}Resource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '{Model Label} updated successfully';
    }

    protected function afterSave(): void
    {
        // Post-save logic
        // Clear caches, send notifications, etc.
    }
}
```

## Best Practices

### Form Design
- Group related fields in sections
- Use appropriate field types for data
- Provide helpful labels and descriptions
- Implement proper validation
- Use reactive forms for dynamic behavior

### Table Optimization
- Use searchable columns for text fields
- Implement proper sorting for sortable columns
- Add relevant filters for data filtering
- Use toggleable columns for optional fields
- Implement bulk actions for efficiency

### User Experience
- Provide clear empty states
- Use consistent icons and colors
- Implement proper loading states
- Add confirmation dialogs for destructive actions
- Provide helpful tooltips and descriptions

### Performance
- Use eager loading for relationships
- Implement proper indexing for database queries
- Cache frequently accessed data
- Optimize table queries with proper limits
- Use lazy loading for large datasets