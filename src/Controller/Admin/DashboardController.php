<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Aid\AidCrudController;
use App\Controller\Admin\Page\PageCrudController;
use App\Controller\Admin\User\ApiTokenAskCrudController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerCategory;
use App\Entity\Backer\BackerGroup;
use App\Entity\Backer\BackerSubcategory;
use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
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
use App\Entity\Page\Page;
use App\Entity\Perimeter\FinancialData;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Entity\Program\PageTab;
use App\Entity\Program\Program;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectValidated;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceCategory;
use App\Entity\Search\SearchPage;
use App\Entity\User\ApiTokenAsk;
use App\Entity\User\User;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserService $userService,
        private ManagerRegistry $managerRegistry,
        private AdminUrlGenerator $adminUrlGenerator,
        private ChartBuilderInterface $chartBuilderInterface,
    )
    {   
    }
        
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // Aides en attente de revue
        $nbAidsInReview = $this->managerRegistry->getRepository(Aid::class)->countCustom(['status' => Aid::STATUS_REVIEWABLE]);
        $urlAidsInReview = $this->adminUrlGenerator
            ->setController(AidCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[status][value]', Aid::STATUS_REVIEWABLE)
            ->set('filters[status][comparison]', '=')
            ->generateUrl();

        // aides publiées depuis la semaine dernière
        $lastWeek = new \DateTime();
        $lastWeek->modify('-7 days');
        $nbAidsPublishedLastWeek = $this->managerRegistry->getRepository(Aid::class)->countCustom(['status' => Aid::STATUS_PUBLISHED, 'publishedAfter' => $lastWeek]);
        $urlAidsPublishedLastWeek = $this->adminUrlGenerator
            ->setController(AidCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[status][value]', Aid::STATUS_PUBLISHED)
            ->set('filters[status][comparison]', '=')
            ->set('filters[datePublished][value]', $lastWeek->format('Y-m-d'))
            ->set('filters[datePublished][comparison]', '>=')
            ->generateUrl();

        // Demandes de token API
        $nbApiTokenAsks = $this->managerRegistry->getRepository(ApiTokenAsk::class)->countPendingAccept();
        $urlApiTokenAsk = $this->adminUrlGenerator
            ->setController(ApiTokenAskCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        // inscriptions
        $lastMonth = new \DateTime();
        $lastMonth->modify('-1 month');
        $registerByDay = $this->managerRegistry->getRepository(User::class)->countRegisterByDay(['dateCreateMin' => $lastMonth]);
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

        return $this->render('admin/dashboard.html.twig', [
            'nbAidsInReview' => $nbAidsInReview,
            'urlAidsInReview' => $urlAidsInReview,
            'nbAidsPublishedLastWeek' => $nbAidsPublishedLastWeek,
            'urlAidsPublishedLastWeek' => $urlAidsPublishedLastWeek,
            'nbApiTokenAsks' => $nbApiTokenAsks,
            'urlApiTokenAsk' => $urlApiTokenAsk,
            'chart' => $chart,
        ]);
    }
    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            // ->addWebpackEncoreEntry('app')
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
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::subMenu('Utilisateurs', 'fas fa-user')->setSubItems([
            MenuItem::linkToCrud('Dernières connexions des utilisateurs', 'fas fa-list', LogUserLogin::class),
            MenuItem::linkToCrud('Utilisateurs', 'fas fa-list', User::class),
            MenuItem::linkToCrud('Demandes de token API', 'fas fa-list', ApiTokenAsk::class),
        ]);

        yield MenuItem::subMenu('Organizations', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Organizations', 'fas fa-list', Organization::class),
            MenuItem::linkToCrud('Types', 'fas fa-list', OrganizationType::class),
            MenuItem::linkToCrud('Groupes des types', 'fas fa-list', OrganizationTypeGroup::class),
        ]);


        yield MenuItem::subMenu('Aides', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Destinations', 'fas fa-list', AidDestination::class),
            MenuItem::linkToCrud('Aide Projets', 'fas fa-list', AidProject::class),
            MenuItem::linkToCrud('Récurrences', 'fas fa-list', AidRecurrence::class),
            MenuItem::linkToCrud('Etapes', 'fas fa-list', AidStep::class),
            MenuItem::linkToCrud('Aide projets suggérés', 'fas fa-list', AidSuggestedAidProject::class),
            MenuItem::linkToCrud('Aide type', 'fas fa-list', AidType::class),
            MenuItem::linkToCrud('Aide type group', 'fas fa-list', AidTypeGroup::class),
            MenuItem::linkToCrud('Aides', 'fas fa-list', Aid::class),
        ]);

        yield MenuItem::subMenu('Catégories', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Catégories', 'fas fa-list', Category::class),
            MenuItem::linkToCrud('Themes', 'fas fa-list', CategoryTheme::class),
        ]);

        yield MenuItem::subMenu('Porteurs', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Catégories', 'fas fa-list', BackerCategory::class),
            MenuItem::linkToCrud('Sous-Catégories', 'fas fa-list', BackerSubcategory::class),
            MenuItem::linkToCrud('Groupes', 'fas fa-list', BackerGroup::class),
            MenuItem::linkToCrud('Porteurs', 'fas fa-list', Backer::class),
        ]);

        yield MenuItem::subMenu('Programmes', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Onglets', 'fas fa-list', PageTab::class),
            MenuItem::linkToCrud('Programmes', 'fas fa-list', Program::class),
        ]);

        yield MenuItem::subMenu('Projets', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Projets subventionnés', 'fas fa-list', ProjectValidated::class),
            MenuItem::linkToCrud('Projets', 'fas fa-list', Project::class),
        ]);

        yield MenuItem::subMenu('Projets Référents', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Keyword', 'fas fa-list', KeywordReference::class),
            MenuItem::linkToCrud('Projets', 'fas fa-list', ProjectReference::class),
            MenuItem::linkToCrud('Catégories de projet', 'fas fa-list', ProjectReferenceCategory::class),
        ]);

        yield MenuItem::subMenu('Mots clés', 'fas fa-table')->setSubItems([
            MenuItem::linkToCrud('Mots clés', 'fas fa-list', Keyword::class),
            MenuItem::linkToCrud('Listes de synonymes', 'fas fa-list', KeywordSynonymlist::class),
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
        ]);

        yield MenuItem::subMenu('Pages Personnalisées', 'fas fa-newspaper')->setSubItems([
            MenuItem::linkToCrud('Pages Personnalisées', 'fas fa-list', SearchPage::class),            
        ]);



        yield MenuItem::subMenu('Configuration système', 'fas fa-cogs')->setSubItems([
            MenuItem::linkToCrud('Exports de données', 'fas fa-list', DataExport::class),
            MenuItem::linkToCrud('Sources de données', 'fas fa-list', DataSource::class),
        ]);
    }
}
