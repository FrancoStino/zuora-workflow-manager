<?php

namespace App\Console\Commands;

use App\Models\Workflow;
use App\Services\ZuoraService;
use Exception;
use Illuminate\Console\Command;

class SyncWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-workflows {--page=1} {--pageSize=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync workflows from Zuora API to local database';

    /**
     * Execute the console command.
     */
    public function handle ()
    {
        $zuoraService = new ZuoraService();

        $page     = (int) $this -> option ( 'page' );
        $pageSize = (int) $this -> option ( 'pageSize' );

        $this -> info ( "Fetching workflows from Zuora API (page {$page}, size {$pageSize})..." );

        try {
            $data = $zuoraService -> listWorkflows ( $page, $pageSize );

            $workflows = $data[ 'workflows' ] ?? [];
            $total     = $data[ 'total' ] ?? 0;

            $this -> info ( "Found {$total} workflows, processing page {$page}..." );

            foreach ( $workflows as $wf ) {
                Workflow ::updateOrCreate (
                    [ 'id' => $wf[ 'id' ] ],
                    [
                        'name'        => $wf[ 'name' ],
                        'description' => $wf[ 'description' ] ?? null,
                        'state'       => $wf[ 'state' ],
                        'created_on'  => $wf[ 'created_on' ],
                        'updated_on'  => $wf[ 'updated_on' ],
                    ]
                );
            }

            $this -> info ( 'Workflows synced successfully.' );

            if ( $data[ 'hasMore' ] ?? false ) {
                $this -> info ( 'Run again with --page=' . ( $page + 1 ) . ' to fetch more.' );
            }

        } catch ( Exception $e ) {
            $this -> error ( 'Error syncing workflows: ' . $e -> getMessage () );
            return 1;
        }

        return 0;
    }
}
