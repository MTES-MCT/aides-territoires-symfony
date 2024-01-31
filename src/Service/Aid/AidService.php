<?php

namespace App\Service\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AidService
{
    public function __construct(
        protected HttpClientInterface $httpClientInterface,
        protected UserService $userService,
        protected RouterInterface $routerInterface,
        protected ReferenceService $referenceService,
        protected ManagerRegistry $managerRegistry
    )
    {
        
    }

    public function searchAids(array $aidParams): array
    {
        $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidParams);
        if (count($aids) <= 10) {
            $aidParams['scoreTotalMin'] = 20;
            $aidParams['scoreObjectsMin'] = 0;
            $aids = $this->managerRegistry->getRepository(Aid::class)->findCustom($aidParams);
        }
        $aids = $this->postPopulateAids($aids, $aidParams);

        return $aids;
    }

    public function extractInlineStyles(Aid $aid): Aid
    {
        $styles = [];
        $dom = new \DOMDocument();
        $dom->loadHTML($aid->getDescription());

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[@style]");
        foreach ($nodes as $node) {
            $itemId = $node->getAttribute('id') ?? '';
            if ($itemId == '') {
                $itemId = uniqid('style-');
                $node->setAttribute('id', $itemId);
            }
            $styles[$itemId] = $node->nodeValue;
        }

        // Sélectionner uniquement le contenu intérieur de la balise <body>
        $body = $xpath->query('//body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $childNode) {
            $newHtml .= $dom->saveHTML($childNode);
        }

        $aid->setDescription($newHtml);
        // $aid->setInlineStyles($styles);
        return $aid;
    }



    public function postPopulateAids(array $aids, ?array $params) : array
    {
        // on déduplique les génériques
        $aids = $this->unDuplicateGenerics($aids, $params['perimeterFrom'] ?? null);

        // pour les portails il y a des aides mises en avant et des aides à exclures
        $aids = $this->handleSearchPageRules($aids, $params);
        
        return $aids;
    }

    // pour les portails il y a des aides mises en avant et des aides à exclures
    public function handleSearchPageRules(array $aids, $params): array
    {
        if (isset($params['searchPage']) && $params['searchPage'] instanceof SearchPage) {
            // aides à exclures
            foreach ($aids as $key => $aid) {
                if ($params['searchPage']->getExcludedAids()->contains($aid)) {
                    unset($aids[$key]);
                }
            }

            // aides à mettre en avant
            $highlightedAids = [];
            $normalAids = [];
            foreach ($aids as $key => $aid) {
                if ($params['searchPage']->getHighlightedAids()->contains($aid)) {
                    $highlightedAids[] = $aid;
                } else {
                    $normalAids[] = $aid;
                }
                unset($aids[$key]);
            }

            $aids = array_merge($highlightedAids, $normalAids);
        }

        return $aids;
    }

    /*
        Nous ne devrions jamais avoir à la fois l'aide générique et sa version locale dans les résultats de recherche.
        Lequel devrait être supprimé des résultats dépend de plusieurs facteurs.
        Nous prenons en compte le périmètre d'échelle associé à l'aide locale.

        Lorsque la recherche porte sur une zone plus large que le périmètre de l'aide locale,
            nous affichons la version générique.
        Lorsque la recherche porte sur une zone plus petite que le périmètre de l'aide locale,
            nous affichons la version locale.
    */
    public function unDuplicateGenerics(array $aids, ?Perimeter $perimeter) : array
    {
        // Si on n'a pas de périmètre de recherche
        if (!$perimeter instanceof Perimeter) {
            $searchSmaller = false;
            $searchWider = true;
        }
        // converti le array en ArrayCollection
        $aids = new ArrayCollection($aids);

        // les aides que l'on va exclude
        $perimeterSearch = $perimeter instanceof Perimeter;
        $perimeterScale = ($perimeter instanceof Perimeter) ? $perimeter->getScale() : 0;
        // Parcours la liste des aides actuelles
        /** @var Aid $aid */
        foreach ($aids as $aid) {
            // Si on a un périmètre de recherche
            if ($perimeterSearch) {
                $searchSmaller = $perimeterScale <= $aid->getPerimeter()->getScale();
                $searchWider = $perimeterScale > $aid->getPerimeter()->getScale();
            }

            if ($searchSmaller) {
                // si c'est une aide generic avec des declinaisons, on la retire si un des aides locales est dans la liste
                if ($aid->getAidsFromGeneric()) {
                    $localInList = false;
                    foreach ($aid->getAidsFromGeneric() as $aidFromGeneric) {
                        if ($aids->contains($aidFromGeneric)) {
                            $localInList = true;
                        }
                    }
                    if ($localInList) {
                        $aids->remove($aid);
                    }
                }
            } else if ($searchWider) {
                // Si c'est une aide locale et que la liste contiens l'aide générique, on la retire de la listes
                if ($aid->getGenericAid() && $aids->contains($aid->getGenericAid())) {
                    $aids->remove($aid);
                }
            }
        }

        return $aids->toArray();
    }

    public function getUrl(Aid $aid, $interface = UrlGeneratorInterface::ABSOLUTE_URL) : ?string {
        try {
            return $this->routerInterface->generate('app_aid_aid_details', ['slug' => $aid->getSlug()], $interface);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function userCanExportPdf(Aid $aid, ?User $user) : bool {
        if (!$user) {
            return false;
        }
        if ($user->getId() == $aid->getAuthor()->getId() || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }
        return false;
    }

    public function userCanSee(Aid $aid, ?User $user) : bool {
        if (!$aid->isPublished()) {
            if ($user && $aid->getAuthor() && ($user->getId() == $aid->getAuthor()->getId())) {
                return true;
            } else if ($user && $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function userCanEdit(Aid $aid, ?User $user) : bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // si c'est l'auteur ou un admin
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        return false;
    }

    public function userCanDuplicate(Aid $aid, ?User $user) : bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // si c'est l'auteur ou un admin
        if ($aid->getAuthor() == $user || $this->userService->isUserGranted($user, User::ROLE_ADMIN)) {
            return true;
        }

        return false;
    }

    /**
     * Recupère les données chez Démarche Simplifiée (DS)
     *
     * @param integer $dsId
     * @param array $dsMapping
     * @param User|null $user
     * @param Organization|null $organization
     * @return array
     */
    public function getDatasFromDs(Aid $aid, ?User $user, ?Organization $organization): array
    {
        $datas = [
            'prepopulate_application_url' => false,
            'ds_folder_id' => false,
            'ds_folder_number' => false,
            'ds_application_url' => false
        ];
        // l'aide n'as pas de mapping DS
        if (!$aid->getDsMapping()) {
            return $datas;
        }
        
        // utilisateur non connecté
        if (!$user) {
            $datas['ds_application_url'] = true;
            return $datas;
        }

        $organizationType = ($user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) ? $user->getDefaultOrganization()->getOrganizationType() : null;
        if (in_array($organizationType->getSlug(), [OrganizationType::SLUG_COMMUNE, OrganizationType::SLUG_EPCI])) {
            try {
                $response = $this->postPrepopulateData($aid->getDsId(), $aid->getDsMapping(), $user, $organization);
                $content = json_decode($response->getContent());

                $datas['prepopulate_application_url'] = $content->dossier_url ?? null;
                $datas['ds_folder_id'] = $content->dossier_id ?? null;
                $datas['ds_folder_number'] = $content->dossier_number ?? null;

            } catch (\Exception $e) {
                
            }
        }
        
        return $datas;
    }

    /**
     * Aoppel l'API Démarche Simplifiée (DS)
     *
     * @param integer $dsId
     * @param array $dsMapping
     * @param UserInterface|null $user
     * @param Organization|null $organization
     * @return void
     */
    public function postPrepopulateData(int $dsId, array $dsMapping, ?UserInterface $user, ?Organization $organization): mixed
    {
        $datas = $this->prepopulateDsFolder($dsMapping, $user, $organization);

        $response = $this->httpClientInterface->request(
            'POST',
            'https://www.demarches-simplifiees.fr/api/public/v1/demarches/'.$dsId.'/dossiers',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $datas
            ]
        );

        return $response;
    }

    /**
     * Fait le tableau de données à envoyer à Démarche Simplifiée (DS)
     *
     * @param array $dsMapping
     * @param UserInterface|null $user
     * @param Organization|null $organization
     * @return array
     */
    public function prepopulateDsFolder(array $dsMapping, ?UserInterface $user, ?Organization $organization): array
    {
        $datas = [];

        try {
            foreach ($dsMapping['FieldsList'] as $field) {
                if (isset($field['response_value']) && !empty($field['response_value'])) {
                    $datas[$field['ds_field_id']] = $field['response_value'];
                } else if (
                    isset($field['at_model']) && !empty($field['at_model'])
                    && isset($field['at_model_attr']) && !empty($field['at_model_attr'])
                ) {
                    switch ($field['at_model']) {
                        case 'User':
                            $value = $this->getFieldValue($field['at_model_attr'], $user);
                            break;


                        case 'Organization':
                            $value = $this->getFieldValue($field['at_model_attr'], $organization);
                            break;
                    }
                    if ($value) {
                        $datas[$field['ds_field_id']] = $value;
                    }
                }
            }

            return $datas;
        } catch (\Exception $e) {
            return $datas;
        }
    }

    /**
     * Recupère la donnée en fonction de l'entité et du champ
     * basé sur les nom de champ Django
     *
     * @param string $oldField
     * @param mixed $entity
     * @return string|null
     */
    private function getFieldValue(string $oldField, mixed $entity): ?string
    {
        if (!$entity) {
            return null;
        }

        if ($entity instanceof User) {
            switch ($oldField) {
                case 'last_name':
                    return $entity->getLastname();
                break;

                case 'first_name':
                    return $entity->getFirstname();
                break;

                case 'email':
                    return $entity->getEmail();
                break;
            }
        } else if ($entity instanceof Organization) {
            switch ($oldField) {
                case 'organizationType':
                    return $entity->getOrganizationType() ? $entity->getOrganizationType()->getName() : null;
                break;
            }
        }

        return null;
    }
}