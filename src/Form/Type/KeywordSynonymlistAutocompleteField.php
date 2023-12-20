<?php

namespace App\Form\Type;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class KeywordSynonymlistAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => KeywordSynonymlist::class,
            'placeholder' => 'Ex: Rénovation énergétique',
            'choice_label' => 'name',

            'query_builder' => function (KeywordSynonymlistRepository $keywordSynonymlistRepository) {
                return $keywordSynonymlistRepository->createQueryBuilder('keywordSynonymlist');
            },

            'multiple' => false,
            'data_class' => null,
            'preload' => true,

            // 'max_results' => 3
            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
