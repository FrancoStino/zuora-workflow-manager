@props(['workflowData'])

@php
	$uniqueId = 'workflow-graph-' . uniqid('', true);
@endphp

<div data-graph-container="{{ $uniqueId }}" data-workflow='@json($workflowData)' class="w-full"
	 style="overflow-x: hidden;">
	<div id="{{ $uniqueId }}" class="border rounded-2xl"
		 style="height: 700px; min-height: 700px; max-width: 100%; overflow-x: auto; overflow-y: auto; position: relative;">
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
			document.addEventListener( 'DOMContentLoaded', function () {
				// Find all workflow graph containers and initialize them
				const containers = document.querySelectorAll( '[data-graph-container]' );

				containers.forEach( function ( wrapper ) {
					const containerId = wrapper.getAttribute( 'data-graph-container' );
					const workflowData = JSON.parse( wrapper.getAttribute( 'data-workflow' ) );

					const initGraph = () => {
						console.log( 'Workflow graph component initializing...' );
						console.log( 'Container ID:', containerId );
						console.log( 'workflowData:', workflowData );
						console.log( 'window.initWorkflowGraph exists:', typeof window.initWorkflowGraph !== 'undefined' );

						if ( typeof window.initWorkflowGraph === 'undefined' ) {
							console.error( 'initWorkflowGraph not found on window object' );
							const container = document.getElementById( containerId );
							if ( container ) {
								container.innerHTML = `
                        <div class="p-8 text-center">
                            <div class="text-red-600 font-semibold mb-2">Error: JavaScript not loaded</div>
                            <div class="text-sm text-gray-600">The workflow graph component failed to load. Please refresh the page.</div>
                        </div>
                    `;
							}
							return;
						}

						try {
							console.log( 'Calling initWorkflowGraph...' );
							window.initWorkflowGraph( containerId, workflowData );
							console.log( 'initWorkflowGraph completed successfully' );
						} catch ( error ) {
							console.error( 'Failed to initialize workflow graph:', error );
							const container = document.getElementById( containerId );
							if ( container ) {
								container.innerHTML = `
                        <div class="p-8 text-center">
                            <div class="text-red-600 font-semibold mb-2">Error rendering graph</div>
                            <div class="text-sm text-gray-600">${error.message}</div>
                        </div>
                    `;
							}
						}
					};

					// Cleanup function when component is destroyed
					const cleanup = () => {
						const container = document.getElementById( containerId );
						if ( container && typeof container._workflowCleanup === 'function' ) {
							container._workflowCleanup();
						}
					};

					// Setup cleanup on page unload and when tab changes
					window.addEventListener( 'beforeunload', cleanup );

					// Also cleanup when the wrapper element is removed from DOM
					const observer = new MutationObserver( ( mutations ) => {
						mutations.forEach( ( mutation ) => {
							if ( mutation.type === 'childList' ) {
								mutation.removedNodes.forEach( ( node ) => {
									if ( node === wrapper || node.contains?.( wrapper ) ) {
										cleanup();
										observer.disconnect();
									}
								} );
							}
						} );
					} );

					observer.observe( document.body, {
						childList: true,
						subtree: true
					} );

					// Delay to ensure all scripts are loaded
					setTimeout( initGraph, 100 );
				} );
			} );
		</script>
	@endpush
@endonce
