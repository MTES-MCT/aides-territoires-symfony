<?php

namespace App\Form\Contact;

use App\Entity\Contact\ContactMessage;
use App\Service\File\FileService;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    const SUBJECTS = [
        'contact_add' => 'Je souhaite communiquer sur Aides-territoires',
        'contact_com' => 'J’ai une question sur mon compte utilisateur',
        'contact_blog' => 'J’ai une question concernant le blog',
        'contact_api' => 'Je souhaite utiliser les données d’Aides-territoires / API',
        'contact_tech' => 'J\'ai un problème technique sur le site',
        'contact_fonds_vert' => 'J’ai une question sur le fonds vert',
        'contact_other' => 'Autres',
    ];

    public function __construct(
        private FileService $fileService
    )
    {   
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Votre prénom :',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre prénom.',
                    ]),
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Votre nom :',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre nom.',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email :',
                'required' => true,
                'help' => 'Par exemple : prenom.nom@domaine.fr',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre email.',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas une adresse email valide.',
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'required' => false,
                'label' => 'Votre numéro de téléphone :',
                'constraints' => [
                    new Length(max: 20)
                ]
            ])
            ->add('structureAndFunction', TextType::class, [
                'label' => 'Votre structure et fonction :',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Exemple: Mairie de Château-Thierry / Chargé de mission habitat'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre structure et fonction.',
                    ]),
                ]
            ])
            ->add('subject', ChoiceType::class, [
                'choices' => [
                    'Je souhaite communiquer sur Aides-territoires' => 'contact_add',
                    'J’ai une question sur mon compte utilisateur' => 'contact_com',
                    'J’ai une question concernant le blog' => 'contact_blog',
                    'Je souhaite utiliser les données d’Aides-territoires / API' => 'contact_api',
                    'J\'ai un problème technique sur le site' => 'contact_tech',
                    'J’ai une question sur le fonds vert' => 'contact_fonds_vert',
                    'Autres' => 'contact_other',
                ],
                'placeholder' => '---',
                'label' => 'Sujet :',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un sujet.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre question ou message :',
                'required' => true,
                'attr' => ['class' => 'fr-input', 'cols' => 40, 'rows' => 10],
                'sanitize_html' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir un message.',
                    ]),
                ],
            ])
        ;

        if ($this->fileService->getEnvironment() !== FileService::ENV_TEST) {
            $builder->add('captcha', CaptchaType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
