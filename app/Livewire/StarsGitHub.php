<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class StarsGitHub extends Component
{
    public function render()
    {
        $stars = $this->getStars();
        if ($stars === null || $stars == 0) {
            return <<<'BLADE'
<div class="hidden"></div>
BLADE;

        }

        return <<<'BLADE'
        <x-filament::badge
                    size="sm"
                    color="gold"
                >

            {{ $this->getStars() }}

            </x-filament::badge>
        BLADE;
    }

    public function getStars(): mixed
    {
        return Cache::remember('github_stars', 86400, static function () {
            try {
                $response = Http::timeout(5)
                    ->get('https://img.shields.io/github/stars/FrancoStino/zuora-workflows.json');

                return $response->successful()
                    ? $response['value']
                    : null;
            } catch (Exception) {
                return null;
            }
        });
    }
}
