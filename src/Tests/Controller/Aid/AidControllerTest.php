<?php

namespace App\Tests\Controller\Aid;

use App\Entity\Alert\Alert;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/Aid/AidControllerTest.php
class AidControllerTest extends AtWebTestCase
{
    public function testCreateAlert()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        // Charge utilisateur
        $user = $userRepository->find(1);
        // Identifie utilisateur
        $this->client->loginUser($user);

        // on appel la route avec un paramètre (obligatoire pour créer une alerte)
        $route = $this->router->generate('app_aid_aid', ['organizationType' => 'commune']);
        $crawler = $this->client->request('GET', $route);

        // selectionne le bouton submit du form[name="alert_create"]
        $form = $crawler->filter('form[name="alert_create"] button:contains("Créer une alerte")')->form([
            'alert_create[title]' => 'Titre Test',
            'alert_create[alertFrequency]' => Alert::FREQUENCY_DAILY_SLUG,
        ]);
        // Envoi du formulaire
        $this->client->submit($form);

        // On vérifie la présence du message flash
        $this->assertStringContainsString('Votre alerte a bien été créée', $this->client->getResponse()->getContent());
    }
}