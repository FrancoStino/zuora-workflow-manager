<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Livewire\Component;

class StarsGitHub extends Component
{

    public function render()
    {
        $stars = $this->getStars();
        if ($stars === null) {
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
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . config('services.github.token'),
                        'Accept' => 'application/vnd.github.v3+json',
                    ])
                    ->get('https://api.github.com/repos/FrancoStino/zuora-workflows');

                return $response->successful()
                    ? Number::abbreviate($response['stargazers_count'])
                    : null;
            } catch (Exception) {
                return null;
            }
        });
    }
}
