<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZuoraService
{

    public function listWorkflows ( string $clientId, string $clientSecret, string $baseUrl = 'https://rest.zuora.com', int $page = 1, int $pageSize = 12 ) : array
    {
        $token = $this -> getAccessToken ( $clientId, $clientSecret, $baseUrl );

        $response = Http ::withToken ( $token )
                         -> get ( $baseUrl . '/v1/workflows', [
                             'page'     => $page,
                             'pageSize' => $pageSize,
                         ] );

        if ( $response -> failed () ) {
            throw new Exception( 'Failed to list workflows: ' . $response -> body () );
        }

        return $response -> json ();
    }

    public function getAccessToken ( string $clientId, string $clientSecret, string $baseUrl = 'https://rest.zuora.com' ) : string
    {
        $cacheKey = 'zuora_access_token_' . md5 ( $clientId . $clientSecret );

        return Cache ::remember ( $cacheKey, 3600, function () use ( $clientId, $clientSecret, $baseUrl ) { // Cache for 1 hour
            if ( !$clientId || !$clientSecret ) {
                throw new Exception( 'Zuora credentials not provided' );
            }

            $response = Http ::asForm () -> post ( $baseUrl . '/oauth/token', [
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

    public function downloadWorkflow ( string $clientId, string $clientSecret, string $baseUrl = 'https://rest.zuora.com', int $workflowId ) : array
    {
        $token = $this -> getAccessToken ( $clientId, $clientSecret, $baseUrl );

        $response = Http ::withToken ( $token )
                         -> get ( $baseUrl . "/v1/workflows/{$workflowId}/export" );

        if ( $response -> failed () ) {
            throw new Exception( 'Failed to download workflow: ' . $response -> body () );
        }

        return $response -> json ();
    }
}
