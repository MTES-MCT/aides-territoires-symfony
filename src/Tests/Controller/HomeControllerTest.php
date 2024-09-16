<?php

namespace App\Tests\Controller;

use App\Tests\AtWebTestCase;

// php bin/phpunit src/Tests/Controller/HomeControllerTest.php
class HomeControllerTest extends AtWebTestCase
{
    /**
     * Vérifie la recherche d'aides depuis la home
     *
     * @return void
     */
    public function testSearchAidsOnHome()
    {
        $route = $this->router->generate('app_home');
        $routeSuccess = $this->router->generate('app_aid_aid');
        $crawler = $this->client->request('GET', $route);

        // Ajouter dynamiquement une option au champ <select>
        $select = $crawler->filter('select[name="searchPerimeter"]')->first();
        $domSelect = $select->getNode(0);
        $option = $domSelect->ownerDocument->createElement('option', 'perimeterTest (Ad-hoc)');
        $option->setAttribute('value', '1');
        $domSelect->appendChild($option);
        
        $form = $crawler->filter('#tabpanel-aids-panel form')->form([
            'organizationType' => 'commune',
            'searchPerimeter' => 1,
        ]);

        $this->client->submit($form);

        // Vérifier que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifier que la route de succès est atteinte
        $this->assertStringContainsString($routeSuccess, $this->client->getRequest()->getUri(), "La requête n'a pas atteint la route attendue.");
    }
}