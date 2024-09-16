<?php

namespace App\Tests\Controller\Aid;

use App\Entity\Alert\Alert;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

// php bin/phpunit src/Tests/Controller/Aid/AidControllerTest.php
class AidControllerTest extends WebTestCase
{
    public function testCreateAlert()
    {
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        // Charge utilisateur
        $user = $userRepository->find(1);
        // Identifie utilisateur
        $client->loginUser($user);

        // on appel la route avec un paramètre (obligatoire pour créer une alerte)
        $route = $router->generate('app_aid_aid', ['organizationType' => 'commune']);
        $crawler = $client->request('GET', $route);

        // selectionne le bouton submit du form[name="alert_create"]
        $form = $crawler->filter('form[name="alert_create"] button:contains("Créer une alerte")')->form([
            'alert_create[title]' => 'Titre Test',
            'alert_create[alertFrequency]' => Alert::FREQUENCY_DAILY_SLUG,
        ]);
        // Envoi du formulaire
        $client->submit($form);

        // On vérifie la présence du message flash
        $this->assertStringContainsString('Votre alerte a bien été créée', $client->getResponse()->getContent());
    }
}