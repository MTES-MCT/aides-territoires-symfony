<?php

namespace App\Controller\User;

use App\Entity\Aid\Aid;
use App\Entity\User\FavoriteAid;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

class FavoriteAidController extends AbstractController
{
    #[Route('/aid/{slug}/toggle-favorite', name: 'app_aid_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(
        string $slug,
        Request $request,
        AidRepository $aidRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $aid = $aidRepository->findOneBy(['slug' => $slug]);
        
        if (!$aid) {
            throw $this->createNotFoundException('Aide non trouvée');
        }
        
        $user = $this->getUser();
        $favoriteAid = $entityManager->getRepository(FavoriteAid::class)->findOneBy([
            'user' => $user,
            'aid' => $aid
        ]);

        if ($favoriteAid) {
            $entityManager->remove($favoriteAid);
            $isFavorite = false;
        } else {
            $favoriteAid = new FavoriteAid();
            $favoriteAid->setUser($user);
            $favoriteAid->setAid($aid);
            $favoriteAid->setDateCreate(new \DateTime());
            $entityManager->persist($favoriteAid);
            $isFavorite = true;
        }

        $entityManager->flush();

        // Déterminer quel template utiliser
        $template = $request->query->get('display', 'default') === 'icon' 
            ? 'aid/aid/_favorite_button_icon.html.twig'
            : 'aid/aid/_favorite_button.html.twig';

        // Création du contenu du bouton
        $buttonHtml = $this->renderView($template, [
            'aid' => $aid,
            'isFavorite' => $isFavorite
        ]);

        // Retourne une réponse Turbo Stream
        return new Response(
            sprintf(
                '<turbo-stream action="replace" target="favorite-button-%s"><template>%s</template></turbo-stream>',
                $aid->getSlug(),
                $buttonHtml
            ),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html']
        );
    }
}