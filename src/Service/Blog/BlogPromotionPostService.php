<?php

namespace App\Service\Blog;

use App\Entity\Blog\BlogPromotionPost;
use Doctrine\Common\Collections\ArrayCollection;

class BlogPromotionPostService
{
    // Si les bloc promotion on des prér-requis,
    // ex : uniquement pour la catégogrie "voirie" il ne faut pas qu'elle soit affiché
    // si la recherche n'as pas de critère catégorie
    /**
     * Undocumented function
     *
     * @param array<int, BlogPromotionPost> $blogPromotionPosts
     * @param array<string, mixed>|null $aidParams
     * @return array<int, BlogPromotionPost>
     */
    public function handleRequires(array $blogPromotionPosts, array $aidParams = null): array // NOSONAR too complex
    {
        /** @var BlogPromotionPost $blogPromotionPost */
        foreach ($blogPromotionPosts as $key => $blogPromotionPost) {
            if (
                !$blogPromotionPost->getOrganizationTypes()->isEmpty()
                && (!isset($aidParams['organizationType']) || !$aidParams['organizationType'])
            ) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (
                (!$blogPromotionPost->getBackers()->isEmpty())
                && (
                    !isset($aidParams['backers'])
                    || !$aidParams['backers']
                    || count($aidParams['backers']) == 0
                )
            ) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (
                (!$blogPromotionPost->getCategories()->isEmpty())
                && (
                    !isset($aidParams['categories'])
                    || !$aidParams['categories']
                    || count($aidParams['categories']) == 0
                )
            ) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (
                (!$blogPromotionPost->getPrograms()->isEmpty())
                && (
                    !isset($aidParams['programs'])
                    || !$aidParams['programs']
                    || count($aidParams['programs']) == 0
                )
            ) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (
                !$blogPromotionPost->getKeywordReferences()->isEmpty()
                && (
                    !isset($aidParams['keyword'])
                    || !$aidParams['keyword']
                )
            ) {
                unset($blogPromotionPosts[$key]);
            }
        }

        return $blogPromotionPosts;
    }
}
