<?php

namespace App\Service\Export;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use Doctrine\ORM\QueryBuilder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExporterService
{
    public function  createResponseFromQueryBuilder(
        QueryBuilder $queryBuilder,
        mixed $entityFcqn,
        string $filename,
        string $format = 'csv'
    ): StreamedResponse
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1.5G');

        $response = new StreamedResponse();
        $response->setCallback(function () use ($queryBuilder, $entityFcqn, $filename, $format) {
            $entity = new $entityFcqn();

            $results = $queryBuilder->getQuery()->getResult();

            $datas = $this->getDatasFromEntityType($entity, $results);

            if ($format == 'csv') {
                $options = new \OpenSpout\Writer\CSV\Options();
                $options->FIELD_DELIMITER = ';';
                $options->FIELD_ENCLOSURE = '"';
    
                $writer = new \OpenSpout\Writer\CSV\Writer($options);
            } else if ($format == 'xlsx') {
                $sheetView = new SheetView();               
                $writer = new \OpenSpout\Writer\XLSX\Writer();
            } else {
                throw new \Exception('Format not supported');
            }

            $now = new \DateTime(date('Y-m-d H:i:s'));
            $writer->openToBrowser('export_'.$filename.'_at_'.$now->format('d_m_Y'));

            if ($format == 'xlsx') {
                $writer->getCurrentSheet()->setSheetView($sheetView);
            }
            $headers = [];
            if (isset($datas[0])) {
                $headers = array_keys($datas[0]);
            }

            $cells = [];
            foreach ($headers as $csvHeader) {
                $cells[] = Cell::fromValue($csvHeader);
            }
            
            /** add a row at a time */
            $singleRow = new Row($cells);
            $writer->addRow($singleRow);

            foreach ($datas as $data) {
                $cells = [];
                foreach ($data as $value) {
                    $cells[] = Cell::fromValue($value);
                }
                $singleRow = new Row($cells);
                $writer->addRow($singleRow);
            }
            
            $writer->close();
        });
    
        return $response;
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