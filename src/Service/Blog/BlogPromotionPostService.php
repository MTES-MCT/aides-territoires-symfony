<?php

namespace App\Service\Blog;

use App\Entity\Blog\BlogPromotionPost;

class BlogPromotionPostService
{
    // Si les bloc promotion on des prér-requis, ex : uniquement pour la catégogrie "voirie" il ne faut pas qu'elle soit affiché si la recherche n'as pas de critère catégorie
    public function handleRequires(array $blogPromotionPosts, array $aidParams = null) // NOSONAR too complex
    {
        /** @var BlogPromotionPost $blogPromotionPost */
        foreach ($blogPromotionPosts as $key => $blogPromotionPost) {
            if ($blogPromotionPost->getOrganizationTypes() && (!isset($aidParams['organizationType']) || $aidParams['organizationType'] === null)) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (($blogPromotionPost->getBackers() && !$blogPromotionPost->getBackers()->isEmpty()) && (!isset($aidParams['backers']) || $aidParams['backers'] === null || count($aidParams['backers']) == 0)) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (($blogPromotionPost->getCategories() && !$blogPromotionPost->getCategories()->isEmpty()) && (!isset($aidParams['categories']) || $aidParams['categories'] === null || count($aidParams['categories']) == 0)) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (($blogPromotionPost->getPrograms() && !$blogPromotionPost->getPrograms()->isEmpty()) && (!isset($aidParams['programs']) || $aidParams['programs'] === null || count($aidParams['programs']) == 0)) {
                unset($blogPromotionPosts[$key]);
                continue;
            }
            if (($blogPromotionPost->getKeywordReferences() && !$blogPromotionPost->getKeywordReferences()->isEmpty()) && (!isset($aidParams['keyword']) || $aidParams['keyword'] === null)) {
                unset($blogPromotionPosts[$key]);
            }
        }

        return $blogPromotionPosts;
    }
}
