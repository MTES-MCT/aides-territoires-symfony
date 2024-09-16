<?php

namespace App\DataFixtures;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Service\Various\StringService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Pour insérer les données dans une base de données de test, exécutez la commande suivante :
 * php bin/console --env=test doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private StringService $stringService,
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernel
    ) {
        // Supprimer et recréer la base de données
        $this->resetDatabase();
        // $this->updateSchema($managerRegistry->getManager());
    }

    public function load(ObjectManager $manager): void
    {

        
        // perimeterTest
        $perimeter = new Perimeter();
        $perimeter->setName('perimeterTest');
        $perimeter->setUnaccentedName($this->stringService->getNoAccent($perimeter->getName()));
        $perimeter->setCode('perimeterTest');
        $perimeter->setScale(Perimeter::SCALE_ADHOC);
        $perimeter->setContinent(Perimeter::CODE_EUROPE);
        $perimeter->setCountry(Perimeter::CODE_FRANCE);
        $manager->persist($perimeter);

        // organizationTypeGroup
        $organizationTypeGroup = new OrganizationTypeGroup();
        $organizationTypeGroup->setName('Collectivités');
        $manager->persist($organizationTypeGroup);

        // organizationType
        $organizationType = new OrganizationType();
        $organizationType->setName('Commune');
        $organizationType->setSlug($this->stringService->getSlug($organizationType->getName()));
        $organizationType->setOrganizationTypeGroup($organizationTypeGroup);
        $manager->persist($organizationType);

        // organization  test
        $organization = new Organization();
        $organization->setName('organizationTest');
        $organization->setSlug($this->stringService->getSlug($organization->getName()));
        $organization->setOrganizationType($organizationType);

        /**
         * USER ADMIN
         */
        $admin = new User();
        $admin->setEmail('admin@aides-territoires.beta.gouv.fr');
        $admin->setFirstname('Ad');
        $admin->setLastname('Min');
        $admin->setPassword($this->passwordEncoder->hashPassword($admin, '#123Password'));
        $admin->addRole(User::ROLE_ADMIN);
        $admin->setTotpSecret('rand0mT0tpS3cr3t');
        $manager->persist($admin);

        // Backer
        $backer = new Backer();
        $backer->setName('Backer test');
        $backer->setSlug($this->stringService->getSlug($backer->getName()));
        $backer->setPerimeter($perimeter);
        $backer->addOrganization($organization);
        $backer->setActive(true);
        $backer->setIsCorporate(false);
        $backer->setIsSpotlighted(false);
        $manager->persist($backer);

        // Program
        $program = new Program();
        $program->setName('Program test');
        $program->setSlug($this->stringService->getSlug($program->getName()));
        $program->setShortDescription('Short description');
        $program->setIsSpotlighted(false);
        $manager->persist($program);

        // ProjectReference
        $projectReference = new ProjectReference();
        $projectReference->setName('Project Reference test');
        $projectReference->setSlug($this->stringService->getSlug($projectReference->getName()));
        $manager->persist($projectReference);

        // CategoryTheme
        $categoryTheme = new CategoryTheme();
        $categoryTheme->setName('Category Theme test');
        $categoryTheme->setSlug($this->stringService->getSlug($categoryTheme->getName()));
        $categoryTheme->setShortDescription('Short description');
        $manager->persist($categoryTheme);

        // Category
        $category = new Category();
        $category->setName('Category test');
        $category->setSlug($this->stringService->getSlug($category->getName()));
        $category->setCategoryTheme($categoryTheme);
        $category->setShortDescription('Short description');
        $manager->persist($category);

        // AidType
        $aidType = new AidType();
        $aidType->setName('Aide Type test');
        $aidType->setSlug($this->stringService->getSlug($aidType->getName()));
        $manager->persist($aidType);

        // AidStep
        $aidStep = new AidStep();
        $aidStep->setName('Aide Step test');
        $aidStep->setSlug($this->stringService->getSlug($aidStep->getName()));
        $manager->persist($aidStep);

        // Aid Recurrence
        $aidRecurrence = new AidRecurrence();
        $aidRecurrence->setName('Aide Recurrence test');
        $aidRecurrence->setSlug($this->stringService->getSlug($aidRecurrence->getName()));
        $manager->persist($aidRecurrence);

        // AidDestination
        $aidDestination = new AidDestination();
        $aidDestination->setName('Aide Destination test');
        $aidDestination->setSlug($this->stringService->getSlug($aidDestination->getName()));
        $manager->persist($aidDestination);


        // Aide
        $aid = new Aid();
        $aid->setPerimeter($perimeter);
        $aid->setOrganization($organization);
        $aid->setAuthor($admin);
        $aid->setName('Aide test');
        $aid->setNameInitial('Aide test');
        $aid->setSlug($this->stringService->getSlug($aid->getName()));
        $aid->addAidAudience($organizationType);

        $aidFinancer = new AidFinancer();
        $aidFinancer->setBacker($backer);
        $aid->addAidFinancer($aidFinancer);

        $aid->addAidType($aidType);
        $aid->addAidStep($aidStep);
        $aid->setAidRecurrence($aidRecurrence);
        $aid->addAidDestination($aidDestination);

        $aid->setDescription('Description longue');
        $aid->setProjectExamples('Exemples de projets');
        $aid->setContact('Contact');
        $aid->addCategory($category);
        $aid->addProjectReference($projectReference);
        $aid->setEligibility('Eligibility');
        $aid->setOriginUrl('https://www.example.com');
        $aid->setApplicationUrl('https://www.example.com');

        $aid->setStatus(Aid::STATUS_PUBLISHED);
        $aid->setTimePublished(new \DateTime());
        $aid->setDatePublished(new \DateTime());
        $manager->persist($aid);



        /**
         * USER
         */
        $user = new User();
        $user->setEmail('user@aides-territoires.beta.gouv.fr');
        $user->setFirstname('Us');
        $user->setLastname('er');
        $user->setPassword($this->passwordEncoder->hashPassword($user, '#123Password'));
        $user->addRole(User::ROLE_USER);
        $user->setPerimeter($perimeter);
        $user->setApiToken('tokenApiTest');
        $organization->addBeneficiairy($user);
        $manager->persist($user);

        /**
         * SAUVEGARDE
         */
        $manager->flush();
    }

    private function updateSchema(ObjectManager $manager): void
    {
        $classes = $manager->getMetadataFactory()->getAllMetadata();

        if (!empty($classes)) {
            $schemaTool = new SchemaTool($manager);
            $schemaTool->updateSchema($classes, true);
        }
    }

    private function resetDatabase(): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        // Supprimer la base de données
        $input = new ArrayInput([
            'command' => 'doctrine:database:drop',
            '--force' => true,
            '--if-exists' => true,
            '--env' => 'test',
        ]);
        $application->run($input, new NullOutput());

        // Créer la base de données
        $input = new ArrayInput([
            'command' => 'doctrine:database:create',
            '--env' => 'test',
        ]);
        $application->run($input, new NullOutput());

        // Mettre à jour le schéma
        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
            '--env' => 'test',
        ]);
        $application->run($input, new NullOutput());
    }
}
