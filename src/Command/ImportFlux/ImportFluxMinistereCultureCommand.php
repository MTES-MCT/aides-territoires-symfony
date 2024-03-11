<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;
use App\Entity\Reference\KeywordReference;

#[AsCommand(name: 'at:import_flux:ministere_culture', description: 'Import de flux du ministère de la culture')]
class ImportFluxMinistereCultureCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux du ministère de la culture';
    protected string $commandTextEnd = '>Import de flux du ministère de la culture';

    protected ?string $importUniqueidPrefix = 'MdC_';
    protected ?int $idDataSource = 8;
    protected bool $paginationEnabled = true;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['id'])) {
            return null;
        }
        $importUniqueid = $this->importUniqueidPrefix . $aidToImport['id'];
        return $importUniqueid;
    }

    protected function getFieldsMapping(array $aidToImport, array $params = null): array
    {
        try {
            $importRaws = $this->getImportRaws($aidToImport, ['deadline']);
            $importRawObjectCalendar = $importRaws['importRawObjectCalendar'];
            $importRawObject = $importRaws['importRawObject'];

    
            $description1 = isset($aidToImport['summary']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['summary']) : '';
            $description2 = isset($aidToImport['body']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['body']) : '';
            $type = isset($aidToImport['type']) ? $aidToImport['type'] : '';
            $aidToDelete = '';
            if (in_array($type, ['Demande d\'autorisation', 'Demande de labellisation'])) {
                $aidToDelete = 'Aide à supprimer : type d\'aides non correspondant ';
            }
            $description = html_entity_decode($aidToDelete . $description1 . $description2);
            if (trim($description) == '') {
                $description = null;
            }
    
            $eligility = '';
            if (isset($aidToImport['amount'])) {
                $eligility .= $aidToImport['amount'] . ' ';
            }
            if (isset($aidToImport['public'])) {
                $eligility .= '<br/> bénéficiaires de l\'aide:'. (string) $aidToImport['public'];
            }
            if (isset($aidToImport['eztag_region'])) {
                $eztagRegion = $aidToImport['eztag_region'];
                if (is_array($eztagRegion )) {
                    $eztagRegion  = implode(', ', $eztagRegion);
                }
                $eligility .= '<br/> périmètre de l\'aide:'. (string) $eztagRegion;
            }
            if (isset($aidToImport['deadline'])) {
                $eligility .= '<br/> date de clôture de l\'aide :'. (string) $aidToImport['deadline'];
            }
            $eligility = html_entity_decode($eligility);
            if (trim($eligility) == '') {
                $eligility = null;
            } else {
                $eligility = $this->htmlSanitizerInterface->sanitize($eligility);
            }
    
            $dateSubmissionDeadline = null;
            try {
                if (isset($aidToImport['deadline'])) {
                    $dateSubmissionDeadline = new \DateTime($aidToImport['deadline']);
                }
            } catch (\Exception $e) {
                $dateSubmissionDeadline = null;
            }
    
            $isCallForProject = (isset($aidToImport['deadline']) && $aidToImport['deadline']) ? true : false;
    
            $return = [
                'importDataMention' => 'Ces données sont mises à disposition par le Ministère de la Culture',
                'importRawObjectCalendar' => $importRawObjectCalendar,
                'importRawObject' => $importRawObject,
                'name' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
                'nameInitial' => isset($aidToImport['title']) ? strip_tags($aidToImport['title']) : null,
                'description' => $description,
                'originUrl' => isset($aidToImport['url']) ? $aidToImport['url'] : null,
                'contact' => isset($aidToImport['contact']) ? $this->htmlSanitizerInterface->sanitize($aidToImport['contact']) : null,
                'eligibility' => $eligility,
                'isCallForProject' => $isCallForProject,
            ];
            if (isset($params['context']) && $params['context'] == 'create') {
                $return['dateSubmissionDeadline'] = $dateSubmissionDeadline;
            } else if (isset($params['context']) && $params['context'] == 'update') {
                // en cas d'update on ne met à jour la date de clôture que si elle n'est pas déjà renseignée
                if ($dateSubmissionDeadline && isset($params['aid']) && !$params['aid']->getDateSubmissionDeadline()) {
                    $return['dateSubmissionDeadline'] = $dateSubmissionDeadline;
                }
            }
    
            return $return;
        } catch (\Exception $e) {
            dd($e, $aidToImport);
        }

    }

    protected function setAidSteps(array $aidToImport, Aid $aid): Aid
    {
        $aidSteps = $this->managerRegistry->getRepository(AidStep::class)->findCustom([
            'slugs' => [
                AidStep::SLUG_PREOP,
                AidStep::SLUG_OP,
                AidStep::SLUG_POSTOP,
            ]
        ]);
        foreach ($aidSteps as $aidStep) {
            $aid->addAidStep($aidStep);
        }
        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid
    {
        $audiences = [];
        if (isset($aidToImport['eztag_cible']) && is_array($aidToImport['eztag_cible'])) {
            $audiences = $aidToImport['eztag_cible'];
        }
        if (isset($aidToImport['public']) && is_array($aidToImport['public'])) {
            $audiences = $aidToImport['public'];
        }
        if (count($audiences) == 0) {
            return $aid;
        }

        // mapping des audiences X => [OrganizationType.slug]
        $mapping = [
            'Etudiants ou en recherche d\emploi' => [
                OrganizationType::SLUG_PRIVATE_PERSON
            ],
            'Professionnels de la culture' => [
                OrganizationType::SLUG_PRIVATE_SECTOR,
                'association'
            ],
            'Établissements publics/Services de l\'État' => [
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Établissements publics / Services de l’État' => [
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Établissements publics / services de l’État' => [
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Établissements publics / services de l\'État' => [
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Établissements publics/services de l’État (bibliothèques...) ' => [
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Entreprises publiques locales' => [
                OrganizationType::SLUG_PUBLIC_CIES,
            ],
            'Opérateurs de travaux' => [
                'department',
                'region',
                'commune',
                'epci',
                OrganizationType::SLUG_PUBLIC_ORG,
                'special',
            ],
            'Aménageurs' => [
                OrganizationType::SLUG_PRIVATE_SECTOR
            ],
            'Collectivités' => [
                'department',
                'region',
                'commune',
                'epci',
            ],
            'Associations' => [
                'association'
            ],
            'Laboratoires de recherche' => [
                'researcher'
            ],
            'Entreprises privées' => [
                OrganizationType::SLUG_PRIVATE_SECTOR
            ],
            'Particuliers' => [
                OrganizationType::SLUG_PRIVATE_PERSON
            ],
            'Régions' => [
                'region'
            ],
            'Région' => [
                'region'
            ],
            'Départements' => [
                'department'
            ],
            'Departements' => [
                'department'
            ],
            'Communes' => [
                'commune'
            ],
            'EPCI à fiscalité propre' => [
                'epci'
            ],
            'Syndicats mixtes' => [
                'epci'
            ],
            'Associations' => [
                'association'
            ],
            'Organismes de recherche' => [
                'researcher'
            ],
        ];

        foreach ($audiences as $targetedAudienceName) {
            if (isset($mapping[$targetedAudienceName])) {
                foreach ($mapping[$targetedAudienceName] as $slugOrganizationType) {
                    $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)->findOneBy([
                        'slug' => $slugOrganizationType
                    ]);
                    if ($organizationType instanceof OrganizationType) {
                        $aid->addAidAudience($organizationType);
                    }
                }
            }
        }
        return $aid;
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['eztag_theme']) || !is_array($aidToImport['eztag_theme'])) {
            return $aid;
        }
        $mapping = $this->getMappingCategories();
        
        foreach ($aidToImport['eztag_theme'] as $thematique) {
            if (isset($mapping[$thematique]) && is_array($mapping[$thematique])) {
                foreach ($mapping[$thematique] as $category) {
                    $category = $this->managerRegistry->getRepository(Category::class)->findOneBy([
                        'slug' => $category
                    ]);
                    if ($category instanceof Category) {
                        $aid->addCategory($category);
                    }
                }
            }
        }

        return $aid;
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['eztag_theme']) || !is_array($aidToImport['eztag_theme'])) {
            return $aid;
        }

        $keywords = $this->managerRegistry->getRepository(KeywordReference::class)->findAll();
        $keywordsByName = [];
        foreach ($keywords as $keyword) {
            $keywordsByName[$keyword->getName()] = $keyword;
        }

        foreach ($aidToImport['eztag_theme'] as $thematique) {
            if (!isset($keywordsByName[$thematique])) {
                $keyword = new KeywordReference();
                $keyword->setName($thematique);
                $this->managerRegistry->getManager()->persist($keyword);
                $keywordsByName[$thematique] = $keyword;
            }
            if ($keywordsByName[$thematique] instanceof KeywordReference) {
                $aid->addKeywordReference($keywordsByName[$thematique]);
            }
        }

        return $aid;
    }

    private function getMappingCategories(): array
    {
        return [
            'Démocratisation' => [

            ],
            'Développement culturel' => [

            ],
            'Développement durable' => [

            ],
            'Education aux médias et à l\'information (EMI)' => [
                'medias',
                'education'
            ],
            'Emploi, travail, formation, professions culturelles' => [
                'emploi',
                'formation'
            ],
            'Enseignement supérieur' => [
                'formation'
            ],
            'Handicap, accessibilité' => [
                'handicap'
            ],
            'Pratiques culturelles' => [

            ],
            'Éducation artistique et culturelle' => [
                'education'
            ],
            'Égalité entre femmes et hommes' => [
                'egalite-des-chances'
            ],
            'Architecture' => [
                'patrimoine',
                'architecture'
            ],
            'Cinéma' => [
                'medias'
            ],
            'Arts plastiques' => [
                'arts'
            ],
            'Design' => [
                'arts'
            ],
            'Mode' => [
                'arts'
            ],
            'Métiers d\'art' => [
                'arts'
            ],
            'Photographie' => [
                'arts'
            ],
            'Droit de la culture et de la communication' => [
                'medias'
            ],
            'Europe et international' => [
            ],
            'Histoire des politiques culturelles' => [
            ],
            'Bande dessinée' => [
                'livres'
            ],
            'Industries musicales' => [
                'spectaclevivant'
            ],
            'Jeu vidéo' => [
                'medias',
                'sport'
            ],
            'Livre et lecture' => [
                'livres'
            ],
            'Propriété littéraire et artistique' => [
                'livres'
            ],
            'Édition' => [
                'livres'
            ],
            'Innovation numérique' => [
                'medias',
                'numerique'
            ],
            'Francophonie' => [
                'culture'
            ],
            'Langue française' => [
                'culture'
            ],
            'Langues régionales' => [
                'culture'
            ],
            'Multilinguisme' => [
                'culture'
            ],
            'Mécénat' => [
            ],
            'Audiovisuel' => [
                'medias'
            ],
            'Presse écrite' => [
                'medias'
            ],
            'Publicité' => [
                'medias'
            ],
            'Parcs et jardins' => [
                'patrimoine',
                'espaces-verts'
            ],
            'Archives' => [
                'culture'
            ],
            'Archéologie' => [
                'patrimoine'
            ],
            'Circulation des biens culturels' => [
                'patrimoine'
            ],
            'Connaissance des patrimoines' => [
                'patrimoine'
            ],
            'Conservation-restauration' => [
                'patrimoine'
            ],
            'Ethnologie' => [
                'patrimoine'
            ],
            'Monuments historiques et sites patrimoniaux' => [
                'patrimoine'
            ],
            'Musées, lieux d\'exposition' => [
                'patrimoine',
                'musee'
            ],
            'Patrimoine culturel immatériel' => [
                'patrimoine'
            ],
            'Protection du patrimoine' => [
                'patrimoine'
            ],
            'Sécurité, sûreté' => [
                'patrimoine'
            ],
            '1% artistique' => [
            ],
            'Pass Culture' => [
            ],
            'Villes et pays d\'art et d\'histoire' => [
            ],
            'Pratiques, consommations et usages culturels' => [
            ],
            'Sorties, expositions' => [
                'musee'
            ],
            'Arts de la marionnette' => [
                'spectaclevivant'
            ],
            'Arts de la rue' => [
                'spectaclevivant'
            ],
            'Arts du cirque' => [
                'spectaclevivant'
            ],
            'Danse' => [
                'spectaclevivant'
            ],
            'Musique' => [
                'spectaclevivant'
            ],
            'Théâtre' => [
                'spectaclevivant'
            ],
            'Études et statistiques' => [
            ],
            'Spectacle vivant' => [
                'spectaclevivant'
            ],
            'Médias' => [
                'medias'
            ],
            'Patrimoines' => [
                'patrimoine'
            ],
            'Accès à la culture' => [
            ],
            'Politiques culturelles' => [
            ],
            'Arts visuels' => [
                'spectaclevivant'
            ],
            'Égalité et diversité' => [
                'egalite-des-chances'
            ],
            'Evénements nationaux' => [
            ],
            'Eté culturel' => [
            ],
            'Festival de l\'Histoire de l\'Art' => [
                'arts',
                'musee'
            ],
            'Fête de la musique' => [
                'culture'
            ],
            'Journées Européennes de l\'Archéologie' => [
                'patrimoine'
            ],
            'Journées Européennes du Patrimoine' => [
                'patrimoine'
            ],
            'Nuit européenne des musées' => [
                'musee'
            ],
            'Rendez-vous aux jardins' => [
                'patrimoine'
            ],
            'langues de France' => [
                'culture'
            ],
            'Sciences du patrimoine' => [
                'patrimoine'
            ],
            'Création artistique' => [
            ],
            'Industries culturelles et créatives' => [
            ],
        ];
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['deadline']) || !$aidToImport['deadline']) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONGOING]);
        } else {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        }
        
        if ($aidRecurrence instanceof AidRecurrence) {
            $aid->setAidRecurrence($aidRecurrence);
        }
        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['type']) || !$aidToImport['type']) {
            return $aid;
        }

        if (in_array($aidToImport['type'], ['Subvention', 'Aide', 'Aide & subvention'])) {
            $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => AidType::SLUG_GRANT]);
            if ($aidType instanceof AidType) {
                $aid->addAidType($aidType);
            }
        }
        return $aid;
    }
}