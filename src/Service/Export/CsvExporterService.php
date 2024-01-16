<?php

namespace App\Service\Export;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporterService
{
    // TODO a factoriser
    public function createResponseFromQueryBuilder(QueryBuilder $queryBuilder, FieldCollection $fields, mixed $entityFcqn, string $filename): Response
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1.5G');

        try {
            $entity = new $entityFcqn();

            $fieldsToKeep = ['id', 'live', 'name', 'url', 'status'];
            $results = $queryBuilder->getQuery()->getResult();

            $datas = $this->getDatasFromEntityType($entity, $results);

            $csvHeaders = [];
            if (isset($datas[0])) {
                $csvHeaders = array_keys($datas[0]);
            }
            // Stream response
            $response = new StreamedResponse(function () use ($csvHeaders, $datas) {
                
                // Open the output stream
                $fh = fopen('php://output', 'w');
    
                fputcsv($fh, $csvHeaders, ';', '"');
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

    public function getDatasFromEntityType(mixed $entity, mixed $results): array
    {
        $datas = [];
        switch (get_class($entity)) {
            case Aid::class:
                foreach ($results as $key => $result) {
                    $datas[] = [
                        'id' => $result->getId(),
                        'live' => $result->isLive() ? 'Oui' : 'Non',
                        'name' => $result->getName(),
                        'url' => $result->getUrl(),
                        'status' => $result->getStatus(),
                    ];
                    unset($results[$key]);
                }
                break;
            case User::class:
                /** @var User $result */
                foreach ($results as $key => $result) {
                    $datas[] = [
                        'id' => $result->getId(),
                        'email' => $result->getEmail(),
                        'firstname' => $result->getFirstname(),
                        'lastname' => $result->getLastname(),
                        'isBeneficiary' => $result->isIsBeneficiary() ? 'Oui' : 'Non',
                        'isContributor' => $result->isIsContributor() ? 'Oui' : 'Non',
                        'roles' => implode(',', $result->getRoles()),
                        'isCertified' => $result->isIsCertified() ? 'Oui' : 'Non',
                        'mlConsent' => $result->isMlConsent() ? 'Oui' : 'Non',
                        'timeLastLogin' => $result->getTimeLastLogin() ? $result->getTimeLastLogin()->format('d/m/Y H:i:s') : '',
                        'timeCreate' => $result->getTimeCreate() ? $result->getTimeCreate()->format('d/m/Y H:i:s') : '',
                        'timeUpdate' => $result->getTimeUpdate() ? $result->getTimeUpdate()->format('d/m/Y H:i:s') : '',
                        'invitationTime' => $result->getInvitationTime() ? $result->getInvitationTime()->format('d/m/Y H:i:s') : '',
                        'timeJoinOrganization' => $result->getTimeJoinOrganization() ? $result->getTimeJoinOrganization()->format('d/m/Y H:i:s') : '',
                        'acquisitionChannel' => $result->getAcquisitionChannel(),
                        'acquisitionChannelComment' => $result->getAcquisitionChannelComment(),
                        'notificationCounter' => $result->getNotificationCounter(),
                        'notificationEmailFrequency' => $result->getNotificationEmailFrequency(),
                        'contributorContactPhone' => $result->getContributorContactPhone(),
                        'contributorOrganization' => $result->getContributorOrganization(),
                        'contributorRole' => $result->getContributorRole(),
                        'beneficiaryFunction' => $result->getBeneficiaryFunction(),
                        'beneficiaryRole' => $result->getBeneficiaryRole(),
                        'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : '',
                        'invitationAuthor' => $result->getInvitationAuthor() ? $result->getInvitationAuthor()->getEmail() : '',
                        
                    ];
                    unset($results[$key]);
                }
                break;
        }
        return $datas;
    }
}