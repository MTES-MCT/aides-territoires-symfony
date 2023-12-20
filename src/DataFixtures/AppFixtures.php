<?php

namespace App\DataFixtures;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Entity\User\UserAcquisitionChannel;
use App\Entity\User\UserFunction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        /**
         * USER ADMIN
         */
        // $admin = new User();
        // $admin->setEmail('barret.remi.pro@gmail.com');
        // $admin->setFirstname('Rémi');
        // $admin->setLastname('Barret');
        // $admin->setPassword($this->passwordEncoder->hashPassword($admin, '#MonMotDePasse42'));
        // $admin->addRole('ROLE_ADMIN');
        // $admin->setUserFunction($userFunction);
        // $admin->setUserAcquisitionChannel($userAcquisitionChannel);
        // $admin->setOrganizationType($organizationType);
        // $admin->setPerimeter($perimeter);
        // $admin->setOrganizationName('Nom organisation');
        // $admin->setNewsletterSubscription(false);
        // $manager->persist($admin);

        $manager->flush();
    }
}
