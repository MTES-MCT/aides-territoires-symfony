<?php

namespace App\Form\Project;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Project\Project;
use App\Form\Type\PerimeterAutocompleteType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class ProjectPublicSearchType extends AbstractType
{
    public function  __construct(
        protected ManagerRegistry $managerRegistry,
        protected RouterInterface $routerInterface
    ) {}

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
            ['name' => 'ASC']
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


        $nameParams = [
            'required' => false,
            'label' => "Types de projet",
            'autocomplete' => true,
            'autocomplete_url' => $this->routerInterface->generate('app_project_reference_ajax_ux_autocomplete'),
            'tom_select_options' => [
                'create' => true,
                'createOnBlur' => true,
                'maxItems' => 1,
                'delimiter' => '$%§'
            ],
        ];
        if ($options['forceName'] !== false) {
            $nameParams['data'] = $options['forceName'];
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
            ->add('name', TextType::class, $nameParams)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'forcePerimeter' => false,
            'forceName' => false,
            'attr' => [
                'data-controller' => 'custom-autocomplete'
            ]
        ]);
    }
}
