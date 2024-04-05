<?php

namespace App\Form\Admin\Program;

use App\Entity\Page\FaqCategory;
use App\Entity\Page\FaqQuestionAnswser;
use App\Entity\Program\Program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FaqQuestionAnswerCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => 'Question',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
                'help' => 'max 255 caractères'
            ])
            ->add('answer', TextareaType::class, [
                'label' => 'Réponse',
                'attr' => [
                    'class' => 'trumbowyg'
                ],
                'constraints' => [
                    new Length(['max' => 4294967295]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FaqQuestionAnswser::class,
        ]);
    }
}
