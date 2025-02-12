<?php

namespace App\Service\Api;

use App\Service\Various\ParamService;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class VappApiService
{
    private string $apiKey;
    private string $baseUrl;
    private Client $client;
    private const SESSION_PROJECT_UUID = 'vapp_project_uuid';

    public function __construct(
        private ParamService $paramService,
        private RequestStack $requestStack
    )
    {
        $this->baseUrl = $this->paramService->get('vapp_base_url');
        $this->apiKey = $this->paramService->get('vapp_api_key');
        $this->client = $this->createClient();
    }

    // créer le client pour appeller l'api
    private function createClient(): Client
    {
        return new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function createProject(
        string $description,
        string $porteur,
        array $zonesGeographiques
    ): void
    {
        try {
            $folder = 'projets';
            $method = 'POST';

            $datas = [
                'data' => [
                    'id' => $this->getProjectUuid(),
                    'description' => $description,
                    'porteur' => $porteur,
                    'zonesGeographiques' => $zonesGeographiques,
                    'etatAvancement' => 'IDEE'
                ]
            ];

            $this->client->request($method, $folder, [
                'json' => $datas,
            ]);
        } catch (\Exception $e) {
        }
    }

    public function scoreAids(array $aids): array
    {
        $folder = 'projets/' . $this->getProjectUuid() . '/aides/scoring';
        $method = 'POST';
        $datas = [
            'data' => []
        ];

        foreach ($aids as $aid) {
            $datas['data'][] = [
                'id' => (string) $aid['id'],
                'nom' => (string) $aid['name'],
                'description' => (string) $aid['description'],
                'fournisseurDonnees' => 'aides-territoires'
            ];
        }

        $response = $this->client->request($method, $folder, [
            'json' => $datas,
        ]);

        $datas = json_decode($response->getBody()->getContents(), true);
        return $datas['data'] ?? [];
    }

    private function getProjectUuid(): string
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $uuid = $session->get(self::SESSION_PROJECT_UUID, null);
        if (!$uuid) {
            $uuid = $this->generateUuid();
            $session->set(self::SESSION_PROJECT_UUID, $uuid);
        }

        return $uuid;
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}