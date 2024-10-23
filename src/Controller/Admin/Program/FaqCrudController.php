<?php

namespace App\Controller\Admin\Program;

use App\Controller\Admin\AtCrudController;
use App\Entity\Page\Faq;
use App\Entity\Page\FaqCategory;
use App\Entity\Page\FaqQuestionAnswser;
use App\Form\Admin\Program\FaqCategoryCollectionType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Faq::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom')
            ->setHelp('Utilisé uniquement pour l\'association dans l\'administration.');
        yield AssociationField::new('pageTab', 'Onglet lié');
        yield CollectionField::new('faqCategories', 'Catégories des questions')
            ->setEntryType(FaqCategoryCollectionType::class)
            ->setColumns(12);
    }

    public function configureActions(Actions $actions): Actions
    {
        $order = Action::new('order', 'Ordre', 'fa fa-sort')
            ->linkToCrudAction('order')
            ->displayIf(fn ($entity) => $entity instanceof Faq);

        return $actions
            ->add(Crud::PAGE_INDEX, $order)
            ->add(Crud::PAGE_DETAIL, $order)
            ->add(Crud::PAGE_EDIT, $order)
        ;
    }
    public function order(
        AdminContext $adminContext
    ) {
        // la faq choisie
        $faq = $adminContext->getEntity()->getInstance();

        // les repository
        $faqRepository = $this->managerRegistry->getRepository(Faq::class);
        $faqCategoryRepository = $this->managerRegistry->getRepository(FaqCategory::class);
        $faqQuestionAnswserRepository = $this->managerRegistry->getRepository(FaqQuestionAnswser::class);

        $orderToSave = $adminContext->getRequest()->get('orderToSave', null);
        if ($orderToSave) {
            $orderToSave = json_decode($orderToSave);

            if (is_array($orderToSave)) {
                foreach ($orderToSave as $key => $value) {
                    $faqItem = $faqRepository->find($value->id);
                    if (!$faqItem instanceof Faq) {
                        continue;
                    }
                    if (isset($value->children) && is_array($value->children)) {
                        $positionCategory = 0;
                        foreach ($value->children as $faqCategoryItem) {
                            $faqCategory = $faqCategoryRepository->find($faqCategoryItem->id);
                            if ($faqCategory instanceof FaqCategory) {
                                $faqCategory->setPosition($positionCategory);
                                $positionCategory++;
                                $this->managerRegistry->getManager()->persist($faqCategory);

                                if (isset($faqCategoryItem->children) && is_array($faqCategoryItem->children)) {
                                    $positionQuestion = 0;
                                    foreach ($faqCategoryItem->children as $faqQuestionAnswserItem) {
                                        $faqQuestionAnswser = $faqQuestionAnswserRepository
                                            ->find($faqQuestionAnswserItem->id);
                                        if ($faqQuestionAnswser instanceof FaqQuestionAnswser) {
                                            $faqQuestionAnswser->setPosition($positionQuestion);
                                            $positionQuestion++;
                                            $this->managerRegistry->getManager()->persist($faqQuestionAnswser);
                                        }
                                    }
                                }
                            }

                            $this->managerRegistry->getManager()->flush();
                        }
                    }
                }

                $this->addFlash('success', 'L\'ordre des catégories de questions a été enregistré.');

                return $this->redirect(
                    $this->adminUrlGenerator
                        ->setController(FaqCrudController::class)
                        ->setAction('order')
                        ->setEntityId($faq->getId())
                        ->generateUrl()
                );
            }
        }
        return $this->render('admin/program/faq-order.html.twig', [
            'faq' => $faq
        ]);
    }
}
