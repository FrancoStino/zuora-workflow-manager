@props(['workflow'])

@php
    $disabled = empty($workflow->json_export);
    $jsonString = $disabled ? '' : json_encode($workflow->json_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $fileName = $workflow->name . '.json';
@endphp

<div x-data="{
    jsonData: @js($jsonString),
    fileName: @js($fileName),
    
    downloadWorkflow() {
        if (!this.jsonData) return;
        
        const blob = new Blob([this.jsonData], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = this.fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        new FilamentNotification()
            .title('Success')
            .body('Workflow downloaded successfully')
            .success()
            .send();
    }
}">
    <x-filament::button
        color="primary"
        icon="heroicon-o-arrow-down-tray"
        @click="downloadWorkflow()"
        :disabled="$disabled"
        :tooltip="$disabled ? 'No JSON export available for this workflow' : 'Download workflow JSON'"
    >
        Download Workflow
    </x-filament::button>
</div>
