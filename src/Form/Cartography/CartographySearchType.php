<?php

namespace App\Form\Cartography;

use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\BackerCategory;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Service\User\UserService;
use App\Form\Type\CheckboxMultipleSearchType;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartographySearchType extends AbstractType
{
    public function  __construct(
        private UserService $userService,
        protected ManagerRegistry $managerRegistry
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categoryThemes = $this->managerRegistry->getRepository(CategoryTheme::class)->findBy(
            [],
            [
                'name' => 'ASC'
            ]
        );
        $categoriesByTheme = [];
        foreach ($categoryThemes as $categoryTheme) {
            if (!isset($categoriesByTheme[$categoryTheme->getName()])) {
                $categoriesByTheme[$categoryTheme->getName()] = [];
            }
            foreach ($categoryTheme->getCategories() as $category) {
                $categoriesByTheme[$categoryTheme->getName()][] = [$category->getName() => $category->getId()];
            }
        }
        
        /** @var User $user */
        $user = $this->userService->getUserLogged();

        $departementParams = [
            'required' => true,
            'label' => 'Département',
            'class' => Perimeter::class,
            'choice_label' => function($entity){
                return $entity->getCode().' - '.$entity->getName();
            },
            'query_builder' => function (PerimeterRepository $perimeterRepository) {
                return $perimeterRepository->getQueryBuilder([
                    'scale' => Perimeter::SCALE_COUNTY,
                    'orderby' => ['p.code' => 'ASC']
                ]);
            },
        ];

        if($options['forceDepartement']!==false){
            $departementParams['data'] = $options['forceDepartement'];
        }

        $builder
            ->add('departement', EntityType::class,$departementParams)
            ->add('organizationType', EntityType::class, [
                'required' => false,
                'label' => 'Public bénéficiaire',
                'class' => OrganizationType::class,
                'choice_label' => 'name',
                'placeholder' => 'Toutes les structures',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->orderBy('o.name', 'ASC');
                },
            ])
            ->add('aidTypeGroup', EntityType::class, [
                'required' => false,
                'label' => 'Type d’aide',
                'class' => AidTypeGroup::class,
                'choice_label' => function($entity){
                    return $entity->getName().' par type';
                },
                'placeholder' => 'Toutes les aides',
            ])
            ->add('categorysearch', CheckboxMultipleSearchType::class, [
                'customChoices' => $categoriesByTheme,
                'displayerPlaceholder' => 'Toutes les sous-thématiques',
                'displayerLabel' => 'Thématiques',
                'label' => false
            ])
            ->add('scaleGroup', ChoiceType::class, [
                'choices' => [
                    'Porteurs locaux uniquement' =>  Perimeter::SLUG_LOCAL_GROUP,
                    'Porteurs nationaux uniquement' => Perimeter::SLUG_NATIONAL_GROUP,
                ],
                'placeholder' => 'Toutes les échelles', 
                'label' => 'Échelle d\'intervention', 
                'required'  => false
            ])
            ->add('backerCategory', EntityType::class, [
                'required' => false,
                'label' => 'Catégorie des porteurs',
                'class' => BackerCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Toutes les catégories',
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'forceDepartement'=>false
        ]);
    }
}
