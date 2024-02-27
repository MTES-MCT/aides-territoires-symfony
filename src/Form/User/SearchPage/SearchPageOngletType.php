<?php

namespace App\Form\User\SearchPage;

use App\Entity\Page\Page;
use App\Entity\Search\SearchPage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchPageOngletType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Titre :'
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu :',
                'attr' => [
                    'class' => 'trumbowyg',
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
            ])
            ->add('delete', ButtonType::class, [
                'label_html' => true,
                'label' => 'Supprimer onglet',
                'attr' => [
                    'class' => 'btn-delete-collection-generic fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-delete-line'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
