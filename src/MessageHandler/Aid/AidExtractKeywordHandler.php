<?php

namespace App\MessageHandler\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\KeywordReferenceSuggested;
use App\Message\Aid\AidExtractKeyword;
use App\Repository\Aid\AidRepository;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\Notification\NotificationService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class AidExtractKeywordHandler
{
    public function __construct(
        private NotificationService $notificationService,
        private ManagerRegistry $managerRegistry,
        private ParamService $paramService,
        private AidService $aidService
    ) {}

    public function __invoke(AidExtractKeyword $message): void
    {
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $aid = $aidRepository->find($message->getIdAid());
        if ($aid instanceof Aid) {
            $keywordsFreq = $this->aidService->extractKeywords($aid);
            foreach ($keywordsFreq as $keywordFreq) {
                if (!$keywordFreq['keyword'] instanceof KeywordReference) {
                    continue;
                }
                $keyword = $keywordFreq['keyword'];
                $hasASynonym = false;
                if ($aid->getKeywordReferences()->contains($keyword)) {
                    $hasASynonym = true;
                }
                foreach ($keyword->getKeywordReferences() as $keywordReference) {
                    if ($aid->getKeywordReferences()->contains($keywordReference)) {
                        $hasASynonym = true;
                    }
                }
                if (!$hasASynonym) {
                    $keywordReferenceSuggested = new KeywordReferenceSuggested();
                    $keywordReferenceSuggested->setKeywordReference($keyword);
                    $keywordReferenceSuggested->setAid($aid);
                    $keywordReferenceSuggested->setOccurence($keywordFreq['freq']);
                    $this->managerRegistry->getManager()->persist($keywordReferenceSuggested);
                    $this->managerRegistry->getManager()->flush();
                }
            }
        }
    }
}
