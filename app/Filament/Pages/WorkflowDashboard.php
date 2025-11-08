<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\ZuoraService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Exception;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

//use Filament\Forms\Form;

//use Filament\Tables\Actions\Action as TableAction;

class WorkflowDashboard extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title            = 'Workflow Dashboard';
    protected static ?string $navigationLabel  = 'Workflows';
    protected static ?int    $navigationSort   = 2;
    public ?Customer         $selectedCustomer = null;

    public function getView () : string
    {
        return 'filament.pages.workflow-dashboard';
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
