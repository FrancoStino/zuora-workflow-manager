<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Concerns\HasTaskInfolist;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewTask extends ViewRecord
{
    use HasTaskInfolist;

    protected static string $resource = TaskResource::class;

    public function getSubheading(): ?string
    {
        return "Workflow related to: {$this->record->workflow->name}";
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema($this->getTaskInfolistSchema());
    }

    public function getTitle(): Htmlable|string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
