<?php

namespace App\Form\Admin\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    private string $blockPrefix = '';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (isset($options['block_prefix'])) {
            $this->blockPrefix = $options['block_prefix'];
        }

        $builder
            ->add('dateMin', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateMax', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;

        // dates par défaut
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $dateMin = $form->get('dateMin')->getData();
            $dateMax = $form->get('dateMax')->getData();

            if (!$dateMin) {
                $dateMin = new \DateTime('-1 month');
                $form->get('dateMin')->setData($dateMin);
            }
            if (!$dateMax) {
                $dateMax = new \DateTime();
                $form->get('dateMax')->setData($dateMax);
            }
        });
    }

    public function getBlockPrefix()
    {
        return $this->blockPrefix == '' ? 'date_range' : $this->blockPrefix;
    }
}
