<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class DocumentationButton extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function documentation(): Action
    {
        return Action::make('documentation')
            ->color('primary')
            ->label('Documentation')
            ->icon(Heroicon::OutlinedBookOpen)
            ->extraAttributes(['class' => 'w-full'])
            ->url('zuoraworkflows.mintlify.app', shouldOpenInNewTab: true);
    }

    public function render()
    {
        return <<<'HTML'
        <div class="space-y-2">
            {{ $this->documentation }}
        </div>
        HTML;
    }
}
