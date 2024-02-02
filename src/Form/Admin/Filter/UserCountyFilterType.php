<?php

namespace App\Form\Admin\Filter;

use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCountyFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Perimeter::class,
            'choice_label' => 'name',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                ->andWhere('p.scale = :scaleCounty')
                ->setParameter('scaleCounty', Perimeter::SCALE_COUNTY)
                ->orderBy('p.name', 'ASC');
            },
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}