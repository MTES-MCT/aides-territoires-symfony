<?php

namespace App\Form\Project;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Project\Project;
use App\Form\Type\KeywordSynonymlistAutocompleteField;
use App\Form\Type\PerimeterCityAutocompleteType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectValidatedSearchType extends AbstractType
{
    public function  __construct(
        protected ManagerRegistry $managerRegistry
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $statuses = [];
        foreach (Project::PROJECT_STEPS as $status) {
            $statuses[$status['name']] = $status['slug'];
        }

        $contactLinks = [];
        foreach (Project::CONTRACT_LINK as $contactLink) {
            $contactLinks[$contactLink['name']] = $contactLink['slug'];
        }

        $keywordSynonymlists = $this->managerRegistry->getRepository(KeywordSynonymlist::class)->findBy(
            [],
            ['name'=>'ASC']
        );
        $choicesKeywordSynonymList = [];
        foreach ($keywordSynonymlists as $keywordSynonymlist) {
            $choicesKeywordSynonymList[$keywordSynonymlist->getName()] = $keywordSynonymlist->getId();
        }

        // Perimeter params
        $perimeterParams = [
            'required' => true,
            'label' => 'Territoire du projet',
        ];

        

        if ($options['forcePerimeter'] !== false) {
            $perimeterParams['data'] = $options['forcePerimeter'];
        }

        // keyword params
        $keywordParams = [
            'required' => false,
            'label' => 'Mot-clés',
            'attr' => [
                'placeholder' => 'Ex: rénovation énergétique, vélo, tiers lieu, etc.'
            ]
        ];


        $builder
            ->add('project_perimeter', PerimeterCityAutocompleteType::class, $perimeterParams)
            ->add('text', KeywordSynonymlistAutocompleteField::class,  $keywordParams)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'forcePerimeter' => false
        ]);
    }
}
