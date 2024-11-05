<?php

namespace App\Controller\Admin;

use App\Enum\Direction;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\File\FileService;
use App\Service\Image\ImageService;
use App\Service\User\UserService;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AtCrudController extends AbstractCrudController
{
    public const UPLOAD_TMP_FOLDER = '/public/uploads/_tmp/';

    public function __construct(
        public ManagerRegistry $managerRegistry,
        public ImageService $imageService,
        public ParamService $paramService,
        public FileService $fileService,
        public KernelInterface $kernelInterface,
        public AdminUrlGenerator $adminUrlGenerator,
        public RequestStack $requestStack,
        public AidSearchFormService $aidSearchFormService,
        public AidService $aidService,
        public UserService $userService,
        public UserPasswordHasherInterface $userPasswordHasherInterface,
        public MessageBusInterface $messageBusInterface,
        public StringService $stringService
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return '';
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addWebpackEncoreEntry('import-scss/admin/admin');
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud)
            ->addFormTheme('form/text_length_count_type.html.twig')
            ->addFormTheme('form/url_click_type.html.twig')
            ->addFormTheme('form/image_field_preview.html.twig')
            ->addFormTheme('form/add_new_type.html.twig')
            ->addFormTheme('form/collection_copyable_type.html.twig')
            ->setDefaultSort(['id' => 'DESC']) // modifie le tri
            ->showEntityActionsInlined() // met les actions affichées directement
        ;
        $entityTest = new (static::getEntityFqcn());

        if (method_exists($entityTest, 'getPosition')) {
            $crud
                ->setDefaultSort(['position' => 'ASC']) // modifie le tri
            ;
        }

        return $crud;
    }

    /**
     * Tri des entités (sur le champ position)
     */

    public function configureActions(Actions $actions): Actions
    {
        if (!static::getEntityFqcn()) {
            return $actions;
        }

        $entityTest = new (static::getEntityFqcn());

        if (method_exists($entityTest, 'getPosition')) {
            /** @var ServiceEntityRepository<object> $repository */
            $repository = $this->managerRegistry->getRepository(static::getEntityFqcn());
            $entityCount = $repository->count([]);

            // les actions pour monter / descendre
            $moveTop = Action::new('moveTop', false, 'fa fa-arrow-up')
                ->setHtmlAttributes(['title' => 'Move to top']) // titre
                ->linkToCrudAction('moveTop') // l'action appellée
                ->displayIf(fn ($entity) => $entity->getPosition() > 0); // condition d'affichage

            $moveUp = Action::new('moveUp', false, 'fa fa-sort-up')
                ->setHtmlAttributes(['title' => 'Move up'])
                ->linkToCrudAction('moveUp')
                ->displayIf(fn ($entity) => $entity->getPosition() > 0);

            $moveDown = Action::new('moveDown', false, 'fa fa-sort-down')
                ->setHtmlAttributes(['title' => 'Move down'])
                ->linkToCrudAction('moveDown')
                ->displayIf(fn ($entity) => $entity->getPosition() < $entityCount - 1);

            $moveBottom = Action::new('moveBottom', false, 'fa fa-arrow-down')
                ->setHtmlAttributes(['title' => 'Move to bottom'])
                ->linkToCrudAction('moveBottom')
                ->displayIf(fn ($entity) => $entity->getPosition() < $entityCount - 1);

            return $actions
                ->add(Crud::PAGE_INDEX, $moveBottom)
                ->add(Crud::PAGE_INDEX, $moveDown)
                ->add(Crud::PAGE_INDEX, $moveUp)
                ->add(Crud::PAGE_INDEX, $moveTop);
        }

        return $actions;
    }

    public function moveTop(AdminContext $context): Response
    {
        return $this->move($context, Direction::Top);
    }

    public function moveUp(AdminContext $context): Response
    {
        return $this->move($context, Direction::Up);
    }

    public function moveDown(AdminContext $context): Response
    {
        return $this->move($context, Direction::Down);
    }

    public function moveBottom(AdminContext $context): Response
    {
        return $this->move($context, Direction::Bottom);
    }

    private function move(AdminContext $context, Direction $direction): Response
    {
        $object = $context->getEntity()->getInstance();
        $entityCount = $this->managerRegistry->getManager()
            ->getRepository(static::getEntityFqcn())->count([]);
        $oldPosition = $object->getPosition();
        $newPosition = match ($direction) {
            Direction::Top => 0,
            Direction::Up => $object->getPosition() - 1,
            Direction::Down => $object->getPosition() + 1,
            Direction::Bottom => $entityCount - 1,
        };

        $object->setPosition($newPosition);

        $this->managerRegistry->getManager()->flush();
        $this->updatePositions(
            [
                'oldPosition' => $oldPosition,
                'newPosition' => $object->getPosition(),
                'exclude' => $object
            ]
        );

        $this->addFlash('success', 'Element repositionné.');

        return $this->redirect($context->getReferrer());
    }

    /**
     *
     * @param array<string, mixed>|null $params
     * @return void
     */
    public function updatePositions(array $params = null): void
    {
        $exclude = $params['exclude'] ?? null;
        $newPosition = $params['newPosition'] ?? null;
        $oldPosition = $params['oldPosition'] ?? null;

        $direction = $newPosition < $oldPosition ? 'down' : 'up';

        /** @var ServiceEntityRepository $serviceEntityRepository */
        $serviceEntityRepository = $this->managerRegistry->getRepository(static::getEntityFqcn());
        $qb = $serviceEntityRepository->createQueryBuilder('p');
        $qb->update(static::getEntityFqcn(), 'pt');
        if ($direction == 'down') {
            $qb->set('pt.position', 'pt.position + 1');
        } else {
            $qb->set('pt.position', 'pt.position - 1');
        }
        if ($exclude !== null) {
            $qb->andWhere('pt != :exclude')
                ->setParameter('exclude', $exclude)
            ;
        }
        if ($newPosition !== null) {
            if ($direction == 'down') {
                $qb
                    ->andWhere('pt.position >= :newPosition')
                    ->andWhere('pt.position <= :oldPosition')
                    ->setParameter('oldPosition', $oldPosition)
                    ->setParameter('newPosition', $newPosition)
                ;
                ;
            } else {
                $qb
                    ->andWhere('pt.position <= :newPosition')
                    ->andWhere('pt.position >= :oldPosition')
                    ->setParameter('oldPosition', $oldPosition)
                    ->setParameter('newPosition', $newPosition)
                ;
            }
        }

        $qb->getQuery()->execute();
    }

    public function exportSpreadsheet(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename,
        string $format = 'csv'
    ): Response {
        ini_set('max_execution_time', 60 * 60);
        ini_set('memory_limit', '1G');

        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create(
            $context->getCrud()->getFiltersConfig(),
            $fields,
            $context->getEntity()
        );
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        return $spreadsheetExporterService->createResponseFromQueryBuilder(
            $queryBuilder,
            $context->getEntity()->getFqcn(),
            $filename,
            $format
        );
    }

    public function exportXlsx(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename = ''
    ): Response {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'xlsx');
    }

    public function exportCsv(
        AdminContext $context,
        SpreadsheetExporterService $spreadsheetExporterService,
        string $filename = ''
    ): Response {
        return $this->exportSpreadsheet($context, $spreadsheetExporterService, $filename, 'csv');
    }

    public function getExportXlsxAction(): Action
    {
        return Action::new('exportXlsx')
            ->linkToUrl(function () {
                $request = $this->requestStack->getCurrentRequest();
                return $this->adminUrlGenerator->setAll($request->query->all())
                    ->setAction('exportXlsx')
                    ->generateUrl();
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();
    }

    public function getExportCsvAction(): Action
    {
        return Action::new('exportCsv')
            ->linkToUrl(function () {
                $request = $this->requestStack->getCurrentRequest();
                return $this->adminUrlGenerator->setAll($request->query->all())
                    ->setAction('exportCsv')
                    ->generateUrl();
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();
    }
}
