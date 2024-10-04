<?php

namespace App\Command\Script;

use App\Service\Email\EmailService;
use App\Service\File\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[AsCommand(name: 'at:script:fv_2023_stats', description: 'Stats fond vert 2023')]
class FondVert2023StatsCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Stats fond vert 2023';
    protected string $commandTextEnd = '>Stats fond vert 2023';

    public function __construct(
        private KernelInterface $kernelInterface,
        private EntityManagerInterface $managerRegistry,
        private FileService $fileService,
        private EmailService $emailService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        // le fichier
        $filePath = $this->kernelInterface->getProjectDir().'/datas/fonds-vert-2023-export.csv';

        // les tableaux pour stocké les données
        $cities = [];
        $organizationNames = [];

        // parcours les lignes
        ini_set('auto_detect_line_endings', true);
        $rowNumber = 1;
        if (($handle = fopen($filePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 4096, ';')) !== false) {
                if ($rowNumber == 1) {
                    $rowNumber++;
                    continue;
                }

                $cities[] = $data[4];
                $organizationNames[] = $data[10];
            }
        }

        // nettoyage des tableaux
        $cities = array_unique($cities);
        $organizationNames = array_unique($organizationNames);

        // requete pour récupérer toutes nos structures qui ont associé un projet avec le programme fond vert (36)
        $organizations = $this->getOrganizationsFromDb();

        // on parcours les organisations pour les comparer avec les données du fichier
        $organizationsFinal = $this->getOrganizationsFinal($organizations, $cities, $organizationNames);

        // On écrit un csv à partir du résultat
        $tmpFolder = $this->fileService->getUploadTmpDir();
        if (!is_dir($tmpFolder)) {
            mkdir($tmpFolder, 0777, true);
        }

        $filename = 'fond-vert-2023-stats';
        $fileTarget = $tmpFolder . '/' . $filename . '.xlsx';

        $spreadSheet = new Spreadsheet();
        $sheet = $spreadSheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id_organization');
        $sheet->setCellValue('B1', 'name_organization');
        $sheet->setCellValue('C1', 'city_name');
        $sheet->setCellValue('D1', 'city_name_fv');
        $sheet->setCellValue('E1', 'organization_type');
        $sheet->setCellValue('F1', 'city_found');
        $sheet->setCellValue('G1', 'organization_found');

        $row = 2;
        foreach ($organizationsFinal as $organization) {
            $sheet->setCellValue('A'.$row, $organization['id_organization']);
            $sheet->setCellValue('B'.$row, $organization['name_organization']);
            $sheet->setCellValue('C'.$row, $organization['city_name']);
            $sheet->setCellValue('D'.$row, $organization['city_name_fv']);
            $sheet->setCellValue('E'.$row, $organization['organization_type']);
            $sheet->setCellValue('F'.$row, $organization['city_found'] ? 'Oui' : 'Non');
            $sheet->setCellValue('G'.$row, $organization['organization_found'] ? 'Oui' : 'Non');
            $row++;
        }

        // Filtre sur toutes les colonnes
        $sheet->setAutoFilter('A1:G1');

        $writer = new Xlsx($spreadSheet);
        $writer->save($fileTarget);

        // Envoi l'email
        $send = $this->emailService->sendEmail(
            'remi.barret@beta.gouv.fr',
            'Stats fond vert 2023',
            'emails/base.html.twig',
            [
                'subject' => 'Stats fond vert 2023',
                'body' => 'Votre export en pièce jointe',
            ],
            [
                'attachments' => [
                    $fileTarget
                ]
            ]
        );

        // Supprime le fichier
        @unlink($fileTarget);

        if (!$send) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getOrganizationsFromDb(): array
    {
        // requete pour récupérer toutes nos structures qui ont associé un projet avec le programme fond vert (36)
        $sql = "
            SELECT
            DISTINCT(o.id) as id_organization,
            o.name as name_organization,
            o.city_name,
            REPLACE(o.city_name, '-', ' ') AS city_name_fv,
            ot.name
            from organization o
            left join organization_type ot on o.organization_type_id = ot.id 
            inner join project p on o.id = p.organization_id
            inner join aid_project ap on ap.project_id = p.id
            inner join aid_program ap2 ON ap.aid_id = ap2.aid_id AND ap2.program_id = 36
            ;
        ";
        $stmt = $this->managerRegistry->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    private function getOrganizationsFinal(array $organizations, array $cities, array $organizationNames): array
    {
        $organizationsFinal = [];
        foreach ($organizations as $organization) {
            $cityFound = false;
            $organizationFound = false;

            if (in_array($organization['city_name_fv'], $cities)) {
                $cityFound = true;
            }

            if (in_array($organization['name_organization'], $organizationNames)) {
                $organizationFound = true;
            }

            $organizationsFinal[] = [
                'id_organization' => $organization['id_organization'],
                'name_organization' => $organization['name_organization'],
                'city_name' => $organization['city_name'],
                'city_name_fv' => $organization['city_name_fv'],
                'organization_type' => $organization['name'],
                'city_found' => $cityFound,
                'organization_found' => $organizationFound,
            ];
        }

        return $organizationsFinal;
    }
}
