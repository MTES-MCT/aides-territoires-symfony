<?php

namespace App\Controller\Admin\Statistics;

use App\Repository\Log\LogAidApplicationUrlClickRepository;
use App\Repository\Log\LogAidOriginUrlClickRepository;
use App\Repository\Log\LogAidSearchRepository;
use App\Repository\Log\LogAidSearchTempRepository;
use App\Repository\Log\LogAidViewRepository;
use App\Repository\Log\LogAidViewTempRepository;
use App\Repository\Site\AbTestRepository;
use App\Repository\Site\AbTestUserRepository;
use App\Repository\Site\AbTestVoteRepository;
use App\Service\Site\AbTestService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class VappController extends AbstractController
{
    #[Route('/admin/statistics/vapp', name: 'admin_statistics_vapp')]
    public function index(
        AbTestRepository $abTestRepository,
        AbTestUserRepository $abTestUserRepository,
        AbTestVoteRepository $abTestVoteRepository,
        LogAidSearchRepository $logAidSearchRepository,
        LogAidSearchTempRepository $logAidSearchTempRepository,
        LogAidViewRepository $logAidViewRepository,
        LogAidViewTempRepository $logAidViewTempRepository,
        LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository,
        LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository,
    ): Response {
        $dateStart = new \DateTime(date('2025-03-03'));
        $sources = ['vapp', 'at_test'];

        $vappFormulaire = $abTestRepository->findOneBy([
            'name' => AbTestService::VAPP_FORMULAIRE
        ]);

        // Nombre de participants at
        $nbUsersAt = $abTestUserRepository->count([
            'abTest' => $vappFormulaire,
            'variation' => 'at',
        ]);

        // Nombre de participants Vapp
        $nbUsersVapp = $abTestUserRepository->count([
            'abTest' => $vappFormulaire,
            'variation' => 'vapp',
        ]);

        // nombre d'utilisateur qui ont demandé la version normale
        $nbUsersNormal = $abTestUserRepository->count([
            'abTest' => $vappFormulaire,
            'refused' => true,
        ]);

        // Nombre de recherches par source
        $logAidSearchs = $logAidSearchRepository->countBySource([
            'dateCreateMin' => $dateStart,
            'sources' => $sources,
            'noPageInQuery' => true
        ]);

        $logAidSearchsBySource = [];
        foreach ($logAidSearchs as $logAidSearch) {
            $logAidSearchsBySource[$logAidSearch['source']] = $logAidSearch['nb'];
        }

        // on ajoute les logs temporaires pour avoir du live
        $logAidSearchTemps = $logAidSearchTempRepository->countBySource([
            'dateCreateMin' => $dateStart,
            'sources' => $sources,
            'noPageInQuery' => true
        ]);

        foreach ($logAidSearchTemps as $logAidSearchTemp) {
            if (isset($logAidSearchsBySource[$logAidSearchTemp['source']])) {
                $logAidSearchsBySource[$logAidSearchTemp['source']] += $logAidSearchTemp['nb'];
            } else {
                $logAidSearchsBySource[$logAidSearchTemp['source']] = $logAidSearchTemp['nb'];
            }
        }

        // Nombre d'affichage par source
        $logAidViews = $logAidViewRepository->countBySource([
            'dateMin' => $dateStart,
            'sources' => $sources,
        ]);
        $logAidViewsBySource = [];
        foreach ($logAidViews as $logAidView) {
            $logAidViewsBySource[$logAidView['source']] = $logAidView['nb'];
        }
        // on ajoute les logs temporaires pour avoir du live
        $logAidViewsTemps = $logAidViewTempRepository->countBySource([
            'dateMin' => $dateStart,
            'sources' => $sources,
        ]);
        foreach ($logAidViewsTemps as $logAidViewTemp) {
            if (isset($logAidViewsBySource[$logAidViewTemp['source']])) {
                $logAidViewsBySource[$logAidViewTemp['source']] += $logAidViewTemp['nb'];
            } else {
                $logAidViewsBySource[$logAidViewTemp['source']] = $logAidViewTemp['nb'];
            }
        }

        // Nombre plus infos par source
        $logAidOrigins = $logAidOriginUrlClickRepository->countBySource([
            'dateMin' => $dateStart,
            'sources' => $sources,
        ]);
        $logAidOriginsBySource = [];
        foreach ($logAidOrigins as $logAidOrigin) {
            $logAidOriginsBySource[$logAidOrigin['source']] = $logAidOrigin['nb'];
        }

        // Nombre candidater par sources
        $logAidApplications = $logAidApplicationUrlClickRepository->countBySource([
            'dateMin' => $dateStart,
            'sources' => $sources,
        ]);
        $logAidApplicationsBySource = [];
        foreach ($logAidApplications as $logAidApplication) {
            $logAidApplicationsBySource[$logAidApplication['source']] = $logAidApplication['nb'];
        }

        // On charge tous les votes
        $abTestVotes = $abTestVoteRepository->findBy([
            'abTest' => $vappFormulaire,
        ]);
        $upvotesAt = 0;
        $upvotesVapp = 0;
        $upvotesVappValid = 0;
        $downvotesAt = 0;
        $downvotesVapp = 0;
        $downvotesVappValid = 0;
        foreach ($abTestVotes as $abTestVote) {
            if ($abTestVote->getVariation() === 'at') {
                if ($abTestVote->getVote() === 1) {
                    $upvotesAt++;
                } else {
                    $downvotesAt++;
                }
            } else {
                $scoreVApp = $this->extractVappScore($abTestVote->getData());
                if ($abTestVote->getVote() === 1) {
                    $upvotesVapp++;
                    if ($scoreVApp >= 60) {
                        $upvotesVappValid++;
                    }
                } else {
                    $downvotesVapp++;
                    if ($scoreVApp < 60) {
                        $downvotesVappValid++;
                    }
                }
            }
        }

        // Création du tableur
        $filename = 'rapport-vapp.xlsx';
        $row = 1;
        $spreadsheet = new Spreadsheet();

        // selectionne la feuille courante
        $sheet = $spreadsheet->getActiveSheet();

        // met le nom à la feuille
        $sheet->setTitle('Vapp Rapport');

        // Création des entêtes
        $headers = [
            '',
            'Version AT',
            'Version Vapp',
        ];

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A' . $row);
        $row++;

        // Ajout des données
        $cells = [
            ['Nombre de participants', $nbUsersAt, $nbUsersVapp],
            ['Nombre de participants ayant refusé', $nbUsersNormal, ''],
            ['Nombre de recherches', $logAidSearchsBySource['at_test'] ?? 0, $logAidSearchsBySource['vapp'] ?? 0],
            ['Nombre d\'affichages', $logAidViewsBySource['at_test'] ?? 0, $logAidViewsBySource['vapp'] ?? 0],
            ['Nombre de plus d\'infos', $logAidOriginsBySource['at_test'] ?? 0, $logAidOriginsBySource['vapp'] ?? 0],
            ['Nombre de candidatures', $logAidApplicationsBySource['at_test'] ?? 0, $logAidApplicationsBySource['vapp'] ?? 0],
            ['Nombre de votes positifs', $upvotesAt, $upvotesVapp],
            ['Nombre de votes positifs corrigés', '', $upvotesVappValid],
            ['Nombre de votes négatifs', $downvotesAt, $downvotesVapp],
            ['Nombre de votes négatifs corrigés', '', $downvotesVappValid],
        ];
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($cells, null, 'A' . $row);
        $row++;
        
        $writer = new Xlsx($spreadsheet);
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        
        // StreamedResponse pour le téléchargement
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function extractVappScore(string $score): float{
        try {
            $data = json_decode($score, true);
            return isset($data['score_vapp']) ? (float) $data['score_vapp'] : 0.0;
        } catch (\JsonException $e) {
            return 0.0;
        }
    }
}
