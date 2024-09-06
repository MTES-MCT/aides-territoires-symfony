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
            ])
            ->add('votersNumber', NumberType::class, [
                'label' => 'Votants :',
                'required' => false,
            ])
            ->add('corporatesNumber', NumberType::class, [
                'label' => 'Entreprise :',
                'required' => false,
            ])
            ->add('shopsNumber', NumberType::class, [
                'label' => 'Commerce :',
                'required' => false,
            ])
            ->add('associationsNumber', NumberType::class, [
                'label' => 'Association :',
                'required' => false,
            ])
            ->add('municipalRoads', NumberType::class, [
                'label' => 'Routes communales (kms) :',
                'required' => false,
            ])
            ->add('departmentalRoads', NumberType::class, [
                'label' => 'Routes départementales (kms) :',
                'required' => false,
            ])
            ->add('tramRoads', NumberType::class, [
                'label' => 'Tramway (kms) :',
                'required' => false,
            ])
            ->add('lamppostNumber', NumberType::class, [
                'label' => 'Lampadaires :',
                'required' => false,
            ])
            ->add('bridgeNumber', NumberType::class, [
                'label' => 'Ponts :',
                'required' => false,
            ])
            ->add('libraryNumber', NumberType::class, [
                'label' => 'Bibliothèque :',
                'required' => false,
            ])
            ->add('medialibraryNumber', NumberType::class, [
                'label' => 'Médiathèque :',
                'required' => false,
            ])
            ->add('theaterNumber', NumberType::class, [
                'label' => 'Théâtre :',
                'required' => false,
            ])
            ->add('cinemaNumber', NumberType::class, [
                'label' => 'Cinéma :',
                'required' => false,
            ])
            ->add('museumNumber', NumberType::class, [
                'label' => 'Musée :',
                'required' => false,
            ])
            ->add('nurseryNumber', NumberType::class, [
                'label' => 'Crèche :',
                'required' => false,
            ])
            ->add('kindergartenNumber', NumberType::class, [
                'label' => 'École maternelle :',
                'required' => false,
            ])
            ->add('primarySchoolNumber', NumberType::class, [
                'label' => 'École élémentaire :',
                'required' => false,
            ])
            ->add('recCenterNumber', NumberType::class, [
                'label' => 'Centre de loisirs :',
                'required' => false,
            ])
            ->add('middleSchoolNumber', NumberType::class, [
                'label' => 'Collège :',
                'required' => false,
            ])
            ->add('highSchoolNumber', NumberType::class, [
                'label' => 'Lycée :',
                'required' => false,
            ])
            ->add('universityNumber', NumberType::class, [
                'label' => 'Université :',
                'required' => false,
            ])
            ->add('tennisCourtNumber', NumberType::class, [
                'label' => 'Court de tennis :',
                'required' => false,
            ])
            ->add('footballFieldNumber', NumberType::class, [
                'label' => 'Terrain de football :',
                'required' => false,
            ])
            ->add('runningTrackNumber', NumberType::class, [
                'label' => 'Piste d\'athlétismes :',
                'required' => false,
            ])
            ->add('otherOutsideStructureNumber', NumberType::class, [
                'label' => 'Structure extérieure autre :',
                'required' => false,
            ])
            ->add('coveredSportingComplexNumber', NumberType::class, [
                'label' => 'Complexe sportif couvert :',
                'required' => false,
            ])
            ->add('swimmingPoolNumber', NumberType::class, [
                'label' => 'Piscine :',
                'required' => false,
            ])
            ->add('placeOfWorshipNumber', NumberType::class, [
                'label' => 'Lieux de cultes :',
                'required' => false,
            ])
            ->add('cemeteryNumber', NumberType::class, [
                'label' => 'Cimetières :',
                'required' => false,
            ])
            ->add('protectedMonumentNumber', NumberType::class, [
                'label' => 'Monument classé :',
                'required' => false,
            ])
            ->add('forestNumber', NumberType::class, [
                'label' => 'Forêt (en hectares) :',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
        ]);
    }
}
