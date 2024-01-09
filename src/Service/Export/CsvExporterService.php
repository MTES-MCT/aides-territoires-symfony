<?php

namespace App\Service\Export;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporterService
{
    // TODO a factoriser
    public function createResponseFromQueryBuilder(QueryBuilder $queryBuilder, FieldCollection $fields, string $filename): Response
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1.5G');
        
        try {
            $fieldsToKeep = ['id', 'live', 'name', 'url', 'status'];
            $results = $queryBuilder->getQuery()->getResult();

            $datas = [];
            foreach ($results as $result) {
                $datas[] = [
                    'id' => $result->getId(),
                    'live' => $result->isLive() ? 'Oui' : 'Non',
                    'name' => $result->getName(),
                    'url' => $result->getUrl(),
                    'status' => $result->getStatus(),
                ];
            }
            // Stream response
            $response = new StreamedResponse(function () use ($fieldsToKeep, $datas) {
                
                // Open the output stream
                $fh = fopen('php://output', 'w');
    
                fputcsv($fh, $fieldsToKeep, ';', '"');
                foreach ($datas as $data) {
                    fputcsv($fh, $data, ';', '"');
                }
            });
    
            // réponse avec header csv
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'.csv"');
            return $response;
        } catch (\Exception $e) {
            // dd($e);
        }
    }
}