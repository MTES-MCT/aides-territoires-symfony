<?php

namespace App\DataFixtures;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Entity\Perimeter\Perimeter;
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
