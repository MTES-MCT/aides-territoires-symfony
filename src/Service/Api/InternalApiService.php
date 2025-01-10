<?php

namespace App\Service\Api;

use App\Service\Various\ParamService;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class InternalApiService
{
    public const API_FOLDER = 'api';
    private string $bearerToken = '';

    public function __construct(
        private RequestStack $requestStack,
        private ParamService $paramService
    ) {
    }

    private function getAPiBaseUrl(): string
    {
        return 'https://aides-territoires.beta.gouv.fr';
        try {
            $request = $this->requestStack->getCurrentRequest();
            $baseUrl =  $request->getSchemeAndHttpHost();
            // gestion problème Docker
            if (preg_match('/localhost:8080/', $baseUrl)) {
                $baseUrl = 'http://172.27.0.4'; // DevSkim: ignore DS137138
            }
            return $baseUrl;
        } catch (\Exception $e) {
            return '';
        }
    }

    private function getBearerToken(bool $force = false): string
    {
        if ($this->bearerToken !== '' && !$force) {
            return $this->bearerToken;
        }

        try {
            // créer le client pour appeller l'api
            $client = new Client([
                'base_uri' => $this->getAPiBaseUrl(),
                'headers' => [
                    'X-AUTH-TOKEN' => $this->paramService->get('at_x_auth_token'),
                    'Accept' => 'application/json',
                ],
            ]);

            // fait l'appel
            $response = $client->request('POST', '/' . self::API_FOLDER . '/connexion/', []);

            // retour
            $this->bearerToken = json_decode($response->getBody()->getContents())->token;
            return $this->bearerToken;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Undocumented function
     *
     * @param string $url
     * @param array<string, mixed>|null $params
     * @param string $method
     * @return string
     */
    public function callApi(
        string $url,
        ?array $params = null,
        string $method = 'GET'
    ): string {
        // créer le client pour appeller l'api
        $client = new Client([
            'base_uri' => $this->getAPiBaseUrl(),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getBearerToken(),
                'Accept' => 'application/json',
            ],
        ]);

        // fait l'appel
        $response = $client->request($method, '/' . self::API_FOLDER . '' . $url, [
            'query' => $params,
        ]);

        if ($response->getStatusCode() == 401) { // token expiré, on relance
            $this->getBearerToken(true);
            return $this->callApi($url, $params, $method);
        }

        // retour
        return $response->getBody()->getContents();
    }
}
