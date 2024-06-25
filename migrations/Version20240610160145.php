<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240610160145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
        INSERT INTO sanctuarized_field (id,label,name,`position`) VALUES
            (1,'Titre','name',1),
            (2,'Description','description',3),
            (3,'Nom initial','nameInitial',2),
            (4,'Programmes d''aides','programs',4),
            (5,'Porteurs','aidFinancers',5),
            (6,'Suggérer un nouveau porteur','financerSuggestion',6),
            (7,'Instructeurs','instructors',7),
            (8,'Suggérer un nouvel instructeur','instructorSuggestion',8),
            (9,'Bénéficiaires de l’aide','aidAudiences',9),
            (10,'Types d''aide','aidTypes',10);
        ");
        $this->addSql("
        INSERT INTO sanctuarized_field (id,label,name,`position`) VALUES
            (11,'Taux de subvention min','subventionRateMin',11),
            (12,'Taux de subvention max','subventionRateMax',12),
            (13,'Taux de subvention (commentaire optionnel)','subventionComment',13),
            (14,'Montant du prêt maximum','loanAmount',14),
            (15,'Montant de l’avance récupérable','recoverableAdvanceAmount',15),
            (16,'Autre aide financière (commentaire optionnel)','otherFinancialAidComment',16),
            (17,'Aide Payante','isCharged',17),
            (18,'Appel à projet / Manifestation d’intérêt','isCallForProject',18),
            (19,'Description complète de l’aide et de ses objectif','description',19),
            (20,'Exemples d’applications ou de projets réalisés grâce à cette aide','projectExamples',20);
     ");
     $this->addSql("
        INSERT INTO sanctuarized_field (id,label,name,`position`) VALUES
            (21,'Thématiques de l''aide','categories',21),
            (22,'Projet référent','projectReferences',22),
            (23,'Récurrence','aidRecurrence',23),
            (24,'Date d’ouverture','dateStart',24),
            (25,'Date de clôture','dateSubmissionDeadline',25),
            (26,'Conditions d’éligibilité','eligibility',26),
            (27,'État d’avancement du projet pour bénéficier du dispositif','aidSteps',27),
            (28,'Types de dépenses / actions couvertes','aidDestinations',28),
            (29,'Zone géographique couverte par l’aide','perimeter',29),
            (30,'Vous ne trouvez pas de zone géographique appropriée ?','perimeterSuggestion',30);
     ");
     $this->addSql("
        INSERT INTO sanctuarized_field (id,label,name,`position`) VALUES
            (31,'Lien vers plus d’information','originUrl',31),
            (32,'Lien vers une démarche en ligne pour candidater','applicationUrl',32),
            (33,'Contact pour candidater','contact',33);
    ");

    }

    public function down(Schema $schema): void
    {
    }
}
