<?php

namespace App\Command\Script;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Reference\KeywordReference;
use App\Service\Reference\ReferenceService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:keywords_to_reference', description: 'Import des mots-clés référents')]
class AddKeywordToReferenceCommand extends Command
{

    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import des anciens mots-clés';
    protected string $commandTextEnd = '>Import des anciens mots-clés';

    

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ReferenceService $referenceService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            // import des keywords
            $this->importKeyword($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function importKeyword($input, $output): void
    {
        $keywords = array(
            array("Accès à la culture", "Accès aux services"),
            array("Action sociale"),
            array("Actions internationales", "Adhésion UE", "Europe", "Europe et international"),
            array("Aéronautique & spatial", "Aéronautique & Spatial"),
            array("Agriculture / Ruralité", "Agriculture & Pêche", "Agriculture et agroalimentaire", "Développement durable", "Ruralité"),
            array("Alimentation et biodéchets", "Biodéchets"),
            array("Aménagement du territoire", "Développement Territorial", "Démarches territoriales", "Espaces publics"),
            array("Apprentissage", "Éducation & Formation", "Éducation artistique et culturelle", "Formation professionnelle", "Enseignement supérieur", "Scolaire"),
            array("Archéologie", "Archives", "Patrimoines"),
            array("Architecture", "Construction BTP", "Design"),
            array("Arts de la marionnette", "Arts de la rue", "Arts du cirque", "Arts plastiques", "Arts visuels", "Création artistique", "Culture", "Danse", "Musique", "Spectacle vivant", "Théâtre"),
            array("Assainissement des eaux", "Eau", "Environnement"),
            array("Audiovisuel", "Cinéma", "Culture, média & communication", "Culture, Média & Communication", "Médias", "Presse", "Presse écrite"),
            array("Autre", "Divers", "Tous les secteurs"),
            array("Bibliothèques", "Lecture", "Livre et lecture"),
            array("Biodéchets", "Gestion des déchets", "Tri et recyclage"),
            array("Biodiversité", "Conservation-restauration", "Patrimoines", "Protection du patrimoine"),
            array("Bois, biomasse énergie", "Énergie", "Énergie (Autres)", "Éclairage public", "Géothermie", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Cimetière / Enclos paroissiaux", "Funérarium"),
            array("Cinéma", "Arts visuels", "Culture, média & communication", "Culture, Média & Communication", "Sorties, expositions"),
            array("Circulation des biens culturels"),
            array("Citoyenneté", "Citoyenneté & Droits Humains", "Égalité et diversité"),
            array("Cohésion sociale", "Famille et enfance", "Jeunesse"),
            array("Commerce & Industrie", "Commerces", "Développement économique", "Industries culturelles et créatives", "Industries musicales", "Innovation numérique", "Technologies & Digital"),
            array("Compostage", "Gestion des déchets", "Réemploi,réparation"),
            array("Conservation-restauration", "Patrimoines"),
            array("Construction BTP", "Aménagement du territoire", "Développement Territorial", "Espaces publics"),
            array("Coopération & Développement", "Actions internationales"),
            array("Création artistique", "Arts plastiques", "Arts visuels"),
            array("Culture", "Arts de la marionnette", "Arts de la rue", "Arts du cirque"),
            array("Culture, média & communication", "Culture, Média & Communication", "Médias", "Presse", "Presse écrite"),
            array("Danse", "Arts du cirque"),
            array("Démarches territoriales", "Aménagement du territoire", "Développement Territorial", "Espaces publics"),
            array("Démocratisation", "Accès à la culture"),
            array("Design", "Arts plastiques"),
            array("Développement culturel", "Création artistique", "Pratiques artistiques et culturelles"),
            array("Développement durable", "Agriculture et agroalimentaire", "Environnement", "Transition écologique"),
            array("Développement économique", "Commerce & Industrie", "Économie Sociale", "Economie sociale et solidaire", "Emploi", "Emploi, travail, formation, professions culturelles", "Finance", "France Relance", "Fret"),
            array("Développement Territorial", "Aménagement du territoire", "Espaces publics"),
            array("e-santé", "Santé", "Santé numérique"),
            array("Éclairage public", "Énergie", "Énergie (Autres)", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Ecole", "Éducation & Formation", "Éducation artistique et culturelle", "Éducation aux médias et à l'information (EMI)", "Scolaire"),
            array("Économie Sociale", "Economie sociale et solidaire"),
            array("Économie sociale et solidaire", "Economie sociale et solidaire"),
            array("Éducation & Formation", "Éducation artistique et culturelle", "Formation professionnelle", "Enseignement supérieur", "Scolaire"),
            array("Éducation artistique et culturelle", "Éducation aux médias et à l'information (EMI)", "Scolaire"),
            array("Efficacité énergétique", "Énergie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Égalité et diversité", "Citoyenneté", "Citoyenneté & Droits Humains"),
            array("Emploi", "Emploi, travail, formation, professions culturelles", "Formation professionnelle"),
            array("Emploi, travail, formation, professions culturelles", "Formation professionnelle"),
            array("Energie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Energie (Autres)", "Énergie", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Enseignement supérieur", "Éducation & Formation", "Scolaire"),
            array("Environnement", "Développement durable", "Transition écologique"),
            array("Environnement & Climat", "Développement durable", "Transition écologique"),
            array("Espaces publics", "Aménagement du territoire", "Développement Territorial"),
            array("Eté culturel", "Sorties, expositions"),
            array("Études et statistiques", "Études et statistiques culturelles"),
            array("Études et statistiques culturelles", "Études et statistiques"),
            array("Europe", "Europe et international"),
            array("Europe et international", "Europe"),
            array("Famille et enfance", "Jeunesse"),
            array("Finance", "France Relance"),
            array("Formation professionnelle", "Éducation & Formation", "Emploi", "Emploi, travail, formation, professions culturelles", "Scolaire"),
            array("France Relance", "Finance"),
            array("Fret"),
            array("FSS"),
            array("Funérarium", "Cimetière / Enclos paroissiaux"),
            array("Géothermie", "Énergie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Nucléaire", "Solaire", "Valorisation énergétique"),
            array("Habitat", "Logement", "Logement et habitat"),
            array("Handicap, accessibilité"),
            array("Hébergement touristique", "Tourisme"),
            array("Industries culturelles et créatives", "Industries musicales", "Innovation numérique", "Technologies & Digital"),
            array("Industries musicales", "Industries culturelles et créatives", "Innovation numérique", "Technologies & Digital"),
            array("Innovation numérique", "Industries culturelles et créatives", "Industries musicales", "Technologies & Digital"),
            array("Jeunesse", "Famille et enfance"),
            array("Journées Européennes du Patrimoine", "Sorties, expositions"),
            array("Justice, Sécurité, Défense"),
            array("Langue française", "Langue française, langues de France"),
            array("Langue française, langues de France", "Langue française"),
            array("Langues régionales"),
            array("Livre et lecture", "Bibliothèques", "Lecture"),
            array("Logement", "Logement et habitat", "Habitat"),
            array("Logement et habitat", "Habitat", "Logement"),
            array("Loisirs"),
            array("Lutte contre les incendies"),
            array("Mairie", "Services Aux Organisations"),
            array("Médias", "Culture, média & communication", "Culture, Média & Communication", "Presse", "Presse écrite"),
            array("Méthanisation", "Gestion des déchets", "Tri et recyclage"),
            array("Métiers d'art"),
            array("Mobilités", "Transport"),
            array("Mode"),
            array("Monuments historiques et sites patrimoniaux", "Patrimoines"),
            array("Musées, lieux d'exposition", "Patrimoines"),
            array("Musique", "Arts de la marionnette", "Arts de la rue", "Arts du cirque", "Arts plastiques", "Arts visuels", "Création artistique", "Culture", "Spectacle vivant", "Théâtre"),
            array("nature en ville", "Environnement", "Transition écologique"),
            array("Nucléaire", "Énergie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Solaire", "Valorisation énergétique"),
            array("Patrimoines", "Conservation-restauration", "Monuments historiques et sites patrimoniaux", "Musées, lieux d'exposition", "Photographie", "Protection du patrimoine"),
            array("Petit patrimoine vernaculaire / Patrimoine et monument historiques", "Patrimoines"),
            array("Photographie", "Patrimoines"),
            array("Politiques culturelles", "Pratiques artistiques et culturelles", "Pratiques culturelles"),
            array("Pratiques artistiques et culturelles", "Création artistique", "Culture", "Politiques culturelles", "Pratiques culturelles"),
            array("Pratiques culturelles", "Création artistique", "Culture", "Politiques culturelles", "Pratiques artistiques et culturelles"),
            array("Pratiques, consommations et usages culturels", "Pratiques culturelles"),
            array("Presse", "Culture, média & communication", "Culture, Média & Communication", "Médias", "Presse écrite"),
            array("Presse écrite", "Culture, média & communication", "Culture, Média & Communication", "Médias", "Presse"),
            array("Production durable", "Développement durable", "Transition écologique"),
            array("Protection civile & risques", "Protection Civile & Risques"),
            array("Protection Civile & Risques", "Protection civile & risques"),
            array("Protection du patrimoine", "Conservation-restauration", "Patrimoines"),
            array("Recherche", "Recherche & Innovation", "Recherche scientifique"),
            array("Recherche & Innovation", "Coopération & Développement", "Recherche", "Recherche scientifique"),
            array("Recherche scientifique", "Recherche", "Recherche & Innovation"),
            array("Récupération de chaleur", "Énergie", "Énergie (Autres)", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Solaire", "Valorisation énergétique"),
            array("Réemploi,réparation", "Gestion des déchets", "Tri et recyclage"),
            array("Réseaux de chaleur et de froid", "Énergie", "Énergie (Autres)", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Solaire", "Valorisation énergétique"),
            array("Restauration", "Patrimoines"),
            array("Route", "Transport"),
            array("Ruralité", "Agriculture / Ruralité", "Développement Territorial"),
            array("Santé", "e-santé", "Santé numérique"),
            array("santé numérique", "e-santé", "Santé"),
            array("Scolaire", "Éducation & Formation", "Éducation artistique et culturelle", "Enseignement supérieur", "Formation professionnelle"),
            array("Services Aux Organisations", "Mairie"),
            array("Slow tourisme", "Tourisme"),
            array("Solaire", "Énergie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Valorisation énergétique"),
            array("Sorties, expositions", "Culture", "Été culturel", "Journées Européennes du Patrimoine"),
            array("Spectacle vivant", "Arts de la marionnette", "Arts de la rue", "Arts du cirque", "Arts plastiques", "Arts visuels", "Création artistique", "Culture", "Danse", "Musique", "Théâtre"),
            array("Sport", "Sports"),
            array("Sports", "Sport"),
            array("Technologies & Digital", "Commerce & Industrie", "Industries culturelles et créatives", "Industries musicales", "Innovation numérique", "Technologies & Digital"),
            array("télétravail"),
            array("Théâtre", "Arts du cirque", "Spectacle vivant"),
            array("Tiers-lieux"),
            array("Tourisme", "Hébergement touristique", "Slow tourisme"),
            array("Tous les secteurs", "Divers"),
            array("trait de côte", "Environnement", "Transition écologique"),
            array("Transition écologique", "Développement durable", "Environnement"),
            array("Transport", "Mobilités", "Route", "Tri et recyclage", "Zones à faibles émissions (ZFE)"),
            array("Tri et recyclage", "Gestion des déchets", "Réemploi,réparation"),
            array("Valorisation énergétique", "Énergie", "Énergie (Autres)", "Éclairage public", "Bois, biomasse énergie", "Énergies renouvelables", "Géothermie", "Nucléaire", "Solaire"),
            array("Vélo", "Mobilités", "Transport"),
            array("Villes et pays d’art et d’histoire", "Patrimoines"),
            array("Zones à faibles émissions (ZFE)", "Transport", "Mobilités")
        );
        
        
        /** @var KeywordSynonymlist $keywordSynonym */
        foreach ($keywords as $keyword) {
            $items = $keyword;
            $keywordReference = null;
            $keySelected = null;
            foreach ($items as $key => $item) {
                $keywordReference = $this->managerRegistry->getRepository(KeywordReference::class)->findOneBy(['name' => trim($item)]);
                if ($keywordReference instanceof KeywordReference) {
                    $keySelected = $key;
                    break;
                }
            }
            // on a trouvé un keywordReference
            if ($keywordReference instanceof KeywordReference) {
                // on reboucle pour ajouter les synonymes
                foreach ($items as $key => $item) {
                    if ($keySelected !== $key) {
                        $keywordReferenceSub = $this->managerRegistry->getRepository(KeywordReference::class)->findOneBy(['name' => trim($item)]);
                        if (!$keywordReferenceSub instanceof KeywordReference) {
                            $keywordReferenceSub = new KeywordReference();
                            $keywordReferenceSub->setName(trim($item));
                            $keywordReferenceSub->setIntention(false);
                            $keywordReference->addKeywordReference($keywordReferenceSub);
                            $this->managerRegistry->getManager()->persist($keywordReference);
                        }
                    }
                }
            } else {
                // on rebooucle, le premier sert de parent, les autres de synonymes
                foreach ($items as $key => $item) {
                    if ($key == 0) {
                        $keywordReference = new KeywordReference();
                        $keywordReference->setName(trim($item));
                        $keywordReference->setIntention(false);
                        $keywordReference->setParent($keywordReference);
                        $this->managerRegistry->getManager()->persist($keywordReference);
                    } else {
                        $keywordReferenceSub = new KeywordReference();
                        $keywordReferenceSub->setName(trim($item));
                        $keywordReferenceSub->setIntention(false);
                        $keywordReference->addKeywordReference($keywordReferenceSub);
                        $this->managerRegistry->getManager()->persist($keywordReference);
                    }
                }
            }

            $this->managerRegistry->getManager()->flush();
        }
    }
}
