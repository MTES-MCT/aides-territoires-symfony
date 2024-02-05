<?php

namespace App\DataFixtures\User;

use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Service\Various\StringService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * php bin/console --env=test doctrine:fixtures:load
 */
class UserFixtures extends Fixture
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



        // user  test
        $user = new User();
        $user->setEmail('remi.barret@beta.gouv.fr');
        $user->setFirstname('Rémi');
        $user->setLastname('Barret');
        $user->setPassword($this->passwordEncoder->hashPassword($user, '#MonMotDePasse42'));
        $user->addRole(User::ROLE_USER);
        $user->setPerimeter($perimeter);

        $manager->persist($user);
        // $manager->persist($product);

        $manager->flush();
    }
}
