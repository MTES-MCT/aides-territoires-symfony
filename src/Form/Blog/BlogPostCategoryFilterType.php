<?php

namespace App\Form\Blog;

use App\Entity\Blog\BlogPostCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BlogPostCategoryFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('blogPostCategory', EntityType::class, [
                'required' => true,
                'label' => false,
                'class' => BlogPostCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Filtrer les articles par catégorie',
                'attr' => [
                    'title' => 'Filtrer les articles par catégorie - La sélection recharge la page'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir une catégorie.',
                    ]),
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
