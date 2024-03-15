<?php

namespace App\Form\Admin\Program;

use App\Entity\Page\Faq;
use App\Entity\Page\FaqCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FaqCategoryCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
                'help' => 'max 255 caractères'
            ])
            ->add('faqQuestionAnswsers', CollectionType::class, [
                'label' => 'Questions et réponses',
                'entry_type' => FaqQuestionAnswerCollectionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FaqCategory::class,
        ]);
    }
}
