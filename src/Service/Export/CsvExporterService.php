<?php

namespace App\Service\Export;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporterService
{
    public function createResponseFromQueryBuilder(QueryBuilder $queryBuilder, FieldCollection $fields, string $filename): Response
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1.5G');

        try {
            $result = $queryBuilder->getQuery()->getArrayResult();

            $fieldsToKeep = ['id', 'name', 'slug', 'status'];
            // Convert DateTime objects into strings
            $datas = [];
            foreach ($result as $key => $aidArray) {
                $data = [];
                foreach ($aidArray as $field => $value) {
                    if (!in_array($field, $fieldsToKeep)) {
                        continue;
                    }
                    $data[$field] = $value;
                }
                array_push($datas, $data);
            }

            // Stream response
            $response = new StreamedResponse(function () use ($datas) {
                
                // Open the output stream
                $fh = fopen('php://output', 'w');
    
                foreach ($datas as $data) {
                    fputcsv($fh, $data);
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