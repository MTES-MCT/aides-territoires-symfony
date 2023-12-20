<?php

namespace App\Form\Project;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Project\Project;
use App\Form\Type\CheckboxMultipleSearchType;
use App\Form\Type\PerimeterAutocompleteType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectSearchType extends AbstractType
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
            'required' => false,
            'label' => 'Territoire du projet'
        ];
        if ($options['forcePerimeter'] !== false) {
            $perimeterParams['data'] = $options['forcePerimeter'];
        }


        $builder
            ->add('perimeter', PerimeterAutocompleteType::class, $perimeterParams)
            ->add('step', ChoiceType::class, [
                'required' => false,
                'label' => 'Avancement du projet',
                'choices' => $statuses
            ])
            ->add('contractLink', ChoiceType::class, [
                'required' => false,
                'label' => 'Appartenance à un plan',
                'choices' => $contactLinks
            ])
            // ->add('keywordSynonymlist', EntityType::class, [
            //     'required' => false,
            //     'label' => 'Types de projet ',
            //     'class' => KeywordSynonymlist::class,
            //     'choice_label' => 'name',
            //     'placeholder' => 'Sélectionnez un type de projet',
            //     'multiple' => true
            // ])
            ->add('keywordSynonymlistSearch', CheckboxMultipleSearchType::class, [
                'required' => false,
                'label' => false,
                'customChoices' => $choicesKeywordSynonymList,
                'displayerPlaceholder' => 'Sélectionnez un type de projet',
                'displayerLabel' => 'Types de projet'
            ])
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
