<?php

namespace App\EventSubscriber;

use App\Entity\Backer\Backer;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Entity\Program\Program;
use App\Entity\Project\Project;
use App\Entity\Search\SearchPage;
use App\Service\Image\ImageService;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\StringService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected StringService $stringService,
        protected PerimeterService $perimeterService,
        protected EntityManagerInterface $entityManagerInterface,
        protected ImageService $imageService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['beforePerimeterImporterCreate'],
            BeforeEntityUpdatedEvent::class => ['onBeforeEntityUpdated'],
        ];
    }

    public function beforePerimeterImporterCreate(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof PerimeterImport)) {
            return;
        }

        if (!$entity->getAdhocPerimeterName()) {
            $entity->setAdhocPerimeterName($this->perimeterService->getAdhocNameFromInseeCodes($entity->getCityCodes()));
        }

        if (!$entity->getAdhocPerimeter() && $entity->getAdhocPerimeterName()) {
            $perimeter = new Perimeter();
            $perimeter->setName($entity->getAdhocPerimeterName());
            $perimeter->setUnaccentedName($this->stringService->getNoAccent($entity->getAdhocPerimeterName()));
            $perimeter->setScale(Perimeter::SCALE_ADHOC);
            $perimeter->setContinent(Perimeter::SLUG_CONTINENT_DEFAULT);
            $perimeter->setCountry(Perimeter::SLUG_COUNTRY_DEFAULT);
            $perimeter->setCode(uniqid());
            $entity->setAdhocPerimeter($perimeter);
        }

        // $slug = $this->slugger->slugify($entity->getTitle());
        // $entity->setSlug($slug);
    }


    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event)
    {
        // l'entite
        $entity = $event->getEntityInstance();

        if ($entity instanceof Backer) {
            if ($entity->getDeleteLogo()) {
                $this->imageService->deleteImageFromCloud($entity->getLogo());
                $entity->setLogo(null);
            }
        }

        if ($entity instanceof BlogPost) {
            if ($entity->getDeleteLogo()) {
                $this->imageService->deleteImageFromCloud($entity->getLogo());
                $entity->setLogo(null);
            }
        }

        if ($entity instanceof BlogPromotionPost) {
            if ($entity->getDeleteImage()) {
                $this->imageService->deleteImageFromCloud($entity->getImage());
                $entity->setImage(null);
            }
            // $this->handleBlogPromotionPostBeforeUpdate($event);
        }

        if ($entity instanceof Program) {
            if ($entity->getDeleteLogo()) {
                $this->imageService->deleteImageFromCloud($entity->getLogo());
                $entity->setLogo(null);
            }
        }

        if ($entity instanceof Project) {
            if ($entity->getDeleteImage()) {
                $this->imageService->deleteImageFromCloud($entity->getImage());
                $entity->setImage(null);
            }
        }

        if ($entity instanceof SearchPage) {
            if ($entity->getDeleteLogo()) {
                $this->imageService->deleteImageFromCloud($entity->getLogo());
                $entity->setLogo(null);
            }
            if ($entity->getDeleteMetaImage()) {
                $this->imageService->deleteImageFromCloud($entity->getMetaImage());
                $entity->setMetaImage(null);
            }
        }

        // return;
    }

    private function handleBlogPromotionPostBeforeUpdate(BeforeEntityUpdatedEvent $event)
    {
        // l'entite
        $entity = $event->getEntityInstance();

        // les champs modifiés
        $uow = $this->entityManagerInterface->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($entity);

        $todo = false;
        if (isset($changeset['image']) && $todo) {
            if ($changeset['image'][0] == null) { // Si l'image était vide

            } else { // Si l'image n'était pas vide
                // Si la nouvelle image est vide
                if ($changeset['image'][1] == null) {
                    if (
                        isset($_POST['BlogPromotionPost'])
                        && isset($_POST['BlogPromotionPost']['image']) 
                        && isset($_POST['BlogPromotionPost']['image']['delete'])
                        && $_POST['BlogPromotionPost']['image']['delete'] == 1
                    ) { // on veu supprimer l'image
                    } else { // on veu garder l'ancienne image
                        $entity->setImage($changeset['image'][0]);
                    }
                } else {

                }
            }
            

        } else {

        }
        // return;
    }
}