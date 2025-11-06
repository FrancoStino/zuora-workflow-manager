<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($this->getCustomers() as $customer)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-lg font-semibold mb-4">Workflows for {{ $customer->name }}</h2>
                    @php $workflows = $this->getWorkflowsForCustomer($customer) @endphp
                    @if(isset($workflows['error']))
                        <p class="text-red-500 dark:text-red-400">Error: {{ $workflows['error'] }}</p>
                    @elseif($workflows)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            State
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Updated
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($workflows as $workflow)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $workflow['id'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $workflow['name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($workflow['state'] === 'Active') bg-green-100 text-green-800
                                                    @elseif($workflow['state'] === 'Inactive') bg-gray-100 text-gray-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ $workflow['state'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $workflow['created_on'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $workflow['updated_on'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <button
                                                    onclick="downloadWorkflow({{ $workflow['id'] }}, '{{ $customer->client_id }}', '{{ $customer->client_secret }}', '{{ $customer->base_url }}')"
                                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Download
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No workflows found or error loading data.</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function downloadWorkflow( workflowId, clientId, clientSecret, baseUrl ) {
            fetch( '/api/zuora/download/' + workflowId + '?client_id=' + encodeURIComponent( clientId ) + '&client_secret=' + encodeURIComponent( clientSecret ) + '&base_url=' + encodeURIComponent( baseUrl ) )
                .then( response => response.json() )
                .then( data => {
                    // Handle download, perhaps open JSON in new tab or download file
                    const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
                    const url  = URL.createObjectURL( blob );
                    const a    = document.createElement( 'a' );
                    a.href     = url;
                    a.download = 'workflow_' + workflowId + '.json';
                    document.body.appendChild( a );
                    a.click();
                    document.body.removeChild( a );
                    URL.revokeObjectURL( url );
                } )
                .catch( error => alert( 'Error downloading workflow: ' + error ) );
        }
    </script>
</x-filament-panels::page>
