<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class WorkflowDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'workflows';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $title = 'Workflows Dashboard';

    protected static ?string $navigationLabel = 'Workflows';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.workflow-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_url')
                    ->label('Base URL')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('view_workflows')
                    ->label('View Workflows')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Customer $record) => route('filament.admin.pages.workflows').'/'.$record->name)
                    ->openUrlInNewTab(false),
            ])
            ->paginated([10, 25, 50]);
    }
}
