<x-filament-panels::page.simple>
    @if (filament()->hasPlugin('filament-socialite'))
        <x-filament-socialite::buttons :show-divider="false"/>
    @endif
</x-filament-panels::page.simple>
