<?php

namespace App\Form\Project;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Service\Image\ImageService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProjectEditType extends AbstractType
{
    public function __construct(
        protected ImageService $imageService
    ) {  
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label'=>'Nom du projet :*',
            'required'=>false,
            'help' => 'Donnez un nom explicite : préférez \'végétalisation du quartier des coteaux\' à \'quartier des coteaux\'',
            'sanitize_html' => true,
        ])
        // ->add('projectTypesSuggestion', EntityType::class, [
        //     'required' => false,
        //     'label' => false,
        //     'placeholder' => 'Saisissez une thématique',
        //     'class' => KeywordSynonymlist::class,
        //     'choice_label' => 'name',
        //     'choice_value' => 'slug',
        //     'query_builder' => function (EntityRepository $er) {
        //         return $er->createQueryBuilder('o')
        //             ->orderBy('o.name', 'ASC');
        //     },
        //     'autocomplete' => true,
        // ])
        ->add('projectReference', EntityType::class, [
            'required' => false,
            'label' => false,
            'placeholder' => 'Saisissez un projet référent',
            'class' => ProjectReference::class,
            'choice_label' => 'name',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('o')
                    ->orderBy('o.name', 'ASC');
            },
            'autocomplete' => true,
        ])
        ->add('referentNotFound', CheckboxType::class, [
            'required' => false,
            'label' => 'Je n\'ai pas trouvé de projet référent dans la liste',
        ])
        ->add('isPublic', CheckboxType::class, [
            'required' => false,
            'label' => 'Je souhaite rendre ce projet public sur Aides-territoires',
        ])
        ->add('step', ChoiceType::class, [
            'choices' => array_column(Project::PROJECT_STEPS, 'slug','name' ),
            'label' => 'État d’avancement du projet :', 
            'placeholder' => 'À quel stade est ce projet ?',
            'required' => true,                
        ])
        ->add('contract_link', ChoiceType::class, [
            'choices' => array_column(Project::CONTRACT_LINK, 'slug','name' ),
            'label' => 'Ce projet appartient-il à un programme ?', 
            'placeholder' => 'Si oui, faites votre choix...',
            'required' => false,                
        ])
        ->add('description', TextareaType::class, [
            'required' => true,
            'label' => 'Description complète de l’aide et de ses objectif :',
            'help' => 'Cette description sera utilisée dans l\'export du projet mais aussi dans le cas où vous le rendiez public',
            'attr' => [
                'placeholder' => 'Si vous avez un descriptif, n’hésitez pas à le copier ici. Essayez de compléter le descriptif avec le maximum d’informations. Si l’on vous contacte régulièrement pour vous demander les mêmes informations, essayez de donner des éléments de réponses dans cet espace.',
                'class' => 'trumbowyg',
                'cols' => 40,
                'rows' => 10
            ],
            'sanitize_html' => true,
        ])
        ->add('private_description', TextareaType::class, [
            'required' => false,
            'label' => 'Notes internes de votre projet',
            'help' => 'Ces informations restent internes à votre organisation même si vous rendez votre projet public.',
            'attr' => [
                'placeholder' => 'Information réservée à vos collaborateurs et à vous-même.',
                'class' => 'trumbowyg',
                'cols' => 40,
                'rows' => 10
            ],
            'sanitize_html' => true,
        ])
        ->add('imageUploadedFile', FileType::class, [
            'label' => 'Ajouter une photo représentant votre projet',
            'help' => 'Taille maximale : 10 Mio. Formats supportés : jpeg, jpg, png',
            'required' => false, 
            'constraints' => [
                new File([
                    'maxSize' => '10M', // Limite la taille à 10 Mo
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Veuillez télécharger un fichier JPG ou PNG valide.',
                ]),
            ],
        ])

        ->addEventListener(
            FormEvents::SUBMIT,
            [$this, 'onSubmit']
        )
        ;
    }

    public function onSubmit(FormEvent $event): void
    {
        $projectReference = $event->getForm()->get('projectReference')->getData();
        $referentNotFound = $event->getForm()->get('referentNotFound')->getData();

        if (!$projectReference && !$referentNotFound) {
            $event->getForm()->get('projectReference')->addError(new FormError('Veuillez choisir un projet référent ou cocher la case "Je n\'ai pas trouvé de projet référent dans la liste"'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
