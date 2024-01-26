<?php

namespace App\Form\Contact;

use App\Entity\Contact\Contact;
use App\Entity\Contact\ContactMessage;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Votre prénom :',
                // 'sanitize_html' => true,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Votre nom :',
                // 'sanitize_html' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email :*',
                'required' => true,
                'help' => 'Par exemple : prenom.nom@domaine.fr',
                // 'sanitize_html' => true,
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Votre numéro de téléphone :',
                // 'sanitize_html' => true,
            ])
            ->add('structureAndFunction', TextType::class, [
                'label' => 'Votre structure et fonction :',
                'required' => true, 
                'attr' => [
                    'placeholder' => 'Exemple: Mairie de Château-Thierry / Chargé de mission habitat'
                ],
                // 'sanitize_html' => true,
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
                'label' => 'Sujet :*',
                'required' => true
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre question ou message *',
                'required' => true,
                'attr' => ['class' => 'fr-input', 'cols' => 40, 'rows' => 10],
                'sanitize_html' => true,
            ])
            ->add('captcha', CaptchaType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
