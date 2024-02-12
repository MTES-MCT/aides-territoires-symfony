<?php

namespace App\Service\Api;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class InternalApiService
{
    const API_FOLDER = 'api';

    public function  __construct(
        private RequestStack $requestStack
    )
    {    
    }

    private function getAPiBaseUrl(): string
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $baseUrl =  $request->getSchemeAndHttpHost();
            // gestion problème Docker
            if (preg_match('/localhost:8080/', $baseUrl)) {
                $baseUrl = 'http://172.27.0.4';
            }
            return $baseUrl;
        } catch (\Exception $e) {
            return '';
        }


    }

    private function getBearerToken(): string
    {
        try {
            // créer le client pour appeller l'api
            $client = new Client([
                'base_uri' => $this->getAPiBaseUrl(),
                'headers' => [
                    'X-AUTH-TOKEN' => '123456',
                    'Accept' => 'application/json',
                ],
            ]);

            // fait l'appel
            $response = $client->request('POST', '/'.self::API_FOLDER.'/connexion/', [
            ]);

            // retour
            return json_decode($response->getBody()->getContents())->token;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function callApi(
        string $url,
        ?array $params = null,
        string $method = 'GET'
    )
    {
        // créer le client pour appeller l'api
        $client = new Client([
            'base_uri' => $this->getAPiBaseUrl(),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getBearerToken(),
                'Accept' => 'application/json',
            ],
        ]);

        // fait l'appel
        $response = $client->request($method, '/'.self::API_FOLDER.''.$url, [
            'query' => $params,
        ]);

        // retour
        return $response->getBody()->getContents();        
    }
}