<?php

namespace App\Tests\Controller\User;

use App\Entity\User\User;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// php bin/phpunit src/Tests/Controller/User/RegisterControllerTest.php
class RegisterControllerTest extends WebTestCase
{
    public function testUserRegistration(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $url = $router->generate('app_user_user_register');
    
        // Récupérer la page du formulaire pour obtenir le jeton CSRF
        $crawler = $client->request('GET', $url);
        $csrfToken = $crawler->filter('input[name="register[_token]"]')->attr('value');

        // Données du formulaire
        $formData = [
            'register[firstname]' => 'John',
            'register[lastname]' => 'Doe',
            'register[email]' => 'john.doe@example.com',
            'register[password][first]' => '#123Password',
            'register[password][second]' => '#123Password',
            'register[organizationType]' => 1,
            'register[perimeter]' => 1,
            'register[organizationName]' => 'Example Organization',
            'register[beneficiaryFunction]' => 'mayor',
            'register[beneficiaryRole]' => 'Example role',
            'register[isBeneficiary]' => true,
            'register[isContributor]' => true,
            'register[acquisitionChannel]' => 'animator',
            'register[mlConsent]' => False,
            
            
            
            // Assurez-vous que l'ID correspond à un type d'organisation valide
            // Assurez-vous que l'ID correspond à un périmètre valide
            'register[_token]' => $csrfToken, // Ajouter le jeton CSRF
        ];
    
        // Envoyer les données en POST
        $client->request('POST', $url, $formData);
    
        // Vérifier la réponse après soumission
        $this->assertResponseIsSuccessful(); // Assure que la réponse est OK
    
        // Vérifier la présence du texte "Votre formulaire contient des erreurs."
        if ($client->getCrawler()->filter('body:contains("Votre formulaire contient des erreurs.")')->count() > 0) {
            $this->fail('formulaire invalide');
        }

        // Vérifier s'il y a des erreurs de formulaire
        $crawler = $client->followRedirect(); // Suivre la redirection après soumission
        if ($crawler->filter('.fr-error-text')->count() > 0) {
            $errors = $crawler->filter('.fr-error-text')->each(function ($node) {
                return $node->text();
            });
            $this->fail('Le formulaire contient des erreurs : ' . implode(', ', $errors));
        }
        // Vérifier l'URL de redirection
        $redirectUrl = $client->getResponse()->headers->get('Location');
        $dashboardUrl = $router->generate('app_user_dashboard');
        $this->assertEquals($dashboardUrl, $redirectUrl, "L'URL de redirection est incorrecte : $redirectUrl");
    
        // Vérifier le contenu de la page après redirection
        $crawler = $client->followRedirect(); // Suivre la redirection
        $this->assertSelectorTextContains('h1', 'Mon compte');
    
        // Vérifier que l'utilisateur a été ajouté à la base de données
        $container = $client->getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneByEmail('john.doe@example.com');
    
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('Doe', $user->getLastname());
    }
}