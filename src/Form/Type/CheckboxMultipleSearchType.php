<?php

namespace App\Form\Type;

use App\Entity\Category\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxMultipleSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $customChoicesParams = [
            'label' => false,
            'choices' => $options['customChoices'] ?? [],
            'expanded' => true,
            'multiple' => true
        ];
        if (is_array($options['customChoicesData'])) {
            $datas = [];
            foreach ($options['customChoicesData'] as $option) {
                $datas[] = $option->getId();
            }
            $customChoicesParams['data'] = $datas;
        }

        $builder
            ->add('customChoices', ChoiceType::class, $customChoicesParams)
            ->add('autocomplete', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'c-autocomplete',
                    'autocomplete' => 'off'
                ],
                'data' => null
            ])
            ->add('displayer', TextType::class, [
                'required' => false,
                'label' => $options['displayerLabel'],
                'help' => $options['displayerHelp'],
                'attr' => [
                    'readonly' => true,
                    'placeholder' => $options['displayerPlaceholder'],
                    'class' => 'fr-select c-displayer',
                    'autocomplete' => 'off'
                ]
            ])
        ;

        if ($options['hideAutocomplete']) {
            $builder
                ->remove('autocomplete')
                ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // this defines the available options and their default values when
        // they are not configured explicitly when using the form type
        $resolver->setDefaults([
            'customChoices' => [],
            'displayerPlaceholder' => false,
            'displayerLabel' => false,
            'displayerHelp' => null,
            'customChoicesData' => false,
            'hideAutocomplete' => false
        ]);
    }
}
