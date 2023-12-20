<?php

namespace App\Controller\Admin\User;

use App\Controller\Admin\AtCrudController;
use App\Entity\User\ApiTokenAsk;
use App\Service\Image\ImageService;
use App\Service\User\UserService;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiTokenAskCrudController extends AtCrudController
{
    public function __construct(
        public ManagerRegistry $managerRegistry,
        public ImageService $imageService,
        public ParamService $paramService,
        public KernelInterface $kernelInterface,
        public UserService $userService
    ) {
        parent::__construct($managerRegistry, $imageService, $paramService, $kernelInterface);
    }

    public static function getEntityFqcn(): string
    {
        return ApiTokenAsk::class;
    }

    public function configureFields(string $pageName): iterable
        {
        yield IdField::new('id')->onlyOnIndex();
        yield TextareaField::new('description', 'Description')
        ->hideOnIndex();
        yield TextField::new('urlService', 'Url du service');
        yield AssociationField::new('user', 'Utilisateur')
        ->autocomplete()
        ->setFormTypeOption('attr', ['readonly' => true])
        ;
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield DateTimeField::new('timeAccept', 'Date d\'acceptation')
        ->setFormTypeOption('attr', ['readonly' => true]);
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $accept = Action::new('accept', 'Accepter', 'fas fa-check')
            ->setHtmlAttributes(['title' => 'Accepter', 'target' => '_self']) // titre
            ->linkToCrudAction('accept') // l'action appellée
            ->displayIf(fn ($entity) => !$entity->getUser()->getApiToken()); // condition d'affichage
            ;
        
        return $actions
            ->add(Crud::PAGE_INDEX, $accept)
            ->add(Crud::PAGE_EDIT, $accept)
        ;
    }

    public function accept(AdminContext $context): Response
    {
        $object = $context->getEntity()->getInstance();
        if (!$object->getUser()->getApiToken()) {
            $object->getUser()->setApiToken($this->userService->generateApiToken());
            $this->managerRegistry->getManager()->persist($object->getUser());
        }
        
        $object->setTimeAccept(new \DateTime(date('Y-m-d H:i:s')));
        $this->managerRegistry->getManager()->persist($object);

        $this->managerRegistry->getManager()->flush();    

        $this->addFlash('success', 'Token ajouté à l\'utilisateur.');

        return $this->redirect($context->getReferrer());
    }
}
