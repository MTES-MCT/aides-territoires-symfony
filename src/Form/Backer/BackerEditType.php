<?php

namespace App\Form\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerGroup;
use App\Entity\Perimeter\Perimeter;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Url;

class BackerEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom du porteur'
            ])
            ->add('isCorporate', ChoiceType::class, [
                'required' => true,
                'label' => 'Porteur d\'aides privé',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'expanded' => true
            ])
            ->add('externalLink', TextType::class, [
                'required' => false,
                'label' => 'Lien externe',
                'help' => 'L’URL externe vers laquelle renvoie un clic sur le logo du porteur',
                'constraints' => [
                    new Url()
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Ajouter une photo représentant votre porteur d\'aides',
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
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Description du porteur',
                'attr' => [
                    'placeholder' => 'Si vous avez un descriptif, n’hésitez pas à le copier ici.
                    Essayez de compléter le descriptif avec le maximum d’informations.
                    Si l’on vous contacte régulièrement pour vous demander les mêmes "
                    informations, essayez de donner des éléments de réponses dans cet espace.',
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('perimeter', PerimeterAutocompleteType::class, [
                'required' => true,
                'label' => 'Zone géographique couverte par le porteur',
                'help' => 'La zone géographique sur laquelle le porteur fourni des aides.<br />
                Exemples de zones valides :
                <ul>
                    <li>France</li>
                    <li>Bretagne (Région)</li>
                    <li>Métropole du Grand Paris (EPCI)</li>
                    <li>Outre-mer</li>
                    <li>Wallis et Futuna</li>
                    <li>Massif Central</li>
                </ul>
                ',
                'help_html' => true,
                'placeholder' => 'Tapez les premiers caractères',
                'class' => Perimeter::class,
            ])
            ->add('backerType', TextareaType::class, [
                'required' => false,
                'label' => 'Type de porteur',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('projectsExamples', TextareaType::class, [
                'required' => false,
                'label' => 'Exemples de projets accompagnés par le porteur',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('internalOperation', TextareaType::class, [
                'required' => false,
                'label' => 'Mode de fonctionnement interne pour obtenir une aide',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('contact', TextareaType::class, [
                'required' => false,
                'label' => 'Contact',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('usefulLinks', TextareaType::class, [
                'required' => false,
                'label' => 'Liens utiles',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Backer::class,
        ]);
    }
}
