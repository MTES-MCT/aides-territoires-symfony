<?php

namespace App\Controller\Admin\User;

use App\Controller\Admin\AtCrudController;
use App\Controller\Admin\Filter\User\UserOganizationTypeFilter;
use App\Controller\Admin\Filter\UserAdministratorOfSearchPageFilter;
use App\Controller\Admin\Filter\UserCountyFilter;
use App\Controller\Admin\Filter\UserRoleFilter;
use App\Entity\User\User;
use App\Service\Export\SpreadsheetExporterService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use Symfony\Component\HttpFoundation\Response;

class UserCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
        ->add(UserRoleFilter::new('roles'))
        ->add('isContributor')
        ->add('isBeneficiary')
        ->add(UserAdministratorOfSearchPageFilter::new('app', 'Administrateur de PP'))
        ->add(ChoiceFilter::new('acquisitionChannel', 'Animateur local')->setChoices(['Animateur' => User::ACQUISITION_CHANNEL_ANIMATOR]))
        ->add(NullFilter::new('apiToken', 'Token API')->setChoiceLabels('Pas de token', 'A un token'))
        ->add('isCertified')
        ->add('mlConsent', 'Consentement newsletter')
        ->add('userGroups')
        ->add(UserCountyFilter::new('perimeter', 'Département'))
        ->add(UserOganizationTypeFilter::new('organizationType', 'Type de structure'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('email', 'Email');
        yield BooleanField::new('isCertified', 'Certifié')
        ->setHelp('Afficher un badge à côté des aides publiées par ce compte.');

        yield FormField::addFieldset('Informations personnelles');
        yield TextField::new('firstname', 'Prénom');
        yield TextField::new('lastname', 'Nom');

        yield FormField::addFieldset('Espace contributeur');
        yield BooleanField::new('isContributor', 'Contributeur')
        ->setHelp('Peut accéder à un espace pour créer et modifier ses aides.');
        yield TextField::new('contributorContactPhone', 'Numéro de téléphone')
        ->hideOnIndex();
        yield IntegerField::new('nbAids', 'Nombre d\'aides')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->onlyOnForms();

        yield FormField::addFieldset('Espace bénéficiaire');

        yield BooleanField::new('isBeneficiary', 'Bénéficiaire')
        ->setHelp('Peut accéder à un espace pour créer et modifier ses projets.');
        $beneficiaryFunctionChoices = [];
        foreach (User::FUNCTION_TYPES as $function) {
            $beneficiaryFunctionChoices[$function['name']] = $function['slug'];
        }
        yield ChoiceField::new('beneficiaryFunction', 'Fonction du bénéficiaire')
        ->setChoices($beneficiaryFunctionChoices)
        ->hideOnIndex();
        yield TextField::new('beneficiaryRole', 'Rôle du bénéficiaire ')
        ->hideOnIndex();
        yield AssociationField::new('organizations', 'Structure(s) du bénéficiaire')
        ->autocomplete()
        ->setHelp('A quelle(s) structure(s) appartient le bénéficiaire ?')
        ->hideOnIndex()
        ->setFormTypeOption('by_reference', false);

        yield FormField::addFieldset('Fusion d\'organisation');
        yield AssociationField::new('proposedOrganization', 'Structure proposée')
        ->setHelp('L’utilisateur a reçu une proposition pour rejoindre cette structure')
        ->autocomplete()
        ->hideOnIndex();
        yield AssociationField::new('invitationAuthor', 'Auteur de l’invitation')
        ->setHelp('utilisateur qui a invité cet utilisateur a rejoindre sa structure')
        ->autocomplete()
        ->hideOnIndex();
        yield DateTimeField::new('invitationTime', 'Date de l’invitation ')
        ->hideOnIndex();
        yield DateTimeField::new('timeJoinOrganization', 'Date d’acceptation de l’invitation')
        ->hideOnIndex();

        yield FormField::addFieldset('Espace administrateur');
        yield AssociationField::new('searchPages', 'Portails dont l\'utilisateur est administrateur')
        ->autocomplete()
        ->hideOnIndex();

        yield FormField::addFieldset('Espace animateur');
        yield AssociationField::new('perimeter', 'Périmètre d’animation')
        ->setHelp('Sur quel périmètre l’animateur local est-il responsable ?')
        ->autocomplete()
        ->hideOnIndex();

        yield FormField::addFieldset('Permissions');
        yield ChoiceField::new('roles', 'Rôles')
        ->setChoices([
            'Administrateur' => User::ROLE_ADMIN,
            'Utilisateur' => User::ROLE_USER,
            'Banni' => User::ROLE_BANNED,
        ])
        ->setHelp('Le role Utilisateur ne peu pas être retiré')
        ->allowMultipleChoices()
        ->renderExpanded()
        ->hideOnIndex()
        ;
        yield TextField::new('apiToken', 'Token API')
        ->hideOnIndex();

        yield FormField::addFieldset('Préférences de notifications');
        yield ChoiceField::new('notificationEmailFrequency', 'Fréquence d’envoi des emails de notifications')
        ->setChoices([
            'Chaque jour' => User::NOTIFICATION_DAILY,
            'Chaque semaine' => User::NOTIFICATION_WEEKLY,
            'Jamais' => User::NOTIFICATION_NEVER,
        ])
        ->hideOnIndex();

        yield FormField::addFieldset('Données diverses');
        yield BooleanField::new('mlConsent', 'A donné son consentement pour recevoir l’actualité')
        ->hideOnIndex();
        $acquisitionChannelChoices = [];
        foreach (User::ACQUISITION_CHANNEL_CHOICES as $channel) {
            $acquisitionChannelChoices[$channel['name']] = $channel['slug'];
        }
        yield ChoiceField::new('acquisitionChannel', 'Canal d’acquisition')
        ->setChoices($acquisitionChannelChoices)
        ->setHelp('Comment l’utilisateur a-t-il connu Aides-territoires?')
        ->hideOnIndex();
        yield TextField::new('acquisitionChannelComment', 'Commentaire Canal d’acquisition')
        ->setHelp('Comment l’utilisateur a-t-il connu Aides-territoires (champ libre)?')
        ->hideOnIndex();
        yield DateTimeField::new('timeLastLogin', 'Dernière connexion')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield DateTimeField::new('timeCreate', 'Date de création')
        ->setFormTypeOption('attr', ['readonly' => true]);
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
        ->setFormTypeOption('attr', ['readonly' => true])
        ->hideOnIndex();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
        ->overrideTemplate('crud/edit', 'admin/user/edit.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // action pour afficher le qrCode
        $showQrCode = Action::new('showQrCode', 'Afficher le QrCode', 'fas fa-qrcode')
            ->setHtmlAttributes(['title' => 'Afficher le QrCode', 'target' => '_blank']) // titre
            ->linkToCrudAction('showQrCode') // l'action appellée
            ->displayIf(fn ($entity) => $this->userService->isUserGranted($entity, User::ROLE_ADMIN)) // condition d'affichage
        ;

        $exportCsvAction = $this->getExportCsvAction();
        $exportXlsxAction = $this->getExportXlsxAction();

        $changePassword = Action::new('changePassword', 'Changer le mot de passe')
        ->linkToCrudAction('changePassword')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $showQrCode)
            ->add(Crud::PAGE_INDEX, $exportCsvAction)
            ->add(Crud::PAGE_INDEX, $exportXlsxAction)
            ->add(Crud::PAGE_INDEX, $changePassword)
            ->add(Crud::PAGE_EDIT, $changePassword)
        ;
    }

    public function changePassword(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        // Redirige vers le formulaire de changement de mot de passe.
        $url = $this->adminUrlGenerator
            ->setController(ChangePasswordCrudController::class)
            ->setAction(Action::EDIT)
            ->set('idUser', $user->getId())
            ->generateUrl();
        return $this->redirect($url);
    }

    public function showQrCode(AdminContext $context): Response
    {
        $object = $context->getEntity()->getInstance();

        return $this->redirectToRoute('app_admin_qr_code_ga', ['idUser' => $object->getId()]);
    }

    public function exportXlsx(AdminContext $context, SpreadsheetExporterService $spreadsheetExporterService, string $filename = 'utilisateur')
    {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'xlsx');
    }

    public function exportCsv(AdminContext $context, SpreadsheetExporterService $spreadsheetExporterService, string $filename = 'utilisateur')
    {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'csv');
    }
}
