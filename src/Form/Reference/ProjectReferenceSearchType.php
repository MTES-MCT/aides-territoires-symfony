<?php

namespace App\Form\Reference;

use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Form\Type\PerimeterCityAutocompleteType;
use App\Service\User\UserService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectReferenceSearchType extends AbstractType
{
    public function __construct(
        private UserService $userService,
        private RouterInterface $routerInterface
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $this->userService->getUserLogged();

        // organizationType params
        $organizationTypeParams = [
            'required' => true,
            'label' => 'Vous cherchez pour…',
            'class' => OrganizationType::class,
            'choice_label' => 'name',
            'choice_value' => 'slug',
            'placeholder' => 'Tous types de structures',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('ot')
                    ->andWhere('ot.slug = :slugCommune')
                    ->setParameter('slugCommune', OrganizationType::SLUG_COMMUNE)
                    ->orderBy('ot.name', 'ASC');
            },
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Veuillez saisir un type de structure.',
                ]),
            ],
        ];
        if ($options['forceOrganizationType'] !== false) {
            $organizationTypeParams['data'] = $options['forceOrganizationType'];
        } else {
            $organizationTypeParams['data'] = ($user && $user->getDefaultOrganization() && $user->getDefaultOrganization()->getOrganizationType()) ? $user->getDefaultOrganization()->getOrganizationType() : null;
        }

        // Perimeter params
        $perimeterParams = [
            'required' => false,
            'label' => 'Territoire du projet'
        ];
        if ($options['forcePerimeter'] !== false) {
            $perimeterParams['data'] = $options['forcePerimeter'];
        } else {
            $perimeterParams['data'] =
                (
                    $user
                    && $user->getDefaultOrganization()
                    && $user->getDefaultOrganization()->getPerimeter()
                    && $user->getDefaultOrganization()->getPerimeter()->getScale() == Perimeter::SCALE_COMMUNE
                )
                ? $user->getDefaultOrganization()->getPerimeter()
                : null;
        }

        $nameParams = [
            'required' => true,
            'label' => "Nom du projet",
            'autocomplete' => true,
            'autocomplete_url' => $this->routerInterface->generate('app_project_reference_ajax_ux_autocomplete'),
            'tom_select_options' => [
                'create' => true,
                'createOnBlur' => true,
                'maxItems' => 1,
                'delimiter' => '$%§'
            ],
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Veuillez choisir le nom du projet.',
                ]),
            ],
        ];
        if ($options['forceName'] !== false) {
            $nameParams['data'] = $options['forceName'];
        }

        $builder
            ->add('organizationType', EntityType::class, $organizationTypeParams)
            ->add('perimeter', PerimeterCityAutocompleteType::class, $perimeterParams)
            ->add('name', TextType::class, $nameParams)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'forcePerimeter' => false,
            'forceOrganizationType' => false,
            'forceName' => false,
            'attr' => [
                'data-controller' => 'custom-autocomplete'
            ]
        ]);
    }
}
