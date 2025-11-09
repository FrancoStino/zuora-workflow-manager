<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Workflow;
use App\Services\ZuoraService;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerWorkflows extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug                     = 'workflows/{customer}';
    protected static bool    $shouldRegisterNavigation = false;
    public string            $customer;
    public ?Customer         $customerModel            = null;
    public ?string           $error                    = null;
    protected string         $view                     = 'filament.pages.customer-workflows';
    protected ?Collection    $workflowsData            = null;

    public function mount ( string $customer ) : void
    {
        $this -> customer      = $customer;
        $this -> customerModel = Customer ::where ( 'name', $customer ) -> first ();

        if ( !$this -> customerModel ) {
            abort ( 404, 'Customer not found' );
        }

        $this -> loadWorkflows ();
    }

    protected function loadWorkflows () : void
    {
        try {
            $service = new ZuoraService();
            $data    = $service -> listWorkflows (
                $this -> customerModel -> client_id,
                $this -> customerModel -> client_secret,
                $this -> customerModel -> base_url,
                1,
                100
            );

            $workflows = $data[ 'data' ] ?? $data[ 'workflows' ] ?? [];

            $this -> workflowsData = collect ( $workflows ) -> map ( function ( $workflow, $index ) {
                $id = $workflow[ 'id' ] ?? $index;
                return [
                    '__key'      => $id,
                    'key'        => $id,
                    'id'         => $id,
                    'name'       => $workflow[ 'name' ] ?? 'N/A',
                    'state'      => $workflow[ 'state' ] ?? $workflow[ 'status' ] ?? 'Unknown',
                    'created_on' => $workflow[ 'created_on' ] ?? null,
                    'updated_on' => $workflow[ 'updated_on' ] ?? null,
                ];
            } );
        } catch ( Exception $e ) {
            $this -> error         = $e -> getMessage ();
            $this -> workflowsData = collect ( [] );
        }
    }

    public function getTitle () : string
    {
        return "Workflows - {$this->customer}";
    }

    public function getHeading () : string
    {
        return "Workflows for {$this->customer}";
    }

    public function table ( Table $table ) : Table
    {
        return $table
            -> query ( $this -> getTableQuery () )
            -> columns ( [
                TextColumn ::make ( 'id' )
                           -> label ( 'ID' )
                           -> searchable ()
                           -> sortable (),
                TextColumn ::make ( 'name' )
                           -> label ( 'Name' )
                           -> searchable ()
                           -> sortable (),
                TextColumn ::make ( 'state' )
                           -> label ( 'State' )
                           -> badge ()
                           -> color ( fn ( string $state ) : string => match ( $state ) {
                               'Active' => 'success',
                               'Inactive' => 'gray',
                               default => 'danger',
                           } )
                           -> sortable (),
                TextColumn ::make ( 'created_on' )
                           -> label ( 'Created' )
                           -> dateTime ()
                           -> sortable (),
                TextColumn ::make ( 'updated_on' )
                           -> label ( 'Updated' )
                           -> dateTime ()
                           -> sortable (),
            ] )
            -> recordActions ( [
                Action ::make ( 'download' )
                       -> label ( 'Download' )
                       -> icon ( 'heroicon-o-arrow-down-tray' )
                       -> action ( function ( $record ) {
                           $workflowId = is_array ( $record ) ? $record[ 'id' ] : $record -> id;
                           $this -> downloadWorkflow ( $workflowId );
                       } ),
            ] )
            -> paginated ( [ 10, 25, 50, 100 ] );
    }

    protected function getTableQuery () : Builder
    {
        return Workflow ::query () -> whereRaw ( '1 = 0' );
    }

    public function downloadWorkflow ( string $workflowId )
    {
        try {
            $service  = new ZuoraService();
            $workflow = $service -> downloadWorkflow (
                $this -> customerModel -> client_id,
                $this -> customerModel -> client_secret,
                $this -> customerModel -> base_url,
                $workflowId
            );

            $fileName = "workflow_{$workflowId}.json";
            $content  = json_encode ( $workflow, JSON_PRETTY_PRINT );

            return response () -> streamDownload ( function () use ( $content ) {
                echo $content;
            }, $fileName, [
                'Content-Type' => 'application/json',
            ] );
        } catch ( Exception $e ) {
            Notification ::make ()
                         -> danger ()
                         -> title ( 'Download Failed' )
                         -> body ( "Error downloading workflow: " . $e -> getMessage () )
                         -> send ();
        }
    }

    public function getTableRecords () : Collection
    {
        return $this -> workflowsData ?? collect ( [] );
    }

    public function getTableRecordKey ( $record ) : string
    {
        return is_array ( $record ) ? ( $record[ '__key' ] ?? $record[ 'key' ] ) : $record -> key;
    }

}
