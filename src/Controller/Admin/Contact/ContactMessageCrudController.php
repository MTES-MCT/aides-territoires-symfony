<?php

namespace App\Controller\Admin\Contact;

use App\Controller\Admin\AtCrudController;
use App\Entity\Contact\ContactMessage;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ContactMessageCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance() ?? null;

        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('firstname', 'Prénom')
            ->hideOnIndex();
        yield TextField::new('lastname', 'Nom')
            ->hideOnIndex();
        yield EmailField::new('email', 'Email');
        yield TextField::new('phoneNumber', 'Téléphone')
            ->hideOnIndex();
        yield TextField::new('structureAndFunction', 'Structure et fonction')
            ->hideOnIndex();
        yield TextField::new('subject', 'Sujet');
        yield TextareaField::new('message', 'Message')
            ->hideOnIndex();
        if ($entity && $entity->getTimeCreate()) {
            yield DateTimeField::new('timeCreate', 'Date de création')
                ->setFormTypeOption('attr', ['readonly' => true]);
        }
    }
}
