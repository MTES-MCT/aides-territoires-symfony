<?php

namespace App\EventListener\Aid;

use App\Entity\Aid\Aid;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AidListener
{
    public function __construct(
        protected AidService $aidService,
        protected EmailService $emailService,
        protected ParamService $paramService,
        protected RouterInterface $routerInterface
    ) {
        
    }

    public function onPostLoad(PostLoadEventArgs $args) : void {
        if ($args->getObject() instanceof Aid) {
            $args->getObject()->setUrl($this->aidService->getUrl($args->getObject()));
        }
    }

    public function onPostUpdate(PostUpdateEventArgs $args): void {
        /** @var Aid $aid */
        $aid = $args->getObject();
        // les champs qui ont été modifiés
        /** @var EntityManager $manager */
        $manager = $args->getObjectManager();
        $changeSet = $manager->getUnitOfWork()->getEntityChangeSet($aid);
        foreach ($changeSet as $field => $change) {
            // Publication d'une aide
            if ($field == 'status' && isset($change[1]) && $change[1] == Aid::STATUS_PUBLISHED) {
                // si première publication
                if (!$aid->getTimePublished()) {
                    $aid->setTimePublished(new \DateTime(date('Y-m-d H:i:s')));
                }
                if (!$aid->getDatePublished()) {
                    $aid->setDatePublished(new \DateTime(date('Y-m-d')));
                }

                // si auteur à demandé à être notifié en cas de publication
                if ($aid->isAuthorNotification()) {
                    $this->emailService->sendEmailViaApi(
                        $aid->getAuthor()->getEmail(),
                        $aid->getAuthor()->getFirstname().' '.$aid->getAuthor()->getLastname(),
                        $this->paramService->get('sib_publication_email_template_id'),
                        [
                            'PRENOM' => $aid->getAuthor()->getFirstname(),
                            'NOM' => $aid->getAuthor()->getLastname(),
                            'AIDE_NOM' => $aid->getName(),
                            'AIDE_URL' => $this->aidService->getUrl($aid, UrlGeneratorInterface::ABSOLUTE_URL),
                            'BASE_URL' => $this->routerInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
                        ]
                    );
                }
            }
        }
    }
}
