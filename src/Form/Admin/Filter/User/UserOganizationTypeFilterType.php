<?php

namespace App\Form\Admin\Filter\User;

use App\Entity\Organization\OrganizationType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserOganizationTypeFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => OrganizationType::class,
            'choice_label' => 'name',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('ot')
                    ->orderBy('ot.name', 'ASC');
            },
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
