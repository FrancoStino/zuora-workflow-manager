@props(['jsonData'])

@php
    $jsonString = is_string($jsonData) ? $jsonData : json_encode($jsonData);
@endphp

<div
    class="mb-4"
    x-data="{
        loading: false,
        copied: false,
        jsonData: @js($jsonString),

        async copyJson() {
            if (this.loading) return;

            this.loading = true;

            try {
                await navigator.clipboard.writeText(this.jsonData);
                this.copied = true;

                new FilamentNotification()
                    .title('Success')
                    .body('JSON copied to clipboard')
                    .success()
                    .send();

                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                console.error('Failed to copy:', error);

                new FilamentNotification()
                    .title('Error')
                    .body('Failed to copy JSON to clipboard')
                    .danger()
                    .send();
            } finally {
                this.loading = false;
            }
        }
    }"
>
    <x-filament::button
        color="primary"
        @click="copyJson()"
        x-bind:disabled="loading"
    >
        <div class="flex items-center gap-2">
            <!-- Loading indicator -->
            <x-filament::loading-indicator
                x-show="loading"
                x-cloak
                class="h-5 w-5"
            />

            <!-- Clipboard icon -->
            <x-filament::icon
                x-show="!loading && !copied"
                x-cloak
                icon="heroicon-o-clipboard-document"
                class="h-5 w-5"
            />

            <!-- Check icon -->
            <x-filament::icon
                x-show="!loading && copied"
                x-cloak
                icon="heroicon-o-check-circle"
                class="h-5 w-5"
            />

            <span x-text="copied ? 'Copied!' : 'Copy JSON'"></span>
        </div>
    </x-filament::button>
</div>
