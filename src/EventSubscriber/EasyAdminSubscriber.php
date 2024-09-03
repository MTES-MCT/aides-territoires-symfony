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
    ) {
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
    }


    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event)
    {
        // l'entite
        $entity = $event->getEntityInstance();

        if ($entity instanceof Backer && $entity->getDeleteLogo()) {
            $this->imageService->deleteImageFromCloud($entity->getLogo());
            $entity->setLogo(null);
        }

        if ($entity instanceof BlogPost && $entity->getDeleteLogo()) {
            $this->imageService->deleteImageFromCloud($entity->getLogo());
            $entity->setLogo(null);
        }

        if ($entity instanceof BlogPromotionPost && $entity->getDeleteImage()) {
            $this->imageService->deleteImageFromCloud($entity->getImage());
            $entity->setImage(null);
        }

        if ($entity instanceof Program && $entity->getDeleteLogo()) {
            $this->imageService->deleteImageFromCloud($entity->getLogo());
            $entity->setLogo(null);
        }

        if ($entity instanceof Project && $entity->getDeleteImage()) {
            $this->imageService->deleteImageFromCloud($entity->getImage());
            $entity->setImage(null);
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
    }
}
