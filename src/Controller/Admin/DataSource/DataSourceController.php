<?php

namespace App\Controller\Admin\DataSource;

use App\Controller\Admin\DashboardController;
use App\Entity\DataSource\DataSource;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataSourceController extends DashboardController
{
    #[Route('/admin/data-source/{id}/analyse', name: 'admin_data_source_analyse')]
    public function analyse(
        $id,
        AdminContext $adminContext,
        ManagerRegistry $managerRegistry,
        HttpClientInterface $httpClientInterface
    ): Response
    {
        // ce qu'on recherche
        $aidsLabelSearch = ['result', 'results', 'aides', 'records'];
        $aidsFormImport = [];

        // la source de données
        $dataSource = $managerRegistry->getRepository(DataSource::class)->find($id);
        
        try {
            $response = $httpClientInterface->request('GET', $dataSource->getImportApiUrl());
            $content = $response->getContent();
            $content = $response->toArray();
            foreach ($content as $key => $value) {
                if (in_array($key, $aidsLabelSearch) && is_array($value)) {
                    $aidsFormImport = $value;
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        
        }

        return $this->render('admin/data-source/analyse.html.twig', [
            'dataSource' => $dataSource,
            'aidsFromImport' => $aidsFormImport
        ]);
    }
}