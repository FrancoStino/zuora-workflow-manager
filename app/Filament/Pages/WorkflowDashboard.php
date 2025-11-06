<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\ZuoraService;
use Exception;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class WorkflowDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title            = 'Workflow Dashboard';
    protected static ?string $navigationLabel  = 'Workflows';
    protected static ?int    $navigationSort   = 2;
    public ?Customer         $selectedCustomer = null;

    public static function canAccess () : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public function getView () : string
    {
        return 'filament.pages.workflow-dashboard';
    }

    public function mount () : void
    {
        if ( !auth () -> user () -> hasRole ( 'super_admin' ) ) {
            abort ( 403, 'Access denied' );
        }
    }

    public function getCustomers ()
    {
        return Customer ::all ();
    }

    public function getWorkflowsForCustomer ( Customer $customer )
    {
        try {
            $service = new ZuoraService();
            $data    = $service -> listWorkflows ( $customer -> client_id, $customer -> client_secret, $customer -> base_url, 1, 50 );
            return $data[ 'workflows' ] ?? [];
        } catch ( Exception $e ) {
            // For debugging, return error message
            return [ 'error' => $e -> getMessage () ];
        }
    }

    protected function getTableQuery () : Builder
    {
        // Not used since we have custom view
        return Customer ::query ();
    }

    protected function getTableColumns () : array
    {
        return [
            TextColumn ::make ( 'name' ),
            // Add more if needed
        ];
    }
}
