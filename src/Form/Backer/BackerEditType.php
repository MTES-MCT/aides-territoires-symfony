<?php

namespace App\Form\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use App\Form\Type\PerimeterAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints as Assert;

class BackerEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Nom du porteur',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir le nom du porteur.',
                    ]),
                ],
            ])
            ->add('isCorporate', ChoiceType::class, [
                'required' => true,
                'label' => 'Porteur d\'aides privé',
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'expanded' => true,
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Veuillez indiquer si le porteur est privé ou non.',
                    ]),
                ],
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
                'label' => 'Ajoutez le logo de votre structure',
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
                    Si l’on vous contacte régulièrement pour vous demander les mêmes
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
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir le périmètre',
                    ]),
                ],
            ])
            ->add('backerType', TextareaType::class, [
                'required' => false,
                'label' => 'Type de porteur',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10,
                    'placeholder' => 'Indiquez la nature juridique de votre structure : collectivité, établissement public (et le cas échéant votre tutelle), association, entreprise …'
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
                    'rows' => 10,
                    'placeholder' => 'Donnez ici des explications sur les modalités de saisine de vos équipes, votre fonctionnement (centralisé, déconcentré ou autre), l\'utilisation ou non de plateformes de dépôt de dossier : en bref tous les bons conseils pour une collectivité qui voudrait demander une aide !'
                ],
                'sanitize_html' => true,
            ])
            ->add('contact', TextareaType::class, [
                'required' => false,
                'label' => 'Contact',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10,
                    'placeholder' => 'Indiquez ici des coordonnées de contact, si possible génériques et non nominatives contact@nouvellestructuretest'
                ],
                'sanitize_html' => true,
            ])
            ->add('usefulLinks', TextareaType::class, [
                'required' => false,
                'label' => 'Liens utiles',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10,
                    'placeholder' => 'Les éventuels raccourcis vers des documents disponibles en ligne, sur votre site web ...'
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
