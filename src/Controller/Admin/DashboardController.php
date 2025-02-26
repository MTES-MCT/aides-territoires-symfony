<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Aid\AidCrudController;
use App\Controller\Admin\Backer\BackerAskAssociateCrudController;
use App\Controller\Admin\Backer\BackerCrudController;
use App\Controller\Admin\Page\PageCrudController;
use App\Controller\Admin\Project\ProjectCrudController;
use App\Controller\Admin\User\ApiTokenAskCrudController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidNotFoundError;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Alert\Alert;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerAskAssociate;
use App\Entity\Backer\BackerCategory;
use App\Entity\Backer\BackerGroup;
use App\Entity\Backer\BackerSubcategory;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Contact\ContactMessage;
use App\Entity\DataExport\DataExport;
use App\Entity\DataSource\DataSource;
use App\Entity\Keyword\Keyword;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\OrganizationTypeGroup;
use App\Entity\Page\Faq;
use App\Entity\Page\FaqCategory;
use App\Entity\Page\FaqQuestionAnswser;
use App\Entity\Page\Page;
use App\Entity\Perimeter\FinancialData;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Entity\Program\PageTab;
use App\Entity\Program\Program;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectValidated;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\KeywordReferenceSuggested;
use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceCategory;
use App\Entity\Reference\ProjectReferenceMissing;
use App\Entity\Search\SearchPage;
use App\Entity\Site\AbTest;
use App\Entity\Site\UrlRedirect;
use App\Entity\User\ApiTokenAsk;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerAskAssociateRepository;
use App\Repository\Backer\BackerRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\User\ApiTokenAskRepository;
use App\Repository\User\UserRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        protected UserService $userService,
        protected ManagerRegistry $managerRegistry,
        protected AdminUrlGenerator $adminUrlGenerator,
        protected ChartBuilderInterface $chartBuilderInterface,
        protected MessageBusInterface $messageBusInterface
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Aides en attente de revue
        /** @var AidRepository $aidRepository */
        $aidRepository = $this->managerRegistry->getRepository(Aid::class);

        $nbAidsInReview = $aidRepository->countCustom(['status' => Aid::STATUS_REVIEWABLE]);
        $urlAidsInReview = $this->adminUrlGenerator
            ->setController(AidCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[status][value]', Aid::STATUS_REVIEWABLE)
            ->set('filters[status][comparison]', '=')
            ->generateUrl();

        // aides publiées depuis la semaine dernière
        $lastWeek = new \DateTime();
        $lastWeek->modify('-7 days');
        $nbAidsPublishedLastWeek = $aidRepository->countCustom(
            ['status' => Aid::STATUS_PUBLISHED, 'publishedAfter' => $lastWeek]
        );
        $urlAidsPublishedLastWeek = $this->adminUrlGenerator
            ->setController(AidCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[status][value]', Aid::STATUS_PUBLISHED)
            ->set('filters[status][comparison]', '=')
            ->set('filters[datePublished][value]', $lastWeek->format('Y-m-d'))
            ->set('filters[datePublished][comparison]', '>=')
            ->generateUrl();

        // Demandes de token API
        /** @var ApiTokenAskRepository $apiTokenAskRepository */
        $apiTokenAskRepository = $this->managerRegistry->getRepository(ApiTokenAsk::class);
        $nbApiTokenAsks = $apiTokenAskRepository->countPendingAccept();
        $urlApiTokenAsk = $this->adminUrlGenerator
            ->setController(ApiTokenAskCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        // inscriptions
        $lastMonth = new \DateTime();
        $lastMonth->modify('-1 month');
        /** @var UserRepository $userRepository */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $registerByDay = $userRepository->countRegisterByDay(['dateCreateMin' => $lastMonth]);
        $labels = [];
        $datas = [];

        foreach ($registerByDay as $register) {
            $labels[] = $register['day'];
            $datas[] = $register['total'];
        }

        $chart = $this->chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Inscriptions sur les 30 derniers jours',
                    'backgroundColor' => 'rgb(255, 255, 255)',
                    'borderColor' => 'rgb(255, 0, 0)',
                    'data' => $datas,
                ],
            ],
        ]);

        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->managerRegistry->getRepository(Project::class);
        // Projets en attente de validation
        $nbProjectsInReview = $projectRepository->countReviewable();
        $urlProjectsInReview = $this->adminUrlGenerator
            ->setController(ProjectCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[status][value]', Project::STATUS_REVIEWABLE)
            ->set('filters[status][comparison]', '=')
            ->generateUrl();

        /** @var BackerRepository $backerRepository */
        $backerRepository = $this->managerRegistry->getRepository(Backer::class);

        // Fiches porteurs d'aides en attente de validation
        $nbBackersInReview = $backerRepository->countReviewable();
        $urlBackersInReview = $this->adminUrlGenerator
            ->setController(BackerCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[active][value]', false)
            ->generateUrl();

        // demande d'association en attente
        /** @var BackerAskAssociateRepository $backerAskAssociateRepo */
        $backerAskAssociateRepo = $this->managerRegistry->getRepository(BackerAskAssociate::class);
        $nbBackerAskAssociatePending = $backerAskAssociateRepo->countPendings();
        $urlBackerAskAssociatePending = $this->adminUrlGenerator
            ->setController(BackerAskAssociateCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[accepted][value]', false)
            ->set('filters[refused][value]', false)
            ->generateUrl();

        // rendu template
        return $this->render('admin/dashboard.html.twig', [
            'nbAidsInReview' => $nbAidsInReview,
            'urlAidsInReview' => $urlAidsInReview,
            'nbAidsPublishedLastWeek' => $nbAidsPublishedLastWeek,
            'urlAidsPublishedLastWeek' => $urlAidsPublishedLastWeek,
            'nbApiTokenAsks' => $nbApiTokenAsks,
            'urlApiTokenAsk' => $urlApiTokenAsk,
            'chart' => $chart,
            'nbProjectsInReview' => $nbProjectsInReview,
            'urlProjectsInReview' => $urlProjectsInReview,
            'nbBackersInReview' => $nbBackersInReview,
            'urlBackersInReview' => $urlBackersInReview,
            'nbBackerAskAssociatePending' => $nbBackerAskAssociatePending,
            'urlBackerAskAssociatePending' => $urlBackerAskAssociatePending
        ]);
    }
    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addWebpackEncoreEntry('admin/admin')
        ;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Home')
            ->setFaviconPath('build/images/favicon/favicon.svg');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute(
            'Visiter le site',
            'fas fa-external-link-alt',
            'app_home',
            []
        )->setLinkTarget('_blank');
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::subMenu('Utilisateurs', 'fas fa-user')->setSubItems([
            MenuItem::linkToCrud('Dernières connexions des utilisateurs', 'fas fa-list', LogUserLogin::class),
            MenuItem::linkToCrud('Utilisateurs', 'fas fa-list', User::class),
            MenuItem::linkToCrud('Demandes de token API', 'fas fa-list', ApiTokenAsk::class),
        ]);

        yield MenuItem::subMenu('Organisations', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Organisations', 'fas fa-list', Organization::class),
            MenuItem::linkToCrud('Types', 'fas fa-list', OrganizationType::class),
            MenuItem::linkToCrud('Groupes des types', 'fas fa-list', OrganizationTypeGroup::class),
        ]);


        yield MenuItem::subMenu('Aides', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Aides', 'fas fa-list', Aid::class),
            MenuItem::linkToCrud('Aide Projets', 'fas fa-list', AidProject::class),
            MenuItem::linkToCrud('Aide projets suggérés', 'fas fa-list', AidSuggestedAidProject::class),
            // MenuItem::linkToRoute('[caractéristique] Associations', 'fas fa-list', 'admin_aid_association', []),
            MenuItem::linkToCrud('[caractéristique] Destinations', 'fas fa-list', AidDestination::class),
            MenuItem::linkToCrud('[caractéristique] Etapes', 'fas fa-list', AidStep::class),
            MenuItem::linkToCrud('[caractéristique] Récurrences', 'fas fa-list', AidRecurrence::class),
            MenuItem::linkToCrud('[caractéristique] Types d\'aides', 'fas fa-list', AidType::class),
            MenuItem::linkToCrud('[caractéristique] Groupes de types d\'aides', 'fas fa-list', AidTypeGroup::class),
            MenuItem::linkToCrud('Aides non trouvée', 'fas fa-list', AidNotFoundError::class),
        ]);

        yield MenuItem::subMenu('Catégories', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Catégories', 'fas fa-list', CategoryTheme::class),
            MenuItem::linkToCrud('Sous-catégories', 'fas fa-list', Category::class),
        ]);

        yield MenuItem::subMenu('Porteurs', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Catégories', 'fas fa-list', BackerCategory::class),
            MenuItem::linkToCrud('Sous-Catégories', 'fas fa-list', BackerSubcategory::class),
            MenuItem::linkToCrud('Groupes', 'fas fa-list', BackerGroup::class),
            MenuItem::linkToCrud('Porteurs', 'fas fa-list', Backer::class),
            MenuItem::linkToCrud('Demandes association', 'fas fa-list', BackerAskAssociate::class),
        ]);

        yield MenuItem::subMenu('Programmes', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Onglets', 'fas fa-list', PageTab::class),
            MenuItem::linkToCrud('Programmes', 'fas fa-list', Program::class),
            MenuItem::linkToCrud('Faqs', 'fas fa-list', Faq::class),
            MenuItem::linkToCrud('Faqs Catégories', 'fas fa-list', FaqCategory::class),
            MenuItem::linkToCrud('Faqs Questions', 'fas fa-list', FaqQuestionAnswser::class),
        ]);

        yield MenuItem::subMenu('Projets', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Projets subventionnés', 'fas fa-list', ProjectValidated::class),
            MenuItem::linkToCrud('Projets', 'fas fa-list', Project::class),
        ]);

        yield MenuItem::subMenu('Projets Référents', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Mots clé référents', 'fas fa-list', KeywordReference::class),
            MenuItem::linkToCrud('Projets', 'fas fa-list', ProjectReference::class),
            MenuItem::linkToCrud('Catégories de projet', 'fas fa-list', ProjectReferenceCategory::class),
            MenuItem::linkToCrud('Projets manquants', 'fas fa-list', ProjectReferenceMissing::class),
            MenuItem::linkToCrud('Suggestion mots clés / aides', 'fas fa-list', KeywordReferenceSuggested::class),
        ]);


        yield MenuItem::subMenu('Périmètres', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Données Financiaires', 'fas fa-list', FinancialData::class),
            MenuItem::linkToCrud('Périmètres', 'fas fa-list', Perimeter::class),
            MenuItem::linkToCrud('Imports périmètres', 'fas fa-list', PerimeterImport::class),
        ]);

        yield MenuItem::subMenu('Contacts', 'fas fa-mail-bulk')->setSubItems([
            MenuItem::linkToCrud('Contacts', 'fas fa-list', ContactMessage::class),
        ]);

        yield MenuItem::subMenu('Contenu éditorial', 'fas fa-newspaper')->setSubItems([
            MenuItem::linkToCrud('Articles de blog', 'fas fa-list', BlogPost::class),
            MenuItem::linkToCrud('Catégories des articles de blog', 'fas fa-list', BlogPostCategory::class),
            MenuItem::linkToCrud('Pages', 'fas fa-list', Page::class)
                ->setController(PageCrudController::class),
            MenuItem::linkToCrud('Communication promotionnelle', 'fas fa-list', BlogPromotionPost::class)
        ]);

        yield MenuItem::subMenu('Pages Personnalisées', 'fas fa-newspaper')->setSubItems([
            MenuItem::linkToCrud('Pages Personnalisées', 'fas fa-list', SearchPage::class),
        ]);

        yield MenuItem::subMenu('Alertes', 'far fa-bell')->setSubItems([
            MenuItem::linkToCrud('Alertes', 'fas fa-list', Alert::class),
        ]);

        yield MenuItem::subMenu('Configuration système', 'fas fa-cogs')->setSubItems([
            MenuItem::linkToCrud('AbTesting', 'fas fa-list', AbTest::class),
            MenuItem::linkToCrud('Urls de redirections', 'fas fa-list', UrlRedirect::class),
            MenuItem::linkToCrud('Exports de données', 'fas fa-list', DataExport::class),
            MenuItem::linkToCrud('Sources de données', 'fas fa-list', DataSource::class),
            MenuItem::linkToRoute('Logs Symfony', 'fas fa-list', 'admin_log_symfony_download', []),
            MenuItem::linkToRoute('Vide Cache Symfony', 'fas fa-list', 'admin_configuration_clear_cache', [])
        ]);

        yield MenuItem::subMenu('Statistiques', 'fas fa-chart-line')->setSubItems([
            MenuItem::linkToRoute('Vapp', 'fas fa-chart-line', 'admin_statistics_vapp', []),
            MenuItem::linkToRoute('Globale', 'fas fa-list', 'admin_statistics_dashboard', []),
            MenuItem::linkToRoute('Aides', 'fas fa-list', 'admin_log_aids_logs', []),
            MenuItem::linkToRoute('Aides favorites', 'fas fa-chart-line', 'admin_statistics_aid_favorites', []),
            MenuItem::linkToRoute('Sources internes', 'fas fa-chart-line', 'admin_statistics_aid_sources', []),
            MenuItem::linkToRoute('Communes - Inscriptions', 'fas fa-list', 'admin_statistics_commune_dashboard', []),
            MenuItem::linkToRoute('Commnunes - Carte', 'fas fa-list', 'admin_statistics_commune_population', []),
            MenuItem::linkToRoute('Blog', 'fas fa-list', 'admin_statistics_blog_dashboard', []),
            MenuItem::linkToRoute(
                'Périmètres manquants',
                'fas fa-list',
                'admin_statistics_log_aid_search_missing_perimeters',
                []
            ),
            MenuItem::linkToRoute(
                'Projets référents',
                'fas fa-list',
                'admin_statistics_project_reference_dashboard',
                []
            ),
            MenuItem::linkToRoute('Porteurs d\'aides', 'fas fa-list', 'admin_statistics_backer_dashboard', []),
            MenuItem::linkToRoute('Recherche', 'fas fa-list', 'admin_statistics_log_aid_search', []),
            MenuItem::linkToRoute('Redirections', 'fas fa-list', 'admin_statistics_log_url_redirect', []),
            MenuItem::linkToRoute('Utilisateurs', 'fas fa-list', 'admin_statistics_user_dashboard', []),
            MenuItem::linkToRoute('Api - Utilisation', 'fas fa-list', 'admin_statistics_api_use', []),

        ]);

        yield MenuItem::subMenu('Obsolète - Mots clés', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Mots clés', 'fas fa-list', Keyword::class),
            MenuItem::linkToCrud('Listes de synonymes', 'fas fa-list', KeywordSynonymlist::class),
        ]);
    }
}
