<?php

namespace App\MessageHandler\SearchPage;

use App\Entity\Log\LogEvent;
use App\Entity\Search\SearchPage;
use App\Message\SearchPage\SearchPageCountAid;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

#[AsMessageHandler()]
class SearchPageCountAidHandler
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AidSearchFormService $aidSearchFormService,
        private AidService $aidService
    ) {
    }

    public function __invoke(SearchPageCountAid $message): void
    {
        /** @var SearchPageRepository $searchPageRepo */
        $searchPageRepo = $this->managerRegistry->getRepository(SearchPage::class);

        // charge le portail
        $searchPage = $searchPageRepo->find($message->getIdSearchPage());

        // met à jour le nombre de porteurs
        if ($searchPage instanceof SearchPage) {
            $aidParams = [
                'showInSearch' => true,
                'searchPage' => $searchPage
            ];
            $aidSearchClass = $this->aidSearchFormService->getAidSearchClass(
                params: [
                    'querystring' => $searchPage->getSearchQuerystring() ?? null,
                    'forceOrganizationType' => null,
                    'dontUseUserPerimeter' => true
                ]
            );

            $aidParams = array_merge(
                $aidParams,
                $this->aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass)
            );

            $aids = $this->aidService->searchAids($aidParams);

            $logEvent = new LogEvent();
            $logEvent->setCategory('aid');
            $logEvent->setEvent('live_count');
            $logEvent->setSource($searchPage->getSlug());
            $logEvent->setValue(count($aids));

            $this->managerRegistry->getManager()->persist($logEvent);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
