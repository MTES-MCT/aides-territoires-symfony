<?php

namespace App\EventListener\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Site\UrlRedirect;
use App\Message\Aid\AidPropagateUpdate;
use App\Service\Aid\AidService;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AidListener
{
    public function __construct(
        private AidService $aidService,
        private EmailService $emailService,
        private ParamService $paramService,
        private RouterInterface $routerInterface,
        private MessageBusInterface $messageBusInterface
    ) {
        
    }

    public function onPostLoad(PostLoadEventArgs $args) : void
    {
        if ($args->getObject() instanceof Aid) {
            $args->getObject()->setUrl($this->aidService->getUrl($args->getObject()));
        }
    }

    public function onPostUpdate(PostUpdateEventArgs $args): void
    {
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

            // modification du slug
            if ($field == 'slug') {
                $this->handleSlugUpdate($args);
            }
        }

        // si c'est une aide générique avec des champs sanctuarisés, on va mettre à jour ses déclinaisons
        if ($aid->isIsGeneric() && !$aid->getSanctuarizedFields()->isEmpty()) {
            $this->propagateUpdate($aid);
        }
    }

    private function propagateUpdate(Aid $aid): void
    {
        foreach ($aid->getAidsFromGeneric() as $aidFromGeneric) {
            $this->messageBusInterface->dispatch(new AidPropagateUpdate($aid->getId(), $aidFromGeneric->getId()));
        }
    }

    private function handleSlugUpdate(PostUpdateEventArgs $args)
    {
        /** @var Aid $aid */
        $aid = $args->getObject();

        // regarde si l'aide à déjà été publiée
        if ($aid->getTimePublished()) {
            // Si oui on créer une redirection de l'ancienne url vers la nouvelles

            /** @var EntityManager $manager */
            $manager = $args->getObjectManager();
            $changeSet = $manager->getUnitOfWork()->getEntityChangeSet($aid);

            $oldSlug = $changeSet['slug'][0];
            $newSlug = $changeSet['slug'][1];
            $urlRedirect = new UrlRedirect();
            $aid->setSlug($oldSlug);
            $urlRedirect->setOldUrl('/'.$this->aidService->getUrl($aid, UrlGeneratorInterface::RELATIVE_PATH));
            $aid->setSlug($newSlug);
            $urlRedirect->setNewUrl('/'.$this->aidService->getUrl($aid, UrlGeneratorInterface::RELATIVE_PATH));
            $manager->persist($urlRedirect);
            $manager->flush();
        }
    }
}
