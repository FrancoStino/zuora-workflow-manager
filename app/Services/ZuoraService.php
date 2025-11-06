<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZuoraService
{
    private string $baseUrl = 'https://rest.zuora.com';

    public function listWorkflows ( int $page = 1, int $pageSize = 12 ) : array
    {
        $token = $this -> getAccessToken ();

        $response = Http ::withToken ( $token )
                         -> get ( $this -> baseUrl . '/v1/workflows', [
                             'page'     => $page,
                             'pageSize' => $pageSize,
                         ] );

        if ( $response -> failed () ) {
            throw new Exception( 'Failed to list workflows: ' . $response -> body () );
        }

        return $response -> json ();
    }

    public function getAccessToken () : string
    {
        return Cache ::remember ( 'zuora_access_token', 3600, function () { // Cache for 1 hour
            $clientId     = config ( 'services.zuora.client_id' );
            $clientSecret = config ( 'services.zuora.client_secret' );

            if ( !$clientId || !$clientSecret ) {
                throw new Exception( 'Zuora credentials not configured' );
            }

            $response = Http ::asForm () -> post ( $this -> baseUrl . '/oauth/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ] );

            if ( $response -> failed () ) {
                throw new Exception( 'Failed to authenticate with Zuora: ' . $response -> body () );
            }

            return $response -> json ()[ 'access_token' ];
        } );
    }

    public function downloadWorkflow ( int $workflowId ) : array
    {
        $token = $this -> getAccessToken ();

        $response = Http ::withToken ( $token )
                         -> get ( $this -> baseUrl . "/v1/workflows/{$workflowId}/export" );

        if ( $response -> failed () ) {
            throw new Exception( 'Failed to download workflow: ' . $response -> body () );
        }

        return $response -> json ();
    }
}
