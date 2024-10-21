<?php

namespace App\Controller\Admin\Perimeter;

use App\Controller\Admin\AtCrudController;
use App\Entity\Perimeter\PerimeterImport;
use App\Message\Perimeter\MsgPerimeterImport;
use App\Repository\User\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerimeterImportCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return PerimeterImport::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield AssociationField::new('adhocPerimeter', 'Périmètre adhoc')
            ->autocomplete()
            ->setHelp('Périmètre à définir.')
            ->hideWhenCreating();
        yield TextField::new('adhocPerimeterName', 'Périmètre adhoc')
            ->onlyWhenCreating()
            ->setHelp(
                'Le nom du périmètre à créer avec cet import. '
                . 'Laissez vide pour auto-remplir avec les données, ex: regions_01_05_06_75_68'
            );

        yield AssociationField::new('author', 'Auteur')
            ->setFormTypeOption('attr', ['readonly' => true, 'autocomplete' => 'off'])
            ->setHelp('Créateur du périmètre')
            ->onlyOnForms()
            ->setFormTypeOptions([
                'query_builder' => function (UserRepository $er) {
                    return $er->getQueryBuilder([
                        'onlyAdmin' => true
                    ]);
                },
                'data' => $this->getUser()
            ]);

        yield FormField::addFieldset('Périmètres à attacher')
            ->onlyOnForms();
        yield IntegerField::new('nbCities', 'Nombre de villes')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->hideWhenCreating();

        $entity = $this->getContext()->getEntity()->getInstance();
        yield ImageField::new('file', 'Fichier csv des codes INSEE')
            ->setHelp('Permière ligne ignorée')
            ->setUploadDir($this->fileService->getUploadTmpDirRelative())
            ->setUploadedFileNamePattern('/[slug]-[timestamp].[extension]')
            ->onlyWhenCreating()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption(
                'upload_new',
                function (UploadedFile $file, string $uploadDir, string $fileName) use ($entity) {
                // créer dossier temporaire si besoin
                    $tmpFolder = $this->fileService->getUploadTmpDir();
                    if (!is_dir($tmpFolder)) {
                        mkdir($tmpFolder, 0777, true);
                    }

                // déplace le fichier dans le dossier temporaire
                    $file->move(
                        $tmpFolder,
                        $fileName
                    );

                    $rowNumber = 1;
                    if (($handle = fopen($tmpFolder . $fileName, "r")) !== false) {
                        while (($data = fgetcsv($handle, 4096, ';')) !== false) {
                            if ($rowNumber == 1) {
                                $rowNumber++;
                                continue;
                            };
                            $entity->addCityCode(str_pad($data[0], 5, '0', STR_PAD_LEFT));
                        }
                    }

                // suppression fichier temporaire
                    unlink($tmpFolder . $fileName);

                // retour vide
                    return;
                }
            );

        yield FormField::addFieldset('Import')->hideWhenCreating();
        yield BooleanField::new('isImported', 'Import effectué')
            ->hideWhenCreating();
        yield DateTimeField::new('timeImported', 'Date importation')
            ->onlyOnForms()
            ->hideWhenCreating();
        yield BooleanField::new('askProcessing', 'Demande effectuéee')
            ->setHelp(
                'Vous n\'avez normalement pas à modifier ce champ, '
                . 'il est là pour indiquer si l\'import a été demandé.'
            )
            ->hideOnIndex()
            ->hideWhenCreating();
        yield BooleanField::new('importProcessing', 'Importation en cours de traitement')
            ->hideOnIndex()
            ->setHelp(
                'Vous n\'avez normalement pas à modifier ce champ, '
                . 'il est là pour indiquer si l\'import est en cours.'
            )
            ->hideWhenCreating();

        yield FormField::addFieldset('Métadonnées')->hideWhenCreating();
        yield DateTimeField::new('timeCreate', 'Date création')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->onlyOnForms()
            ->hideWhenCreating();
        yield DateTimeField::new('timeUpdate', 'Date de mise à jour')
            ->setFormTypeOption('attr', ['readonly' => true])
            ->onlyOnForms()
            ->hideWhenCreating();
    }


    public function configureActions(Actions $actions): Actions
    {
        $askImport = Action::new('askImport', 'Demander import')
            ->setHtmlAttributes(['title' => 'Demander import']) // titre
            ->linkToCrudAction('askImport') // l'action appellée
        ;

        $export = Action::new('export', 'Exporter')
            ->setHtmlAttributes(['title' => 'Exporter']) // titre
            ->linkToCrudAction('export') // l'action appellée
        ;


        return
            $actions
            ->add(Crud::PAGE_INDEX, $export)
            ->add(Crud::PAGE_EDIT, $export)
            ->add(Crud::PAGE_INDEX, $askImport)
        ;
    }

    public function askImport(AdminContext $context): Response
    {
        $entity = $context->getEntity()->getInstance();
        if (!$entity instanceof PerimeterImport) {
            throw new \Exception('Erreur : périmètre introuvable');
        }

        $entity->setAskProcessing(true);
        $entity->setImportProcessing(false);
        $entity->setIsImported(false);
        $this->managerRegistry->getManager()->persist($entity);
        $this->managerRegistry->getManager()->flush();

        // compte le nombre d'import en attente
        $nbImport = $this->managerRegistry->getRepository(PerimeterImport::class)->countCustom([
            'askProcessing' => true,
            'exclude' => $entity
        ]);

        // envoi au worker
        $this->messageBusInterface->dispatch(new MsgPerimeterImport());

        $this->addFlash(
            'success',
            'Import demandé. '
                . 'Vous recevrez un mail lorsque l\'import sera terminé. '
                . 'Il y en a actuellement ' . $nbImport . ' en attente.'
        );
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect(
            $adminUrlGenerator
                ->setController(PerimeterImportCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function export(AdminContext $context): StreamedResponse
    {
        /** @var PerimeterImport $entity */
        $entity = $context->getEntity()->getInstance();

        // nom du fichier
        $now = new \DateTime(date('Y-m-d H:i:s'));
        if ($entity->getAdhocPerimeter()) {
            $filename = $entity->getAdhocPerimeter()->getName();
        } else {
            $filename = 'export-import-perimetre';
        }
        $filename .= '-' . $now->format('Y_m_d_H_i_s');

        // Stream response
        $response = new StreamedResponse(function () use ($entity) {
            // Open the output stream
            $fh = fopen('php://output', 'w');

            // CSV Header
            fputcsv($fh, ['Code insee']);

            // CSV Data
            foreach ($entity->getCityCodes() as $cityCode) {
                fputcsv($fh, [$cityCode]);
            }
        });

        // réponse avec header csv
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        return $response;
    }
}
