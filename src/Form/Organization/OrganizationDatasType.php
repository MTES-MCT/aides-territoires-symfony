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
        ->add('inhabitantsNumber',NumberType::class,['label_html' => true,'label'=>'Habitants&nbsp;:','required'=>false,'help' => ''])
        ->add('votersNumber',NumberType::class,['label_html' => true,'label'=>'Votants&nbsp;:','required'=>false,'help' => ''])
        
        ->add('corporatesNumber',NumberType::class,['label_html' => true,'label'=>'Entreprise&nbsp;:','required'=>false,'help' => ''])
        ->add('shopsNumber',NumberType::class,['label_html' => true,'label'=>'Commerce&nbsp;:','required'=>false,'help' => ''])
        ->add('associationsNumber',NumberType::class,['label_html' => true,'label'=>'Association&nbsp;:','required'=>false,'help' => ''])

        ->add('municipalRoads',NumberType::class,['label_html' => true,'label'=>'Routes communales (kms)&nbsp;:','required'=>false,'help' => ''])
        ->add('departmentalRoads',NumberType::class,['label_html' => true,'label'=>'Routes départementales (kms)&nbsp;:','required'=>false,'help' => ''])
        ->add('tramRoads',NumberType::class,['label_html' => true,'label'=>'Tramway (kms)&nbsp;:','required'=>false,'help' => ''])
        ->add('lamppostNumber',NumberType::class,['label_html' => true,'label'=>'Lampadaires&nbsp;:','required'=>false,'help' => ''])
        ->add('bridgeNumber',NumberType::class,['label_html' => true,'label'=>'Ponts&nbsp;:','required'=>false,'help' => ''])

        ->add('libraryNumber',NumberType::class,['label_html' => true,'label'=>'Bibliothèque&nbsp;:','required'=>false,'help' => ''])
        
        ->add('medialibraryNumber',NumberType::class,['label_html' => true,'label'=>'Médiathèque&nbsp;:','required'=>false,'help' => ''])
        ->add('theaterNumber',NumberType::class,['label_html' => true,'label'=>'Théâtre&nbsp;:','required'=>false,'help' => ''])
        ->add('cinemaNumber',NumberType::class,['label_html' => true,'label'=>'Cinéma&nbsp;:','required'=>false,'help' => ''])
        ->add('museumNumber',NumberType::class,['label_html' => true,'label'=>'Musée&nbsp;:','required'=>false,'help' => ''])

        ->add('nurseryNumber',NumberType::class,['label_html' => true,'label'=>'Crèche&nbsp;:','required'=>false,'help' => ''])
        ->add('kindergartenNumber',NumberType::class,['label_html' => true,'label'=>'École maternelle&nbsp;:','required'=>false,'help' => ''])
        ->add('primarySchoolNumber',NumberType::class,['label_html' => true,'label'=>'École élémentaire&nbsp;:','required'=>false,'help' => ''])
        ->add('recCenterNumber',NumberType::class,['label_html' => true,'label'=>'Centre de loisirs&nbsp;:','required'=>false,'help' => ''])
        ->add('middleSchoolNumber',NumberType::class,['label_html' => true,'label'=>'Collège&nbsp;:','required'=>false,'help' => ''])
        ->add('highSchoolNumber',NumberType::class,['label_html' => true,'label'=>'Lycée&nbsp;:','required'=>false,'help' => ''])
        ->add('universityNumber',NumberType::class,['label_html' => true,'label'=>'Université&nbsp;:','required'=>false,'help' => ''])
        
        ->add('tennisCourtNumber',NumberType::class,['label_html' => true,'label'=>'Court de tennis&nbsp;:','required'=>false,'help' => ''])
        ->add('footballFieldNumber',NumberType::class,['label_html' => true,'label'=>'Terrain de football&nbsp;:','required'=>false,'help' => ''])
        ->add('runningTrackNumber',NumberType::class,['label_html' => true,'label'=>'Piste d\'athlétismes&nbsp;:','required'=>false,'help' => ''])
        ->add('otherOutsideStructureNumber',NumberType::class,['label_html' => true,'label'=>'Structure extérieure autre&nbsp;:','required'=>false,'help' => ''])
        ->add('coveredSportingComplexNumber',NumberType::class,['label_html' => true,'label'=>'Complexe sportif couvert&nbsp;:','required'=>false,'help' => ''])
        ->add('swimmingPoolNumber',NumberType::class,['label_html' => true,'label'=>'Piscine&nbsp;:','required'=>false,'help' => ''])
        
        ->add('placeOfWorshipNumber',NumberType::class,['label_html' => true,'label'=>'Lieux de cultes&nbsp;:','required'=>false,'help' => ''])
        ->add('cemeteryNumber',NumberType::class,['label_html' => true,'label'=>'Cimetières&nbsp;:','required'=>false,'help' => ''])
        
        ->add('protectedMonumentNumber',NumberType::class,['label_html' => true,'label'=>'Monument classé&nbsp;:','required'=>false,'help' => ''])
        ->add('forestNumber',NumberType::class,['label_html' => true,'label'=>'Forêt (en hactares)&nbsp;:','required'=>false,'help' => ''])
        
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
        ]);
    }
}
