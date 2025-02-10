<?php

namespace App\Controller\Site;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Controller\FrontController;
use App\Entity\Site\AbTestVote;
use App\Exception\BusinessException\Site\AbTestVoteException;
use App\Repository\Aid\AidRepository;
use App\Repository\Site\AbTestRepository;
use App\Repository\Site\AbTestVoteRepository;
use App\Security\Voter\InternalRequestVoter;
use App\Service\Site\AbTestService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class AbTestController extends FrontController
{
    #[Route('abtest/ajax-vote', name: 'app_abtest_ajax_vote', options: ['expose' => true])]
    public function ajaxVote(
        RequestStack $requestStack,
        AbTestRepository $abTestRepository,
        AbTestVoteRepository $abTestVoteRepository,
        AidRepository $aidRepository,
        ManagerRegistry $managerRegistry
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

            // recupération des données
            $vote = $requestStack->getCurrentRequest()->get('vote', null);
            $phpSessionId = $requestStack->getCurrentRequest()->cookies->get('PHPSESSID', null);
            $aidId = $requestStack->getCurrentRequest()->get('aidId', null);

            // si il manque un paramètre on ne prends pas en compte
            if ($vote === null || !$phpSessionId || !$aidId) {
                throw new AbTestVoteException('Missing parameters');
            }

            // chargement du test
            $abTest = $abTestRepository->findOneBy(['name' => AbTestService::SEARCH_FORM_TEST]);

            if (!$abTest) {
                throw new AbTestVoteException('Test not found');
            }

            // chargement aide
            $aid = $aidRepository->find($aidId);
            if (!$aid) {
                throw new AbTestVoteException('Aid not found');
            }

            // vérification si vote déjà effectué sur cette aide
            $abTestVote = $abTestVoteRepository->findOneBy([
                'abTest' => $abTest,
                'aid' => $aid,
                'phpSessionId' => $phpSessionId,
            ]);
            // nouveau vote
            if (!$abTestVote instanceof AbTestVote) {
                $abTestVote = new AbTestVote();
                $abTestVote->setAbTest($abTest);
                $abTestVote->setAid($aid);
                $abTestVote->setPhpSessionId($phpSessionId);
            }

            // met le vote
            $abTestVote->setVote((int) $vote);

            // sauvegarde
            $managerRegistry->getManager()->persist($abTestVote);
            $managerRegistry->getManager()->flush();

            // retour ok
            return new JsonResponse([
                'success' => true,
                'message' => 'Vote successfully saved',
            ]);
        } catch (AccessDeniedException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (AbTestVoteException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue'
            ]);
        }
    }
}
