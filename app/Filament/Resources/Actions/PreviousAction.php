<?php

namespace App\Filament\Resources\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class PreviousAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'previous';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel()
            ->icon(Heroicon::OutlinedArrowLeft)
            ->outlined()
            ->url(back()->getTargetUrl());
    }
}
