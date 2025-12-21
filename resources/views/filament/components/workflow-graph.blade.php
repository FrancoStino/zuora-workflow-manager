@props(['workflowData'])

@php
    $uniqueId = 'workflow-graph-' . uniqid('', true);
@endphp

<div class="w-full" style="overflow-x: hidden;">
    {{--
        IMPORTANT: The 'border' class is required for JointJS to properly calculate container bounds.
        Even with border-white/0 (completely transparent), JointJS needs the border property
        to determine the correct dimensions for rendering the workflow graph.
        Removing the border class entirely will cause the graph to not render.
    --}}
    <div id="{{ $uniqueId }}"
         class="border border-white/0 rounded-xl"
         style="height: 700px; min-height: 700px; max-width: 100%; overflow-x: auto; overflow-y: auto; position: relative;"
         data-workflow-container="{{ $uniqueId }}"
         data-workflow-data='@json($workflowData)'>
        <div class="flex items-center justify-center h-full p-8">
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-500">Loading workflow graph...</p>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            /**
             * Simple initialization for workflow graphs
             * wire:ignore on the container ensures the graph persists across Livewire updates
             */

            // Shared initialization function for workflow graphs
            function initializeWorkflowGraphs() {
                const containers = document.querySelectorAll('[data-workflow-container]');

                containers.forEach(function (container) {
                    // Skip if already initialized
                    if (container.hasAttribute('data-workflow-initialized')) {
                        return;
                    }

                    const containerId = container.getAttribute('data-workflow-container');
                    const workflowData = JSON.parse(container.getAttribute('data-workflow-data'));

                    console.log('Initializing workflow graph:', containerId);

                    // Function to attempt initialization with retry logic
                    function attemptInit(retryCount = 0) {
                        if (typeof window.initWorkflowGraph === 'undefined') {
                            if (retryCount < 50) { // Retry for up to 5 seconds (50 * 100ms)
                                setTimeout(() => attemptInit(retryCount + 1), 100);
                                return;
                            }
                            console.error('initWorkflowGraph not found on window object after retries');
                            container.innerHTML = `
                                <div class="p-8 text-center">
                                    <div class="text-red-600 font-semibold mb-2">Error: JavaScript not loaded</div>
                                    <div class="text-sm text-gray-600">The workflow graph component failed to load. Please refresh the page.</div>
                                </div>
                            `;
                            return;
                        }

                        try {
                            // Mark as initialized to prevent duplicate initialization
                            container.setAttribute('data-workflow-initialized', 'true');

                            // Small delay to ensure all assets are loaded
                            setTimeout(() => {
                                const result = window.initWorkflowGraph(containerId, workflowData);

                                if (result && !result.success) {
                                    console.error('Failed to initialize workflow graph:', result.error);
                                    container.innerHTML = `
                                        <div class="p-8 text-center">
                                            <div class="text-red-600 font-semibold mb-2">Error rendering graph</div>
                                            <div class="text-sm text-gray-600">${result.error}</div>
                                        </div>
                                    `;
                                }
                            }, 100);
                        } catch (error) {
                            console.error('Failed to initialize workflow graph:', error);
                            container.innerHTML = `
                                <div class="p-8 text-center">
                                    <div class="text-red-600 font-semibold mb-2">Error rendering graph</div>
                                    <div class="text-sm text-gray-600">${error.message}</div>
                                </div>
                            `;
                        }
                    }

                    attemptInit();
                });
            }

            // Initialize on DOMContentLoaded (for standard page loads)
            document.addEventListener('DOMContentLoaded', function () {
                console.log('DOMContentLoaded: Initializing workflow graphs');
                initializeWorkflowGraphs();
            });

            // Initialize on Livewire navigated (for Livewire-powered pages)
            document.addEventListener('livewire:navigated', function () {
                console.log('Livewire navigated: Initializing workflow graphs');
                initializeWorkflowGraphs();
            });
        </script>
    @endpush
@endonce
