<?php

namespace App\Form\Organization;

use App\Entity\Organization\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationDatasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inhabitantsNumber', NumberType::class, [
                'label' => 'Habitants :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('votersNumber', NumberType::class, [
                'label' => 'Votants :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('corporatesNumber', NumberType::class, [
                'label' => 'Entreprise :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('shopsNumber', NumberType::class, [
                'label' => 'Commerce :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('associationsNumber', NumberType::class, [
                'label' => 'Association :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('municipalRoads', NumberType::class, [
                'label' => 'Routes communales (kms) :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('departmentalRoads', NumberType::class, [
                'label' => 'Routes départementales (kms) :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('tramRoads', NumberType::class, [
                'label' => 'Tramway (kms) :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('lamppostNumber', NumberType::class, [
                'label' => 'Lampadaires :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('bridgeNumber', NumberType::class, [
                'label' => 'Ponts :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('libraryNumber', NumberType::class, [
                'label' => 'Bibliothèque :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('medialibraryNumber', NumberType::class, [
                'label' => 'Médiathèque :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('theaterNumber', NumberType::class, [
                'label' => 'Théâtre :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('cinemaNumber', NumberType::class, [
                'label' => 'Cinéma :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('museumNumber', NumberType::class, [
                'label' => 'Musée :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('nurseryNumber', NumberType::class, [
                'label' => 'Crèche :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('kindergartenNumber', NumberType::class, [
                'label' => 'École maternelle :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('primarySchoolNumber', NumberType::class, [
                'label' => 'École élémentaire :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('recCenterNumber', NumberType::class, [
                'label' => 'Centre de loisirs :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('middleSchoolNumber', NumberType::class, [
                'label' => 'Collège :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('highSchoolNumber', NumberType::class, [
                'label' => 'Lycée :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('universityNumber', NumberType::class, [
                'label' => 'Université :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('tennisCourtNumber', NumberType::class, [
                'label' => 'Court de tennis :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('footballFieldNumber', NumberType::class, [
                'label' => 'Terrain de football :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('runningTrackNumber', NumberType::class, [
                'label' => 'Piste d\'athlétismes :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('otherOutsideStructureNumber', NumberType::class, [
                'label' => 'Structure extérieure autre :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('coveredSportingComplexNumber', NumberType::class, [
                'label' => 'Complexe sportif couvert :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('swimmingPoolNumber', NumberType::class, [
                'label' => 'Piscine :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('placeOfWorshipNumber', NumberType::class, [
                'label' => 'Lieux de cultes :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('cemeteryNumber', NumberType::class, [
                'label' => 'Cimetières :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('protectedMonumentNumber', NumberType::class, [
                'label' => 'Monument classé :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ])
            ->add('forestNumber', NumberType::class, [
                'label' => 'Forêt (en hactares) :',
                'required' => false,
                'attr' => [
                    'readonly' => $options['is_readonly'] ? 'readonly' : false
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
            'is_readonly' => false
        ]);
    }
}