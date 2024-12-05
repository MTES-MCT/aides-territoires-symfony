<?php

namespace App\Form\Program;

use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountySelectType extends AbstractType
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PerimeterRepository $perimeterRepository */
        $perimeterRepository = $this->managerRegistry->getRepository(Perimeter::class);
        $counties = $perimeterRepository->findCounties();
        $countyChoices = [];
        foreach ($counties as $county) {
            $countyChoices[$county->getCode() . ' - ' . $county->getName()] = $county->getCode();
        }

        $builder
            ->add('county', ChoiceType::class, [
                'required' => false,
                'label' => 'Choisissez un département',
                'placeholder' => 'Choisissez un département',
                'choices' => $countyChoices,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
