<x-filament-widgets::widget>
    <x-filament::section
            :heading="__('Workflow JSON')"
            :description="__('Workflow definition in JSON format - Loaded asynchronously from Zuora')"
            icon="heroicon-o-code-bracket"
            collapsible
    >
        @if($json)
            <div class="space-y-4">
                <div class="flex justify-end">
                    <x-filament::button
                            icon="heroicon-o-clipboard"
                            outlined
                            size="sm"
                            wire:click="copyToClipboard"
                    >
                        {{ __('Copy to Clipboard') }}
                    </x-filament::button>
                </div>

                <div class="overflow-auto rounded-lg border border-gray-300 dark:border-gray-700">
                    <pre class="bg-gray-950 text-gray-100 p-4 text-xs"><code
                                class="language-json">{{ $json }}</code></pre>
                </div>
            </div>
        @else
            <div class="py-8 text-center">
                <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="mx-auto h-12 w-12 text-gray-400"
                />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Unable to load workflow JSON data from Zuora') }}
                </p>
                <p class="mt-1 text-xs text-gray-400">
                    {{ __('Please check your connection and try refreshing the page') }}
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
