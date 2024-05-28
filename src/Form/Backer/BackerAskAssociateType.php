<?php

namespace App\Form\Backer;

use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerAskAssociate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BackerAskAssociateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('backer', EntityType::class, [
                'label' => 'Porteur demandé :',
                'class' => Backer::class,
                'choice_label' => 'name',
                'autocomplete' => true,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez choisir un porteur du porteur.',
                    ]),
                ],
                'placeholder' => 'Saisir le nom du porteur',
                
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Message :',
                'sanitize_html' => true,
                'attr' => [
                    'placeholder' => 'Vous pouvez justifer votre demande'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BackerAskAssociate::class,
        ]);
    }
}
