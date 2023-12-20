<?php

namespace App\Form\User;

use App\Entity\User\ApiTokenAsk;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Url;

class ApiTokenAskCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Description de votre projet',
                'attr' => [
                    'cols' => 40,
                    'rows' => 10,
                    'placeholder' => 'Merci de décrire précisément l’usage que vous allez avoir de l’API Aides-territoires.'
                ]
            ])
            ->add('urlService', TextType::class, [
                'required' => false,
                'label' => 'URL de votre service',
                'constraints' => [
                    new Url()
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiTokenAsk::class,
        ]);
    }
}
