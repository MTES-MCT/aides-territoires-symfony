<?php

namespace App\Form\Project;

use App\Validator\AidValidUrl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AidSuggestedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('aidUrl', TextType::class, [
                'required' => true,
                'label' => 'URL de l’aide que vous souhaitez suggérer',
                'help' => 'Coller ici l’URL de l’aide que vous souhaitez suggérer pour le projet.',
                'constraints' => [
                    new AidValidUrl()
                ],
                'sanitize_html' => true,
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'label' => '',
                'data' => 'Bonjour, je vous recommande cette aide qui semble convenir à votre projet.',
                'attr' => [
                    'cols' => 40,
                    'rows' => 10
                ],
                'sanitize_html' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un message.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
