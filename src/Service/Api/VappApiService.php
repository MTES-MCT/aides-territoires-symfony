<?php

namespace App\Service\Api;

use App\Entity\Aid\Aid;
use App\Service\Various\ParamService;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class VappApiService
{
    private string $apiKey;
    private string $baseUrl;
    private Client $client;
    private const SESSION_PROJECT_UUID = 'vapp_project_uuid';
    public const SESSION_AIDS_SCORES = 'vapp_aids_scores';
    public const SESSION_CURRENT_PAGE_SCORE_VAPP = 'currentPageScoreVapp';

    public function __construct(
        private ParamService $paramService,
        private RequestStack $requestStack,
    ) {
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

    /**
     * @param string $description
     * @param string $porteur
     * @param array<string, mixed> $zonesGeographiques
     * @param boolean $force
     * @return string
     */
    public function getProjectUuid(
        string $description,
        string $porteur,
        array $zonesGeographiques,
        bool $force = false
    ): string {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $uuid = $force ? '' : $this->getProjectUuidInSession();
        if (!$uuid || '' == trim($uuid)) {
            $uuid = $this->createProject(
                description: $description,
                porteur: $porteur,
                zonesGeographiques: $zonesGeographiques
            );

            $session->set(self::SESSION_PROJECT_UUID, $uuid);
        }

        return $uuid;
    }

    private function getProjectUuidInSession(): string
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        return $session->get(self::SESSION_PROJECT_UUID, '');
    }

    /**
     * @param string $description
     * @param string $porteur
     * @param array<string, mixed> $zonesGeographiques
     * @return string
     */
    private function createProject(
        string $description,
        string $porteur,
        array $zonesGeographiques,
    ): string {
        try {
            $folder = 'projets';
            $method = 'POST';
            $uuid = $this->generateUuid();

            $datas = [
                'data' => [
                    'id' => $uuid,
                    'description' => $description,
                    'porteur' => $porteur,
                    'zonesGeographiques' => $zonesGeographiques,
                    'etatAvancement' => 'IDEE',
                ],
            ];

            $this->client->request($method, $folder, [
                'json' => $datas,
            ]);

            return $uuid;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param array<int, mixed> $aids
     * @return array<string, mixed>
     */
    public function scoreAids(array $aids): array
    {
        $folder = 'projets/' . $this->getProjectUuidInSession() . '/aides/scoring';
        $method = 'POST';
        $datas = [
            'data' => [],
        ];

        foreach ($aids as $aid) {
            $datas['data'][] = [
                'id' => (string) $aid['id'],
                'nom' => (string) $aid['name'],
                'description' => (string) $aid['description'],
                'fournisseurDonnees' => 'aides-territoires',
            ];
        }

        $response = $this->client->request($method, $folder, [
            'json' => $datas,
        ]);

        $datas = json_decode($response->getBody()->getContents(), true);

        return $datas['data'] ?? [];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * @return array<string, int|null>
     */
    public function getAidScoresInSession(Aid $aid): array
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $scores = json_decode($session->get(self::SESSION_AIDS_SCORES, '{}'), true);
        if (!isset($scores[$aid->getId()])) {
            return [
                'score_total' => null,
                'score_vapp' => null,
            ];
        } else {
            return [
                'score_total' => $scores[$aid->getId()]['score_total'] ?? null,
                'score_vapp' => $scores[$aid->getId()]['score_vapp'] ?? null,
            ];
        }
    }
}
