<?php

namespace App\Tests\Controller\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Alert\Alert;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Repository\User\UserRepository;
use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/Aid/AidControllerTest.php
class AidControllerTest extends AtWebTestCase
{
    /**
     * Test la validation du formulaire de recherche d'aides
     *
     * @return void
     */
    public function testAidSearch(): void
    {
        $route = $this->router->generate('app_aid_aid');
        $routeSuccess = $this->router->generate('app_aid_aid');
        $crawler = $this->client->request('GET', $route);

        // Ajouter dynamiquement une option au champ <select>
        $select = $crawler->filter('select[name="searchPerimeter"]')->first();
        $domSelect = $select->getNode(0);
        $option = $domSelect->ownerDocument->createElement('option', 'perimeterTest (Ad-hoc)');
        $option->setAttribute('value', '1');
        $domSelect->appendChild($option);

        $form = $crawler->selectButton('Rechercher')->form([
            'organizationType' => 'commune',
            'searchPerimeter' => 1,
        ]);

        $this->client->submit($form);

        // Vérifier que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifier que la route de succès est atteinte
        $this->assertStringContainsString(
            $routeSuccess,
            $this->client->getRequest()->getUri(),
            "La requête n'a pas atteint la route attendue."
        );
    }

    // test la création d'alerte
    public function testCreateAlert(): void
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

    public function testAidDetails(): void
    {
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);
        $aid = $aidRepository->find(1);
        $route = $this->router->generate('app_aid_aid_details', ['slug' => $aid->getSlug()]);
        $crawler = $this->client->request('GET', $route);

        // Vérifier que la réponse est réussie
        $this->assertResponseIsSuccessful();
    }

    public function testAidGenericToLocal(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        // Charge utilisateur
        $user = $userRepository->find(1);
        // Identifie utilisateur
        $this->client->loginUser($user);

        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);
        $aid = $aidRepository->find(1);

        $route = $this->router->generate('app_aid_generic_to_local', ['slug' => $aid->getSlug()]);

        // Effectue la requête
        $this->client->request('GET', $route);

        // Vérifie qu'il y a une redirection
        $this->assertTrue($this->client->getResponse()->isRedirect(), 'La réponse n\'est pas une redirection.');
    }
}
