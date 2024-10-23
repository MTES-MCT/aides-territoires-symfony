<?php

namespace App\Command\ImportFlux;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command\ImportFlux\ImportFluxCommand;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Reference\KeywordReference;
use App\Repository\Aid\AidStepRepository;

#[AsCommand(name: 'at:import_flux:welcome_europe', description: 'Import de flux welcome europe')]
class ImportFluxWelcomeEuropeCommand extends ImportFluxCommand
{
    protected string $commandTextStart = '<Import de flux welcome europe';
    protected string $commandTextEnd = '>Import de flux welcome europe';

    protected ?string $importUniqueidPrefix = 'WE_';
    protected ?int $idDataSource = 7;

    protected function getImportUniqueid($aidToImport): ?string
    {
        if (!isset($aidToImport['ID'])) {
            return null;
        }
        return $this->importUniqueidPrefix . $aidToImport['ID'];
    }

    protected function callApi()
    {
        $aidsFromImport = [];
        $client = $this->getClient();

        for ($i = 0; $i < $this->nbPages; $i++) {
            $this->currentPage = $i;
            $importUrl = $this->dataSource->getImportApiUrl();
            if ($this->paginationEnabled) {
                $importUrl .= '?limit=' . $this->nbByPages . '&offset=' . ($this->currentPage * $this->nbByPages);
            }
            try {
                $response = $client->request(
                    'GET',
                    $importUrl,
                    $this->getApiOptions()
                );
                $content = $response->getContent();
                $content = $response->toArray();

                // retourne directement un tableau d'aides
                $aidsFromImport = $content;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            if (!count($aidsFromImport)) {
                throw new \Exception('Le flux ne contient aucune aide');
            }
        }


        return $aidsFromImport;
    }



    protected function getFieldsMapping(array $aidToImport, array $params = null): array // NOSONAR too complex
    {
        $description = '';
        if (isset($aidToImport['relations_programmes'])) {
            $programs = '<p>' . '<br/>' . $aidToImport['relations_programmes'] . '</p>';
            $banner_chapeau = $aidToImport['banner_cheapeau'] ?? null;
            $banner_budget = $aidToImport['banner_budget'] ?? null;
            $info_amount = $aidToImport['info_amount'] ?? null;
            $info_amorce = $aidToImport['info_amorce'] ?? null;
            $info_priories = $aidToImport['info_priories'] ?? null;

            $description =
                '<div>'
                . $programs;
            if ($banner_chapeau) {
                $description .= '<p>-----</p>' . $banner_chapeau;
            }
            if ($banner_budget) {
                $description .= '<p>-----</p>' . $banner_budget;
            }
            if ($info_amount) {
                $description .= '<p>-----</p>' . $info_amount;
            }
            if ($info_amorce) {
                $description .= '<p>-----</p>' . $info_amorce;
            }
            if ($info_priories) {
                $description .= '<p>-----</p>' . $info_priories;
            }
        }
        if (trim($description) == '') {
            $description = null;
        }

        $dateStart = null;
        $keys = ['dates_open-5', 'dates_open-4', 'dates_open-3', 'dates_open-2', 'dates_open-1'];
        foreach ($keys as $key) {
            if (isset($aidToImport[$key])) {
                try {
                    $dateStart = \DateTime::createFromFormat('Ymd', $aidToImport[$key]);
                } catch (\Exception $e) {
                    $dateStart = null;
                }
            }
        }
        if (!$dateStart instanceof \DateTime) {
            $dateStart = null;
        } else {
            // Force pour éviter les différence sur le fuseau horaire
            $dateStart = new \DateTime(date($dateStart->format('Y-m-d')));
            // Force les heures, minutes, et secondes à 00:00:00
            $dateStart->setTime(0, 0, 0);
        }

        $dateSubmissionDeadline = null;
        $keys = ['dates_deadline-4', 'dates_deadline-3', 'dates_deadline-2', 'dates_deadline1', 'deadline1'];
        foreach ($keys as $key) {
            if (isset($aidToImport[$key])) {
                try {
                    $dateSubmissionDeadline = \DateTime::createFromFormat('Ymd', $aidToImport[$key]);
                } catch (\Exception $e) {
                    $dateSubmissionDeadline = null;
                }
            }
        }
        if (!$dateSubmissionDeadline instanceof \DateTime) {
            $dateSubmissionDeadline = null;
        } else {
            // Force pour éviter les différence sur le fuseau horaire
            $dateSubmissionDeadline = new \DateTime(date($dateSubmissionDeadline->format('Y-m-d')));
            // Force les heures, minutes, et secondes à 00:00:00
            $dateSubmissionDeadline->setTime(0, 0, 0);
        }

        $contact = null;
        if (isset($aidToImport['info_utile'])) {
            $info_utile_tmp = str_replace('</a>', '</a>|%|', $aidToImport['info_utile']);
            $info_utile_list = explode('|%|', $info_utile_tmp);
            $info_utile = '';
            foreach ($info_utile_list as $key => $value) {
                if (trim($value) != '') {
                    $info_utile .= '<p>' . trim($value) . '</p>';
                }
            }
        }
        if (isset($aidToImport['info_contact']) && trim($aidToImport['info_contact']) != '') {
            $info_contact = '<p>' . $aidToImport['info_contact'] . '</p>';
        }
        if (isset($aidToImport['info_advice']) && trim($aidToImport['info_advice']) != '') {
            $info_advice = '<p>' . $aidToImport['info_advice'] . '</p>';
        }
        if (isset($info_utile) || isset($info_contact) || isset($info_advice)) {
            $contact = '<div>';
            if (isset($info_advice) && $info_advice != '') {
                $contact .= '<p>-----</p>' . $info_advice;
            }
            if (isset($info_contact) && $info_contact != '') {
                $contact .= '<p>-----</p>' . $info_contact;
            }
            if (isset($info_utile) && $info_utile != '') {
                $contact .= '<p>-----</p>' . $info_utile;
            }
            $contact .= '</div>';
        }

        $return = [
            'importDataMention' => 'Ces données sont mises à disposition par Welcomeurope à titre gracieux.',
            'europeanAid' => Aid::SLUG_EUROPEAN_SECTORIAL,
            'name' => isset($aidToImport['post_title'])
                ? $this->cleanName($aidToImport['post_title']) : null,
            'nameInitial' => isset($aidToImport['post_title'])
                ? $this->cleanName($aidToImport['post_title']) : null,
            'shortTitle' => isset($aidToImport['info_references'])
                ? $this->cleanName($aidToImport['info_references']) : null,
            'description' => $description,
            'eligibility' => $this->getCleanHtml($aidToImport['info_info-regions']),
            'isCallForProject' => true,
            'dateStart' => $dateStart,
            'dateSubmissionDeadline' => $dateSubmissionDeadline,
            'contact' => $contact,
        ];

        // on ajoute les données brut d'import pour comparer avec les données actuelles
        return $this->mergeImportDatas($return);
    }

    protected function setKeywords(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['filtres_sectors'])) {
            return $aid;
        }

        $categories = explode(';', $aidToImport['filtres_sectors']);

        foreach ($categories as $category) {
            $keyword = $this->managerRegistry->getRepository(KeywordReference::class)->findOneBy([
                'name' => $category
            ]);
            if ($keyword instanceof KeywordReference) {
                $aid->addKeywordReference($keyword->getParent() ?? $keyword);
            }
        }
        return $aid;
    }

    protected function setAidRecurrence(array $aidToImport, Aid $aid): Aid
    {
        if (!isset($aidToImport['dates_deadline-2']) || !isset($aidToImport['dates_open-2'])) {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
                ->findOneBy(['slug' => AidRecurrence::SLUG_RECURRING]);
        } else {
            $aidRecurrence = $this->managerRegistry->getRepository(AidRecurrence::class)
                ->findOneBy(['slug' => AidRecurrence::SLUG_ONEOFF]);
        }
        if ($aidRecurrence instanceof AidRecurrence) {
            $aid->setAidRecurrence($aidRecurrence);
        }
        return $aid;
    }

    protected function setAidAudiences(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        /*
            Exemple of string to process:
            "Centres de recherche,Autorités locales et régionales,Grandes entreprises,ONG de Développement,PME,Universités,Organisations Internationales,ONG,Association & ONG,Collectivité Territoriale & Entité Affiliée,Centre de recherche & université,Grande Entreprise (> 250 Salaries),Organisation UE & Internationale,Pme & Start-Up (< 249 Salaries)"
            Split the string, loop on the values and match to our AUDIENCES
         */
        if (!isset($aidToImport['filtres_beneficiaries'])) {
            return $aid;
        }

        $mapping = [
            'Administration état & entité affiliée' => [
                'public-org'
            ],
            'Chambre consulaire & Agence' => [
                'public-org'
            ],
            'Association & ONG' => [
                'association'
            ],
            'Collectivité Territoriale & Entité Affiliée' => [
                'commune',
                'epci',
                'department',
                'region',
                'special'
            ],
            'Etablissement Financier' => [
                'private-sector'
            ],
            'Organisme de Formation & Ecole' => [
                'public-org',
                'researcher'
            ],
            'Centre de recherche & université' => [
                'public-org',
                'researcher'
            ],
            'Organisation Professionnelle & Réseau' => [
                'public-cies',
                'association'
            ],
            'Grande Entreprise (> 250 Salaries)' => [
                'private-sector',
                'public-cies'
            ],
            'Médias et organisations culturelles' => [
                'private-sector',
                'public-org',
                'association'
            ],
            'Organisation UE & Internationale' => [
                'public-org',
            ],
            'Pme & Start-Up (< 249 Salaries)' => [
                'private-sector',
            ],
            'Pme & Start-Up (< 249 Salarié.es)' => [
                'private-sector',
            ],
            'Grande Entreprise (> 250 Salarié.es)' => [
                'private-sector',
            ],
            'Tous bénéficiaires' => [
                'commune',
                'epci',
                'department',
                'region',
                'special',
                'association',
                'private-person',
                'farmer',
                'private-sector',
                'public-cies',
                'public-org',
                'researcher'
            ],
            'Tout bénéficiaire' => [
                'commune',
                'epci',
                'department',
                'region',
                'special',
                'association',
                'private-person',
                'farmer',
                'private-sector',
                'public-cies',
                'public-org',
                'researcher'
            ],
            'Autorités locales et régionales' => [
                'commune',
                'epci',
                'department',
                'region',
                'special',
            ],
            'Association' => [
                'association'
            ],
            'Centres de formation' => [
                'private-sector',
            ],
            'PME' => [
                'private-sector',
            ],
            'Organisations de la société civile' => [
                'association'
            ],
            'ONG' => [
                'association'
            ],
            'Administrations Etats' => [
                'commune',
                'epci',
                'department',
                'region',
                'special',
            ],
            'Agences Chambres' => [
                'public-org'
            ],
            'Banques' => [
                'private-sector'
            ],
            'Centres de recherche' => [
                'researcher'
            ],
            'ONG de Développement' => [
                'association'
            ],
            'Universités' => [
                'public-org'
            ],
            'Organisations Internationales' => [
                'association'
            ],
            'Grandes entreprises' => [
                'private-sector'
            ],
            'Media & Organisation Culturelle' => [
                'private-sector',
            ],
            'Fonds d\'investissement' => [
                'private-sector',
            ],
            'Ecoles' => [
                'public-org'
            ],
            'Fédérations Syndicats' => [
                'association'
            ]
        ];

        $audiences = explode(';', html_entity_decode($aidToImport['filtres_beneficiaries']));

        if (is_array($audiences)) {
            foreach ($audiences as $audience) {
                if (isset($mapping[$audience])) {
                    foreach ($mapping[$audience] as $slug) {
                        $organizationType = $this->managerRegistry->getRepository(OrganizationType::class)
                            ->findOneBy(['slug' => $slug]);
                        if ($organizationType instanceof OrganizationType) {
                            $aid->addAidAudience($organizationType);
                        }
                    }
                }
            }
        }

        return $aid;
    }

    protected function setAidTypes(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        /*
        Exemple of string to process: "Appel à propositions"
        */
        if (!isset($aidToImport['info_categorie'])) {
            return $aid;
        }

        $mapping = [
            'Appel à propositions' => [
                AidType::SLUG_GRANT
            ],
            'Appel à propositions ' => [
                AidType::SLUG_GRANT
            ],
            'Programme de prix' => [
                AidType::SLUG_GRANT
            ],
            'Prix' => [
                AidType::SLUG_GRANT
            ],
        ];

        $types = explode(';', html_entity_decode($aidToImport['info_categorie']));

        if (is_array($types)) {
            foreach ($types as $type) {
                if (isset($mapping[$type])) {
                    foreach ($mapping[$type] as $slug) {
                        $aidType = $this->managerRegistry->getRepository(AidType::class)->findOneBy(['slug' => $slug]);
                        if ($aidType instanceof AidType) {
                            $aid->addAidType($aidType);
                        }
                    }
                }
            }
        }

        return $aid;
    }

    protected function setCategories(array $aidToImport, Aid $aid): Aid // NOSONAR too complex
    {
        /*
            Exemple of string to process:
            "je decouvre les metiers;je choisis mon metier ou ma formation;je rebondis tout au long de la vie;
            je m'informe sur les metiers"  # noqa
            Split the string, loop on the values and match to our Categories
        */
        if (!isset($aidToImport['filtres_sectors'])) {
            return $aid;
        }

        $mapping = [
            'Adhésion UE' => [
                'culture'
            ],
            'Aéronautique & Spatial' => [
                'industrie'
            ],
            'Agriculture & Pêche' => [
                'agriculture'
            ],
            'Agriculture - Pêche' => [
                'agriculture'
            ],
            'Citoyenneté' => [
                'culture'
            ],
            'Citoyenneté & Droits Humains' => [
                'culture'
            ],
            'Commerce & Industrie' => [
                'commerces-et-services',
                'industrie'
            ],
            'Construction BTP' => [
                'batiments-construction'
            ],
            'Coopération & Développement' => [
                'cooperation-transfrontaliere'
            ],
            'Coop. & Développement' => [
                'cooperation-transfrontaliere'
            ],
            'Culture Media & Communication' => [
                'medias'
            ],
            'Culture, média & communication' => [
                'medias'
            ],
            'Développement Territorial' => [
                'commerces-et-services',
                'revitalisation'
            ],
            'Économie Sociale' => [
                'economie-sociale-et-solidaire'
            ],
            'Éducation & Formation' => [
                'formation',
                'education'
            ],
            'Emploi' => [
                'emploi'
            ],
            'Énergie' => [
                'transition-energetique'
            ],
            'Energie' => [
                'transition-energetique'
            ],
            'Environnement & Climat' => [
                'biodiversite'
            ],
            'Finance' => [
                'fiscalite'
            ],
            'Habitat' => [
                'logement-habitat'
            ],
            'Justice, Sécurité, Défense' => [
                'securite'
            ],
            'Nucléaire' => [
                'industrie'
            ],
            'Protection Civile & Risques' => [
                'acces-aux-services'
            ],
            'Protection civile & risques' => [
                'acces-aux-services'
            ],
            'Recherche & Innovation' => [
                'innovation-et-recherche'
            ],
            'Santé' => [
                'sante'
            ],
            'Services Aux Organisations' => [
                'appui-methodologique'
            ],
            'Sport' => [
                'sport'
            ],
            'Technologies & Digital' => [
                'numerique'
            ],
            'Tourisme' => [
                'tourisme'
            ],
            'Transport' => [
                'transports-collectifs-et-optimisation-des-trafics'
            ],
            'Jeunesse' => [
                'jeunesse'
            ]
        ];

        $categories = explode(';', html_entity_decode($aidToImport['filtres_sectors']));

        if (is_array($categories)) {
            foreach ($categories as $category) {
                if (isset($mapping[$category])) {
                    foreach ($mapping[$category] as $slug) {
                        $cat = $this->managerRegistry->getRepository(Category::class)->findOneBy(['slug' => $slug]);
                        if ($cat instanceof Category) {
                            $aid->addCategory($cat);
                        }
                    }
                }
            }
        }

        return $aid;
    }

    protected function setAidSteps(array $aidToImport, Aid $aid): Aid
    {
        /** @var AidStepRepository $aidStepRepo */
        $aidStepRepo = $this->managerRegistry->getRepository(AidStep::class);

        $aidSteps = $aidStepRepo->findCustom([
            'slugs' => [
                AidStep::SLUG_PREOP,
                AidStep::SLUG_OP,
                AidStep::SLUG_POSTOP
            ]
        ]);
        foreach ($aidSteps as $aidStep) {
            $aid->addAidStep($aidStep);
        }
        return $aid;
    }
}
