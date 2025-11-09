<x-filament-panels::page>
    <div class="mb-4">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.pages.workflows') }}"
            icon="heroicon-o-arrow-left"
            color="gray"
        >
            Back
        </x-filament::button>
    </div>

    @if($error)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600 dark:text-danger-400">
                <x-filament::icon
                    icon="heroicon-o-exclamation-circle"
                    class="h-6 w-6"
                />
                <span class="font-medium">{{ $error }}</span>
            </div>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
