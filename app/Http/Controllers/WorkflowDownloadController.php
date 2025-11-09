<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ZuoraService;
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkflowDownloadController extends Controller
{
    public function download ( string $customer, string $workflowId, string $name = '' ) : StreamedResponse
    {
        try {
            $customerModel = Customer ::where ( 'name', $customer ) -> firstOrFail ();

            $service  = new ZuoraService();
            $workflow = $service -> downloadWorkflow (
                $customerModel -> client_id,
                $customerModel -> client_secret,
                $customerModel -> base_url,
                $workflowId
            );

            $fileName = "{$name}.json";
            $content  = json_encode ( $workflow, JSON_PRETTY_PRINT );

            return response () -> streamDownload (
                function () use ( $content ) {
                    echo $content;
                },
                $fileName,
                [
                    'Content-Type' => 'application/json',
                ]
            );
        } catch ( Exception $e ) {
            abort ( 500, "Error downloading workflow: " . $e -> getMessage () );
        }
    }
}
