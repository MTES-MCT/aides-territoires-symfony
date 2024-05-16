<?php

namespace App\Controller\Admin\Reference;

use App\Controller\Admin\Aid\AidCrudController;
use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Filter\Reference\KeywordReferenceSuggestedAidFilter;
use App\Entity\Reference\KeywordReferenceSuggested;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\HttpFoundation\RedirectResponse;

class KeywordReferenceSuggestedCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return KeywordReferenceSuggested::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(KeywordReferenceSuggestedAidFilter::new('aidAuto', 'Aide'))
            ->add('keywordReference')
        ;
    }

    
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
        ->onlyOnIndex();

        yield AssociationField::new('keywordReference')
        ->formatValue(function ($value) {
            $keywords = [$value->getName()];
            foreach ($value->getKeywordReferences() as $keywordReference) {
                $keywords[] = $keywordReference->getName();
            }
            return implode(', ', $keywords);
        });
        
        yield AssociationField::new('aid')
        ->formatValue(function ($value, $entity) {
            return sprintf('<a href="%s">%s</a>',
                $this->adminUrlGenerator
                    ->setController(AidCrudController::class)
                    ->setAction('edit')
                    ->setEntityId($entity->getAid()->getId())
                    ->generateUrl(),
                $value
            );
        })
        ;
        yield NumberField::new('occurence');
    }

    public function configureActions(Actions $actions): Actions
    {
        // accepter
        $accept = Action::new('accept', 'Accepter', 'far fa-valid')
        ->linkToCrudAction('accept');

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $accept)
        ;
    }
    
    public function accept(AdminContext $context): RedirectResponse
    {
        /** @var KeywordReferenceSuggested $entity */
        $entity = $context->getEntity()->getInstance();
        
        $entity->getAid()->addKeywordReference($entity->getKeywordReference());

        // associe mot clé / aide
        $this->managerRegistry->getManager()->persist($entity->getAid());

        // supprime la suggestion
        $this->managerRegistry->getManager()->remove($entity);

        // sauvegarde
        $this->managerRegistry->getManager()->flush();

        // redirection sur la vue index
        return $this->redirect($context->getReferrer());
    }
}
