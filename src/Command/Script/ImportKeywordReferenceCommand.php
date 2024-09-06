<?php

namespace App\Command\Script;

use App\Entity\Reference\KeywordReference;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'at:script:keyword_import_references', description: 'Import des mots-clés référents')]
class ImportKeywordReferenceCommand extends Command
{

    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import des mots-clés référents';
    protected string $commandTextEnd = '>Import des mots-clés référents';



    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try {
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
        $object_projects = [
            "accessibilité" => ["accessibilités", "adaptabilité", "adaptabilités", "facilité l'accès", "fauteuil roulant", "handicap", "handicapé", "handicaps", "mise aux normes", "mobilité réduite", "pmr"],
            "activités cour école" => ["amusements récréation", "jeux de cours", "jeux récréatifs"],
            "agriculture urbaine" => ["agriculture verticale", "ferme urbaine", "jardin partagé", "jardin toiture", "jardinage urbain", "jardins toiture"],
            "aire accueil" => ["aires accueil", "camping", "campings", "caravane", "caravanes"],
            "ameublement cantine" => ["équipement réfectoire", "mobilier matériel cantines", "ustensile cantine"],
            "antenne 4g" => ["4g", "5g", "mât télécommunication", "mât de télécommunication", "relais cellulaire", "station base"],
            "autopartage" => ["covoiturage", "transport partagé", "voiturage"],
            "baie vitrée" => ["baies vitrées", "entrée", "entrées", "fenêtre", "fenêtres", "huisserie", "huisseries", "issue", "issues", "lucarne", "lucarnes", "menuiserie", "menuiseries", "mur", "murs", "ouverture", "ouvertures", "portail", "portails", "porte", "portes"],
            "barrière naturelle" => ["buisson", "buissons", "clôtures végétales", "haie", "haies"],
            "bassin" => ["bassins", "berge", "berges", "biodiversité", "biodiversités", "marais", "mare", "marécage", "marécages", "mares", "nature", "noue", "noues", "renaturation", "zone humide"],
            "bassin municipal" => ["centre aquatique", "parc aquatique", "piscine", "piscine municipale", "piscines"],
            "bâtiment communal" => ["bâtiment", "bâtiments", "bâtiment public", "bâtiments communaux", "bâtiments publics", "espace multifonction", "espaces multifonctions", "foyer rural", "foyers ruraux", "garage communal", "garages communaux", "local associatif", "local périscolaire", "locaux associatifs", "locaux périscolaires", "mairie", "maison associations", "salle fêtes", "salle polyvalente"],
            "bâtiment scolaire" => ["centre apprentissage", "collège", "collèges", "l'école", "école", "école élémentaire", "école maternelle", "école primaire", "écoles", "établissement scolaire", "groupe scolaire", "institution éducative", "lycée", "lycées", "salle de classe", "salles de classe", "pôle scolaire", "l'école", "patrimoine scolaire"], // -"école de musique", -"école de conduiteé
            "bibliothèque" => ["bibliothèque municipale", "bibliothèque publique", "bibliothèque ville", "bibliothèques", "médiathèque", "médiathèques"],
            "borne électrique" => ["bornes électriques", "bornes recharge", "irve", "mobilités vertes", "point charge", "points recharge", "rechargeur électrique", "station recharge", "stations recharge"],
            "bouche incendie" => ["deci", "hydrant", "hydrants", "incendie", "incendies", "poteaux incendies", "prise eau incendie"],
            "bourg" => ["bourgs", "centre ville", "centre ville", "centres villes", "centre bourg", "centres bourgs", "coeur de bourg", "place", "places", "village", "voie communale", "voies communales"],
            "boutique de quartier" => ["commerce de proximité", "commerces de proximité", "magasin local", "petit commerce"],
            "café associatif" => ["café communautaire", "café participatif", "café quartier", "bistrot", "épicerie participative"],
            "cafétéria scolaire" => ["cantine", "cantine scolaire", "réfectoire", "restaurant scolaire"],
            "calfeutrement" => ["étanchéité", "thermique"],
            "caméra sécurité" => ["télésurveillance", "vidéo protection", "vidéoprotection", "vidéosurveillance"],
            "canalisation eaux usées" => ["canalisations eaux usées", "eau pluviale", "eau potable", "eaux usées", "infrastructure sanitaire", "infrastructures sanitaires", "réseau d'assainissement", "réseaux d'assainissement", "système épuration"],
            "capteurs solaires" => ["cellules solaires", "champ photovoltaique", "énergie solaire", "modules solaires", "panneaux photovoltaïques", "panneaux solaires", "photovoltaique", "solaire", "capteurs photovoltaïques"],
            "cassis" => ["coussin berlinois", "coussins berlinois", "dos d'âne", "gendarme couché", "ralentisseur", "ralentisseurs"],
            "centre médical" => ["clinique", "cliniques", "maison de santé", "maisons de santé", "maison médicale", "maisons médicales", "pôle santé", "santé pluri-professionnels", "santé pluridisciplinaires", "MSP", "Clinique", "Centre médical", "Centre de soins", "Centre de santé", "Établissement de santé", "Polyclinique", "Hôpital de jour", "Dispensaire", "soin", "soins", "santé"],
            "centre sportif" => ["complexe sportif", "gymnase", "gymnases", "salle de sport", "salle des sports", "pôle sportif"],
            "chapelle" => ["lieux de culte", "chapelles", "cimetière", "cimetières", "columbarium", "columbariums", "église", "églises", "enterrement", "enterrements", "funéraille", "funérailles", "funéraire", "funéraires", "funérarium", "funérariums", "monument morts", "obsèque", "obsèques", "pierre tombale", "presbytère", "presbytères", "sépulture", "sépultures", "tombe", "tombes"],
            "chaudière" => ["chaudières", "chauffage", "chauffage urbain", "distributionde chaleur", "réseau de chaleur", "système de chauffage collectif"],
            "chaussée" => ["chaussées", "feux tricolores", "parking", "parkings", "réseau routier", "signalétique", "signalétiques", "voirie", "voiries"],
            "chemin piétonnier" => ["chemins piétonniers", "cyclable", "cyclables", "liaison douce", "piste cyclable", "piste vélo", "piste verte", "plan vélo", "sentier non motorisé", "vélovoie", "vélovoies", "voie cyclable", "voie douce", "voie vélo", "voie verte", "voirie"],
            "city park" => ["city stade", "espace vert municipal", "jardin public", "parc urbain", "skate park", "streetpark", "terrains multisports", "terrain multisport", "l'aire de loisir", "l'aire de loisirs", "aires de jeux", "aire de jeux", "aires de loisirs", "espace ludique", "espaces ludiques", "parc de jeux", "parcs de jeux", "terrain de jeux", "terrains de jeux"],
            "cour d'école" => ["cours d'école", "espace extérieur scolaire", "playground scolaire", "préau", "préaux"],
            "couverture solaire" => ["protection solaire", "toile tendue", "voile ombrage"],
            "crèche" => ["crèches", "garderie", "garderies", "halte garderie", "jardin enfants"],
            "crue" => ["crues", "débordements eau", "inondation", "inondations", "submersion", "submersions"],
            "dispositifs électroniques" => ["équipement numérique", "informatique", "matériel informatique", "numérique", "numériques", "ordinateur", "ordinateurs", "tableau numérique", "tableaux numériques", "tablette", "tablettes", "technologies numériques"],
            "éclairage" => ["éclairages", "illumination rues", "lampe rue", "lumière urbaine", "l'éclairage public", "éclairage public", "d'éclairage public"],
            "édifice religieux" => ["édifices religieux", "lieux culte", "monument sacré", "monuments sacrés", "patrimoine religieux"],
            "emplacement" => ["emplacements", "parcelle", "parcelles", "terrain", "terrains", "foncière", "foncières"],
            "environnement naturel cimetière" => ["espace vert cimetière", "parc funéraire vert", "cimetière"],
            "espace vert communautaire" => ["jardin", "jardin communautaire", "jardin partagé", "jardins", "potager collectif"],
            "friche" => ["friche industrielle", "friches industrielles", "friches"],
            "habitat social" => ["logement loyer modéré", "logement social", "logements sociaux", "résidence subventionnée"],
            "miroir de sécurité" => ["miroir de carrefour", "miroir de circulation sécurité routière", "miroir de circulation", "miroir convexe sécurité", "miroir de rue"], //"miroir" a été enlevé car trop large
            "passerelle" => ["passerelles", "pont", "ponts", "structure franchissement", "viaduc", "viaducs"],
            "réhabilitation environnementale" => ["renaturation", "restauration écologique", "restauration écosystème"],
            "secteur basse pollution" => ["zfe", "zone de circulation restreinte", "zone à faible émission", "zone verte", "zones de circulation restreinte", "zones à faibles émissions", "zones vertes"],
            "terrain de foot" => ["stade de football", "stade de foot", "terrain de football", "stades de football", "stades de foot", "terrains de foot", "terrains de football", "pelouse de football"],
            // ajout remi 2024-05-23
            'Logement' => ['Habitation', 'Domicile', 'Résidence', 'Maison', 'Appartement', 'Demeure', 'Pavillon', 'Hébergement'],
            'Bus' => ["Autobus", "Autocar", "Minibus", "Navette", "Transport en commun", "Véhicule de transport collectif", "Trolleybus"],
            'Télémédecine' => ["E-santé", "Téléconsultation", "Télésoin", "Télésanté", "Médecine à distance", 'Soins à distance', "Téléassistance médicale", "Santé numérique", 'Consultation en ligne'],
            'piéton' => ['piétons', 'passant', 'passants', 'piétonne', 'piétonnes', 'passante', 'passantes'],
        ];

        $verbs_synonyms_array = [
            "abriter" => ["assurance", "assurances", "assurer", "défendre", "défense", "défenses", "garde", "garder", "gardes", "préservation", "préservations", "préserver", "protection", "protections", "protéger", "sauvegarde", "sauvegarder", "sauvegardes", "sécurisation", "sécurisations", "sécuriser"],
            "accélération" => ["accélérations", "accélérer", "aiser", "allégement", "allégements", "alléger", "facilitation", "facilitations", "faciliter", "rendre facile", "simplification", "simplifications", "simplifier"],
            "acceptation" => ["acceptations", "accepter", "accueil", "accueillir", "accueils", "admettre", "admission", "admissions", "entertain", "hébergement", "hébergements", "héberger", "réception", "réceptions", "recevoir"],
            "acceptation" => ["acceptations", "accepter", "acquérir", "acquisition", "acquisitions", "adopter", "adoption", "adoptions", "assomption", "assomptions", "assumer", "attraper", "capture", "captures", "embrassement", "embrassements", "embrasser", "prendre", "prise", "prises", "saisie", "saisies", "saisir"],
            "acceptation" => ["acceptations", "accepter", "admettre", "admission", "admissions", "assomption", "assomptions", "assumer", "embauche", "embaucher", "embauches", "emploi", "emplois", "employer", "engagement", "engagements", "engager", "enrôlement", "enrôlements", "enrôler", "recrutement", "recrutements", "recruter"],
            "accompagnement" => ["accompagnements", "accompagner", "aide", "aider", "aides", "appui", "appuis", "appuyer", "assistance", "assistances", "assister", "encouragement", "encouragements", "encourager", "épaulement", "épaulements", "épauler", "faveur", "faveurs", "favoriser", "promotion", "promotions", "promouvoir", "renforcement", "renforcements", "renforcer", "soutenir", "soutien", "soutiens"],
            "accompagnement" => ["accompagnements", "accompagner", "aide", "aider", "aides", "appui", "appuis", "appuyer", "assistance", "assistances", "assister", "participation", "participations", "participer", "secourir", "secours", "secourss", "service", "services", "servir", "soutenir", "soutien", "soutiens"],
            "accomplir" => ["accomplissement", "accomplissements", "activation", "activations", "activer", "effectuer", "effet", "effets", "exécuter", "exécution", "exécutions", "faire", "fait", "faits", "menée", "menées", "mener", "procéder", "procédure", "procédures", "production", "productions", "produire", "réalisation", "réalisations", "réaliser", "rénovation", "rénovations", "rénover", "réussir", "réussite", "réussites"],
            "accomplir" => ["accomplissement", "accomplissements", "effectuer", "effet", "effets", "exécuter", "exécution", "exécutions", "faire", "fait", "faits", "menée", "menées", "mener", "opération", "opérations", "opérer", "procéder", "procédure", "procédures", "réalisation", "réalisations", "réaliser"],
            "accord" => ["accorder", "accords", "autorisation", "autorisations", "autoriser", "consentement", "consentements", "consentir", "laisser", "laissez-passer", "laissez-passers", "offre", "offres", "offrir", "permettre", "permission", "permissions"],
            "accord" => ["accorder", "accords", "don", "donner", "dons", "fournir", "fourniture", "fournitures", "offre", "offres", "offrir", "présentation", "présentations", "présenter", "procurance", "procurances", "procurer", "proposer", "proposition", "propositions"],
            "accord" => ["accorder", "accords", "allocation", "allocations", "allouer", "attribuer", "don", "donner", "dons", "fournir", "fourniture", "fournitures", "offre", "offres", "offrir", "présentation", "présentations", "présenter", "rendre"],
            "accroissement" => ["accroissements", "accroître", "amélioration", "améliorations", "améliorer", "augmentation", "augmentations", "augmenter", "booster", "dynamisation", "dynamisations", "dynamiser", "étendre", "études", "extension", "extensions", "optimisation", "optimisations", "optimiser"],
            "accroissement" => ["accroissements", "accroître", "amplification", "amplifications", "amplifier", "augmentation", "augmentations", "augmenter", "développement", "développements", "développer", "élévation", "élévations", "élever", "étendre", "extension", "extensions", "renforcement", "renforcements", "renforcer"],
            "achat" => ["achats", "acheter", "équipement", "équipements", "équiper"],
            "acquérir" => ["acquisition", "acquisitions", "atteindre", "atteinte", "atteintes", "gagner", "gain", "gains", "obtenir", "obtention", "obtentions", "réception", "réceptions", "recevoir", "remportement", "remportements", "remporter"],
            "acquérir" => ["acquisition", "acquisitions", "approvisionnement", "approvisionnements", "avoir", "choisir", "choix", "choixs", "gagner", "gain", "gains", "obtenir", "obtention", "obtentions", "procurance", "procurances", "réception", "réceptions", "recevoir", "s'approvisionner", "se procurer"],
            "action" => ["actions", "agir", "exécuter", "exécution", "exécutions", "faire", "fait", "faits", "fonctionnement", "fonctionnements", "fonctionner", "opération", "opérations", "opérer", "procéder", "procédure", "procédures", "travail", "travailler", "travails"],
            "adaptation" => ["adaptations", "adapter", "aménagement", "aménagements", "aménager", "arrangement", "arrangements", "arranger", "équipement", "équipements", "équiper", "étendre", "extension", "extensions", "installation", "installations", "installer", "organisation", "organisations", "organiser", "réaménagement", "réaménagements", "réaménager", "restructuration", "restructurations", "restructurer", "soutenir", "soutien", "soutiens", "structuration", "structurations", "structurer"],
            "adaptation" => ["adaptations", "adapter", "ajustement", "ajustements", "ajuster", "changement", "changements", "changer", "conversion", "conversions", "convertir", "équipement", "équipements", "équiper", "modification", "modifications", "modifier", "transformation", "transformations", "transformer"],
            "adaptation" => ["adaptations", "adapter", "changement", "changements", "changer", "conversion", "conversions", "convertir", "modification", "modifications", "modifier", "réforme", "réformer", "réformes", "réviser", "révision", "révisions", "transformation", "transformations", "transformer"],
            "adaptation" => ["adaptations", "adapter", "changement", "changements", "changer", "conversion", "conversions", "convertir", "modification", "modifications", "modifier", "réforme", "réformer", "réformes", "requalifier", "réviser", "révision", "révisions", "transformation", "transformations", "transformer"],
            "adaptation" => ["adaptations", "ajustement", "ajustements", "ajuster", "changement", "changements", "changer", "évoluer", "modification", "modifications", "modifier", "s'adapter", "se conformer", "se modifier", "transformation", "transformations", "transformer"],
            "adhérer" => ["adhésion", "adhésions", "collaboration", "collaborations", "collaborer", "contribuer", "contribution", "contributions", "engagement", "engagements", "implication", "implications", "impliquer", "inscription", "inscriptions", "intervenir", "intervention", "interventions", "joindre", "jonction", "jonctions", "participation", "participations", "participer", "prendre part", "prise", "prises", "s'inscrire", "s’engager"],
            "adjoindre" => ["adjonction", "adjonctions", "assimilation", "assimilations", "assimiler", "inclure", "inclusion", "inclusions", "incorporation", "incorporations", "incorporer", "insérer", "insertion", "insertions", "intégration", "intégrations", "intégrer"],
            "administration" => ["administrations", "administrer", "contrôle", "contrôler", "contrôles", "coordination", "coordinations", "coordonner", "direction", "directions", "diriger", "gérer", "gestion", "gestions", "gouvernance", "gouvernances", "gouverner", "organisation", "organisations", "organiser", "régulation", "régulations", "réguler", "superviser", "supervision", "supervisions"],
            "administration" => ["administrations", "administrer", "appliquer", "emploi", "emplois", "employer", "exécuter", "exécution", "exécutions", "imposer", "instauration", "instaurations", "instaurer", "mettre en œuvre", "mise", "mise en place", "mises", "utilisation", "utilisations", "utiliser"],
            "adopter" => ["adoption", "adoptions", "appliquer", "emploi", "emplois", "employer", "faire appel", "fait", "faits", "recourir", "se servir", "se tourner vers", "service", "services", "utilisation", "utilisations", "utiliser"],
            "affermir" => ["affermissement", "affermissements", "confort", "conforter", "conforts", "consolidation", "consolidations", "consolider", "fortification", "fortifications", "fortifier", "raffermir", "raffermissement", "raffermissements", "renforcement", "renforcements", "renforcer", "soutenir", "soutien", "soutiens", "stabilisation", "stabilisations", "stabiliser"],
            "agrandir" => ["agrandissement", "agrandissements", "conception", "conceptions", "concevoir", "construction", "constructions", "construire", "création", "créations", "créer", "développement", "développements", "développer", "élaborer", "fabriquer", "formuler", "inventer"],
            "allégement" => ["allégements", "alléger", "baisse", "baisser", "baisses", "diminuer", "diminution", "diminutions", "minorer", "minorité", "minorités", "réduction", "réductions", "réduire"],
            "allocation" => ["allocations", "allouer", "cofinancement", "cofinancements", "financement", "financements", "financer", "investir", "investissement", "investissements", "placement", "placements", "placer de l’argent"],
            "aménagement" => ["aménagements", "aménager", "cofinancement", "cofinancements", "conception", "conceptions", "concevoir", "construction", "constructions", "construire", "création", "créations", "créer", "développement", "développements", "développer", "érection", "érections", "ériger", "établir", "financement", "financements", "financer", "fondation", "fondations", "fonder", "génération", "générations", "générer", "innovation", "innovations", "innover", "installation", "installations", "installer", "instituer", "institution", "institutions"],
            "aménagement" => ["aménagements", "aménager", "étendre", "extension", "extensions", "isolation", "isoler", "modernisation", "modernisations", "moderniser", "réalisation", "réalisations", "réaliser des travaux", "réaménagement", "réaménagements", "réaménager", "reclycler", "reconstruction", "reconstructions", "reconstruire", "refaire", "réfection", "refonte", "refontes", "remettre", "remise", "remises", "rénovation", "rénovations", "rénover", "réparation", "réparations", "réparer", "restauration", "restaurations", "restaurer", "restructuration", "restructurations", "restructurer", "revalorisation", "revalorisations", "revaloriser", "travaux", "travaux de réparation", "travauxs", "changer", "changement"],
            "amplification" => ["amplifications", "amplifier", "augmentation", "augmentations", "augmenter", "élévation", "élévations", "élever", "hausser", "lever", "monter", "soulever"],
            "analyse" => ["analyser", "analyses", "appréciation", "appréciations", "apprécier", "calcul", "calculer", "calculs", "estimation", "estimations", "estimer", "évaluation", "évaluations", "évaluer", "examen", "examens", "examiner", "jugement", "jugements", "juger", "mesure", "mesurer", "mesures"],
            "analyse" => ["analyser", "analyses", "consultation", "consultations", "consulter", "étude", "études", "étudess", "étudier", "examen", "examens", "examiner", "exploration", "explorations", "explorer", "observation", "observations", "observer", "recherche", "rechercher", "recherches"],
            "analyse" => ["analyser", "analyses", "étude", "études", "étudess", "étudier", "évaluation", "évaluations", "évaluer", "examen", "examens", "examiner", "exploration", "explorations", "explorer", "inspecter", "inspection", "inspections", "investiguer", "scruter"],
            "anticipation" => ["anticipations", "anticiper", "avertir", "avertissement", "avertissements", "évitement", "évitements", "éviter", "information", "informations", "informer", "préparation", "préparations", "préparer", "prévenir", "prévention", "préventions", "prévision", "prévisions", "prévoir", "traitement", "traitements", "traiter"],
            "anticipation" => ["anticipations", "anticiper", "attendre", "planification", "planifications", "planifier", "préparation", "préparations", "préparer", "prévision", "prévisions", "prévoir", "projeter", "s'attendre"],
            "appliquer" => ["emploi", "emplois", "employer", "manipuler", "opération", "opérations", "opérer", "recourir à", "se servir de", "service", "services", "utilisation", "utilisations", "utiliser"],
            "appréhender" => ["assimilation", "assimilations", "assimiler", "comprendre", "connaître", "discerner", "gras", "percevoir", "saisie", "saisies", "saisir"],
            "apprendre" => ["assimilation", "assimilations", "assimiler", "comprendre", "étude", "études", "étudess", "étudier", "information", "informations", "maîtrise", "maîtriser", "maîtrises", "s'informer", "se former"],
            "approvisionnement" => ["approvisionnements", "approvisionner", "dotation", "dotations", "doter", "équipement", "équipements", "équiper", "fournir", "fourniture", "fournitures", "garnir", "garniture", "garnitures", "installation", "installations", "installer", "munir", "munition", "munitions"],
            "arrangement" => ["arrangements", "arranger", "concéder", "concession", "concessions", "établir", "ordonner", "ordre", "ordres", "organisation", "organisations", "organiser", "planification", "planifications", "planifier", "préparation", "préparations", "préparer", "programmation", "programmations", "programmer"],
            "assemblage" => ["assemblages", "assembler", "concentration", "concentrations", "concentrer", "convier", "convocation", "convocations", "fédération", "fédérations", "fédérer", "mobilisation", "mobilisations", "mobiliser", "rassemblement", "rassemblements", "rassembler", "regroupement", "regroupements", "regrouper", "réunion", "réunions", "réunir"],
            "augmentation" => ["augmentations", "augmenter", "magnification", "magnifications", "magnifier", "mettre en valeur", "mise", "mise en place", "mises", "promotion", "promotions", "promouvoir", "revalorisation", "revalorisations", "revaloriser", "valorisation", "valorisations", "valoriser"],
            "avancement" => ["avancements", "avancer", "exposer", "exposition", "expositions", "offre", "offres", "offrir", "présentation", "présentations", "présenter", "proposer", "proposition", "propositions", "soumettre", "soumission", "soumissions", "suggérer", "suggestion", "suggestions"],
            "avis" => ["conseil", "directive", "guide", "instruction", "orientation", "recommandation", "suggestion"],
            "bataille" => ["batailles", "battre", "combat", "combats", "combattre", "contestation", "contestations", "contester", "lutte", "lutter", "luttes", "opposition", "oppositions", "résistance", "résistances", "résister", "s'opposer"],
            "circonscrire" => ["contenir", "contrôle", "contrôler", "contrôles", "limiter", "modérer", "réduction", "réductions", "réduire", "régulation", "régulations", "réguler", "restreindre"],
            "classement" => ["classements", "classer"],
            "co-financer" => ["cofinancement", "cofinancements", "cofinancer", "financement", "financements", "financer", "fonds", "fondss", "investir", "investissement", "investissements", "subvention", "subventionner", "subventions"],
            "commande" => ["commandement", "commandements", "contrôle", "contrôler", "contrôles", "direction", "directions", "diriger", "domination", "dominations", "dominer", "gérer", "gestion", "gestions", "maîtrise", "maîtriser", "maîtrises", "régulation", "régulations", "réguler", "surmontage", "surmontages", "surmonter"],
            "commandement" => ["commandements", "commander", "conduire", "conduite", "conduites", "direction", "directions", "diriger", "gérer", "gestion", "gestions", "menée", "menées", "mener", "organisation", "organisations", "organiser", "pilotage", "pilotages", "piloter"],
            "commencement" => ["commencements", "commencer", "début", "débuter", "débuts", "établir", "initiation", "initiations", "initier", "instauration", "instaurations", "instaurer", "introduction", "introductions", "introduire", "lancement", "lancements", "lancer"],
            "commencement" => ["commencements", "commencer", "démarrer", "établir", "inaugurer", "initiation", "initiations", "initier", "instauration", "instaurations", "instaurer", "lancement", "lancements", "lancer", "ouverture", "ouvertures", "ouvrir"],
            "commencement" => ["commencements", "commencer", "début", "débuter", "débuts", "démarrer", "entreprendre", "établir", "inaugurer", "initiation", "initiations", "initier", "lancement", "lancements", "lancer"],
            "communication" => ["communications", "communiquer", "conseil", "conseiller", "conseils", "éducation", "éducations", "éduquer", "guidage", "guidages", "guider", "information", "informations", "informer", "sensibilisation", "sensibilisations", "sensibiliser"],
            "communication" => ["communications", "communiquer", "conveyer", "diffuser", "émettre", "passer", "propager", "répandre", "transmettre"],
            "comporter" => ["engagement", "engagements", "engager", "entraîner", "implication", "implications", "impliquer", "inclure", "inclusion", "inclusions", "nécessiter", "signification", "significations", "signifier"],
            "composer" => ["constituer", "construction", "constructions", "construire", "création", "créations", "créer", "établir", "former", "installation", "installations", "installer", "instituer", "institution", "institutions", "poser", "pose"],
            "conservation" => ["conservations", "conserver", "garde", "garder", "gardes", "maintenir", "maintien", "maintiens", "préservation", "préservations", "préserver", "protection", "protections", "protéger", "sauvegarde", "sauvegarder", "sauvegardes", "sécurisation", "sécurisations", "sécuriser"],
            "conservation" => ["conservations", "conserver", "entretenir", "entretien", "entretiens", "maintenir", "maintien", "maintiens", "préservation", "préservations", "préserver", "rénovation", "rénovations", "rénover", "réparation", "réparations", "réparer", "soutenir", "soutien", "soutiens"],
            "contestation" => ["contestations", "contester", "réplique", "répliquer", "répliques", "répondre", "réponse", "réponses", "rétorque", "rétorquer", "rétorques", "retour", "retourner", "retours", "satisfaction", "satisfactions", "satisfaire"],
            "contrôle" => ["contrôler", "contrôles", "épiage", "épiages", "épier", "examen", "examens", "examiner", "guet", "guets", "guetter", "inspecter", "inspection", "inspections", "observation", "observations", "observer", "surveillance", "surveillances", "surveiller", "vérification", "vérifications", "vérifier"],
            "cultiver" => ["culture", "cultures"],
            "définir" => ["définition", "définitions", "érection", "érections", "ériger", "établir", "implantation", "implantations", "implanter", "installation", "installations", "installer", "mettre", "mise", "mise en place", "mises", "placement", "placements", "placer", "poser"],
            "déploiement" => ["déploiements", "déployer", "développement", "développements", "développer", "élargir", "élargissement", "élargissements", "étalage", "étalages", "étaler", "étendre", "exposer", "exposition", "expositions", "extension", "extensions", "ouverture", "ouvertures", "ouvrir"],
            "désignation" => ["désignations", "désigner", "évocation", "évocations", "évoquer", "illustration", "illustrations", "illustrer", "incarnation", "incarnations", "incarner", "manifestation", "manifestations", "manifester", "représentation", "représentations", "représenter", "signification", "significations", "signifier", "symbolisation", "symbolisations", "symboliser"],
            "désimperméabiliser" => ["plantation", "plantations", "planter", "végétalisation", "végétalisations", "végétaliser", "verdir"],
            "devenir" => ["devenirs", "Être", "existence", "existences", "exister"],
            "diffuser" => ["dispenser", "distribuer", "émettre", "étendre", "extension", "extensions", "propager", "répandre", "transmettre"],
            "éclairage" => ["éclairages", "eclairer"],
            "embauche" => ["embaucher", "embauches", "emploi", "emplois", "employer", "engagement", "engagements", "engager", "enrôlement", "enrôlements", "enrôler", "recrutement", "recrutements", "recruter", "utilisation", "utilisations", "utiliser"],
            "encouragement" => ["encouragements", "encourager", "faveur", "faveurs", "favoriser", "incitation", "incitations", "inciter", "promotion", "promotions", "promouvoir"],
            "éprouver" => ["essayer", "évaluation", "évaluations", "évaluer", "examen", "examens", "examiner", "expérimenter", "exploration", "explorations", "explorer", "tenter", "tester"],
            "étude" => ["études", "étudess", "étudier", "examen", "examens", "examiner", "guet", "guets", "guetter", "observation", "observations", "observer", "poursuivre", "regarder", "suivre", "tracer"],
            "mission" => ["missions"],
            "modernisation" => ["modernisations", "moderniser", "reconstituer", "reconstitution", "reconstitutions", "refaire", "refonte", "refontes", "régénération", "régénérations", "régénérer", "remettre", "remise", "remises", "remplacement", "remplacements", "remplacer", "renforcement", "renforcements", "renforcer", "renouveler", "renouvellement", "renouvellements", "rétablir", "rétablissement", "rétablissements"],
            "reclasser" => ["requalifier"],
            "recyclage" => ["recyclages", "recycler", "réhabilitation", "réhabilitations", "réhabiliter"]
        ];

        // supprime les existants
        $keywordReferences = $this->managerRegistry->getRepository(KeywordReference::class)->findAll();
        /** @var KeywordReference $keywordReference */
        foreach ($keywordReferences as $keywordReference) {
            $keywordReference->setParent(null);
            $this->managerRegistry->getManager()->persist($keywordReference);
        }
        $this->managerRegistry->getManager()->flush();
        foreach ($keywordReferences as $keywordReference) {
            $this->managerRegistry->getManager()->remove($keywordReference);
        }
        $this->managerRegistry->getManager()->flush();

        // on reinitialiser l'id
        $connection = $this->managerRegistry->getConnection();
        $connection->exec("ALTER TABLE keyword_reference AUTO_INCREMENT = 1");


        // les ajoutes
        foreach ($verbs_synonyms_array as $name => $synonymes) {
            $c_action = new KeywordReference();
            $c_action->setName(strtolower($name));
            $c_action->setIntention(true);
            $this->managerRegistry->getManager()->persist($c_action);
            $this->managerRegistry->getManager()->flush();
            $c_action->setParent($c_action);
            $this->managerRegistry->getManager()->persist($c_action);

            foreach ($synonymes as $synonyme) {
                $c_action_synonym = new KeywordReference();
                $c_action_synonym->setName(strtolower($synonyme));
                $c_action_synonym->setIntention(true);
                $c_action_synonym->setParent($c_action);
                $this->managerRegistry->getManager()->persist($c_action_synonym);
            }
        }

        foreach ($object_projects as $name => $synonymes) {
            $c_action = new KeywordReference();
            $c_action->setName(strtolower($name));
            $c_action->setIntention(false);
            $this->managerRegistry->getManager()->persist($c_action);
            $this->managerRegistry->getManager()->flush();
            $c_action->setParent($c_action);
            $this->managerRegistry->getManager()->persist($c_action);

            foreach ($synonymes as $synonyme) {
                $c_action_synonym = new KeywordReference();
                $c_action_synonym->setName(strtolower($synonyme));
                $c_action_synonym->setIntention(false);
                $c_action_synonym->setParent($c_action);
                $this->managerRegistry->getManager()->persist($c_action_synonym);
            }
        }

        $this->managerRegistry->getManager()->flush();
    }
}
