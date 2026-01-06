<footer>
    <div class="flex flex-col lg:flex-row items-center justify-between gap-4 p-5">
        {{-- Copyright notice --}}
        <div class="text-sm">
            © {{ date('Y') }}
            <x-filament::link href="https://github.com/FrancoStino/zuora-workflows" target="_blank">
                Zuora Workflow Manager
            </x-filament::link>
            - All Rights Reserved.
        </div>

        {{-- Buttons --}}
        <div class="flex gap-4">
            <x-filament::button
                tag="a"
                href="https://github.com/FrancoStino/zuora-workflows"
                target="_blank"
                rel="noopener noreferrer"
                color="gray"
                outlined
                size="sm"
            >

                @livewire('stars-git-hub')

                <x-filament::icon-button
                    icon="heroicon-o-star"
                    color="gold"
                >
                </x-filament::icon-button>
                Star on GitHub
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="https://github.com/FrancoStino/zuora-workflows/issues"
                target="_blank"
                rel="noopener noreferrer"
                color="gray"
                outlined
                size="sm"
            >
                <x-filament::icon-button
                    icon="heroicon-o-exclamation-circle"
                    color="danger"
                >
                </x-filament::icon-button>
                Report Issue
            </x-filament::button>
        </div>
    </div>
</footer>
