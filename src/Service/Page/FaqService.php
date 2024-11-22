<?php

namespace App\Service\Page;

use App\Entity\Page\Faq;

class FaqService
{
    public function getLatestUpdateTime(Faq $faq): ?\DateTimeInterface
    {
        $latestUpdateTime = $faq->getTimeUpdate() ?? $faq->getTimeCreate() ?? null;
        foreach ($faq->getFaqCategories() as $category) {
            if ($category->getTimeUpdate() > $latestUpdateTime) {
                $latestUpdateTime = $category->getTimeUpdate();
            }
            foreach ($category->getFaqQuestionAnswsers() as $question) {
                if ($question->getTimeUpdate() > $latestUpdateTime) {
                    $latestUpdateTime = $question->getTimeUpdate();
                }
            }
        }

        return $latestUpdateTime;
    }
}
