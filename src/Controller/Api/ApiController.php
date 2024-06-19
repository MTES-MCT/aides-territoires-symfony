<?php

namespace App\Controller\Api;

use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{
    const SERIALIZE_FORMAT = 'json';

    public function __construct(
        protected RequestStack $requestStack,
        protected SerializerInterface $serializerInterface,
        protected RouterInterface $routerInterface,
        protected ManagerRegistry $managerRegistry,
        protected StringService $stringService
    )
    {
    }

    protected function getPage(): int
    {
        return $this->requestStack->getCurrentRequest()->get('page', 1);
    }

    protected function getItemsPerPage(): int
    {
        // old way
        $size = $this->requestStack->getCurrentRequest()->get('size', null);
        if ($size) {
            return (int) $size;
        }

        // new way
        $itemsPerPage = $this->requestStack->getCurrentRequest()->get('itemsPerPage', 50);
        return (int) $itemsPerPage;
    }

    protected function getNbPages($nbItems = 0): int
    {
        if ($nbItems == 0) {
            return 1;
        }
        return ceil($nbItems / $this->getItemsPerPage());
    }

    protected function getPrevious(): ?string
    {
        return $this->getPage() <= 1
            ? null
            : preg_replace_callback('/page='.$this->getPage().'/', function($matches) {
                return 'page='.($this->getPage() - 1);
            }, substr($this->routerInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL), 0, -1).$this->requestStack->getCurrentRequest()->getRequestUri());
    }

    protected function getNext($nbItems = 0): ?string
    {
        // on est sur la dernière page
        if ($this->getPage() >= $this->getNbPages($nbItems)) {
            return null;
        }

        $urlBase = $this->routerInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $pathInfo = $this->requestStack->getCurrentRequest()->getPathInfo();
        $filters = $this->requestStack->getCurrentRequest()->query->all();
        $filters['page'] = isset($filters['page']) ? $filters['page'] + 1 : 2;
        $query = http_build_query($filters);
        return substr($urlBase, 0, -1).$pathInfo.'?'.$query;
    }

    protected function stringToBool(?string $str): bool
    {
        return $str === 'true' ? true : false;
    }
}