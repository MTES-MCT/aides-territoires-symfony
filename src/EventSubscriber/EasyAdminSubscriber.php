<?php

namespace App\EventSubscriber;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected StringService $stringService,
        protected PerimeterService $perimeterService
    )
    {
        ini_set('memory_limit', '5G');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['beforePerimeterImporterCreate'],
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
}