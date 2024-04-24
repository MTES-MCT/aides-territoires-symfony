<?php

namespace App\DataFixtures;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationAccess;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Service\Various\StringService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
/**
 * Pour insérer les données dans une base de données de test, exécutez la commande suivante :
 * php bin/console --env=test doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private StringService $stringService
    )
    {
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

        // organization  test
        $organization = new Organization();
        $organization->setName('organizationTest');
        $organization->setSlug($this->stringService->getSlug($organization->getName()));

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

        $organizationAccess = new OrganizationAccess();
        $organizationAccess->setOrganization($organization);
        $organizationAccess->setAdministrator(true);
        $organizationAccess->setEditAid(true);
        $organizationAccess->setEditPortal(true);
        $organizationAccess->setEditBacker(true);
        $organizationAccess->setEditProject(true);

        $user->addOrganizationAccess($organizationAccess);
        $manager->persist($user);

        /**
         * SAUVEGARDE
         */
        $manager->flush();
    }
}
