<?php

namespace App\Command\Import\User;

use App\Command\Import\ImportCommand;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Entity\User\UserGroup;
use App\Entity\User\UserGroupPermission;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options;
#[AsCommand(name: 'at:import:user', description: 'Import users')]
class UserImportCommand extends ImportCommand
{
    protected string $commandTextStart = '>Import users';
    protected string $commandTextEnd = '<Import users';

    protected function import($input, $output)
    {
        // ==================================================================
        // USER GROUP
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('USER GROUP');

        $userGroupById = [];

        // fichier
        $filePath = $this->findCsvFile('auth_group_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = new UserGroup();
                $entity->setName((string) $cells[1]->getValue());

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                $this->managerRegistry->getManager()->flush();

                // alimente tableau
                $userGroupById[$entity->getId()] = $entity;

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // USER GROUP PERMISSIONS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('USER GROUP PERMISSIONS');

        $userGroupPemissionById = [];

        // fichier
        $filePath = $this->findCsvFile('auth_permission_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = new UserGroupPermission();
                $entity->setName((string) $cells[1]->getValue());
                $entity->setCodename((string) $cells[3]->getValue());

                // sauvegarde
                $this->managerRegistry->getManager()->persist($entity);
                $this->managerRegistry->getManager()->flush();

                // alimente le tableau
                $userGroupPemissionById[$entity->getId()] = $entity;

                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // LIAISON USER_GROUP / USER_GROUP_PERMISSION
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LIAISON USER_GROUP / USER_GROUP_PERMISSION');

        $userGroupPemissionById = [];

        // fichier
        $filePath = $this->findCsvFile('auth_group_permissions_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // progressbar
        $io->createProgressBar($nbToImport);

        // starts and displays the progress bar
        $io->progressStart();
        
        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                // entite
                $entity = $userGroupById[(int) $cells[0]->getValue()] ?? null;
                $entityTarget = $userGroupPemissionById[(int) $cells[1]->getValue()] ?? null;
                if ($entity instanceof UserGroup && $entityTarget instanceof UserGroupPermission) {
                    $entity->addUserGroupPermission($entityTarget);
                    $this->managerRegistry->getManager()->persist($entity);
                }

                // sauvegarde
                $this->managerRegistry->getManager()->flush();
                
                // advances the progress bar 1 unit
                $io->progressAdvance();
            }
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // Nettoyage
        // ==================================================================

        unset($userGroupPemissionById);

        // ==================================================================
        // USER
        // ==================================================================

        // on recupère les périmètres
        $perimeters = $this->managerRegistry->getRepository(Perimeter::class)->findAll();
        $perimetersById = [];
        foreach ($perimeters as $perimeter) {
            $perimetersById[$perimeter->getId()] = $perimeter;
        }
        unset($perimeters);

        // on recupère les organizations
        $organizations = $this->managerRegistry->getRepository(Organization::class)->findAll();
        $organizationsById = [];
        foreach ($organizations as $organization) {
            $organizationsById[$organization->getId()] = $organization;
        }
        unset($organizations);

        $io = new SymfonyStyle($input, $output);
        $io->info('Users');

        $userGroupPemissionById = [];

        // fichier
        $filePath = $this->findCsvFile('accounts_user_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        $nbByBatch = 2000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();

        $sqlBase = "INSERT INTO `user`
        (
        `id`,
        perimeter_id,
        invitation_author_id,
        proposed_organization_id,
        email,
        firstname,
        lastname,
        is_beneficiary,
        is_contributor,
        roles,
        `password`,
        is_certified,
        ml_consent,
        `image`,
        time_last_login,
        date_last_login,
        time_create,
        date_create,
        time_update,
        invitation_time,
        time_join_organization,
        acquisition_channel,
        acquisition_channel_comment,
        notification_counter,
        notification_email_frequency,
        contributor_contact_phone,
        contributor_organization,
        contributor_role,
        beneficiary_function,
        beneficiary_role
        )
        VALUES ";

        $sql = $sqlBase;
        $sqlParams = [];
        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);


        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $sql .= "
                (
                    :id".$rowNumber.",
                    :perimeter_id".$rowNumber.",
                    :invitation_author_id".$rowNumber.",
                    :proposed_organization_id".$rowNumber.",
                    :email".$rowNumber.",
                    :firstname".$rowNumber.",
                    :lastname".$rowNumber.",
                    :is_beneficiary".$rowNumber.",
                    :is_contributor".$rowNumber.",
                    :roles".$rowNumber.",
                    :password".$rowNumber.",
                    :is_certified".$rowNumber.",
                    :ml_consent".$rowNumber.",
                    :image".$rowNumber.",
                    :time_last_login".$rowNumber.",
                    :date_last_login".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber.",
                    :time_update".$rowNumber.",
                    :invitation_time".$rowNumber.",
                    :time_join_organization".$rowNumber.",
                    :acquisition_channel".$rowNumber.",
                    :acquisition_channel_comment".$rowNumber.",
                    :notification_counter".$rowNumber.",
                    :notification_email_frequency".$rowNumber.",
                    :contributor_contact_phone".$rowNumber.",
                    :contributor_organization".$rowNumber.",
                    :contributor_role".$rowNumber.",
                    :beneficiary_function".$rowNumber.",
                    :beneficiary_role".$rowNumber."                  
                ),";



                $sqlParams['id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams["password".$rowNumber] = (string) $cells[1]->getValue();
                try {
                    $timeLastLogin = new \DateTime(date((string) $cells[2]->getValue()));
                } catch (\Exception $exception) {
                }
                try {
                    $timeLastLogin = new \DateTime(date((string) $cells[2]->getValue()));
                } catch (\Exception $exception) {
                }
                $sqlParams["time_last_login".$rowNumber] = $timeLastLogin ? $timeLastLogin->format('Y-m-d H:i:s') : null;
                $sqlParams["date_last_login".$rowNumber] = $timeLastLogin ? $timeLastLogin->format('Y-m-d') : null;

                $roles = [User::ROLE_USER];
                $isSuperUser = (string) $cells[3]->getValue() == 'false' ? false : true;
                if ($isSuperUser) {
                    $roles[] = User::ROLE_ADMIN;
                }
                $strRoles = '[';
                for ($i = 0; $i < count($roles); $i++) {
                    $strRoles .= '"' . $roles[$i] . '"';
                    if ($i < count($roles) - 1) {
                        $strRoles .= ',';
                    }
                }
                $strRoles .= ']';
                $sqlParams["roles".$rowNumber] = $strRoles;
                $sqlParams['email'.$rowNumber] = (string) $cells[4]->getValue();
                $sqlParams['lastname'.$rowNumber] = (string) $cells[5]->getValue();

                try {
                    $timeCreate = new \DateTime(date((string) $cells[6]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams["time_create".$rowNumber] = $timeCreate ? $timeCreate->format('Y-m-d H:i:s') : null;
                $sqlParams["date_create".$rowNumber] = $timeCreate ? $timeCreate->format('Y-m-d') : null;

                $sqlParams['contributor_contact_phone'.$rowNumber] = $this->stringOrNull((string) $cells[7]->getValue());
                $sqlParams['contributor_organization'.$rowNumber] = $this->stringOrNull((string) $cells[8]->getValue());
                $sqlParams['contributor_role'.$rowNumber] = $this->stringOrNull((string) $cells[9]->getValue());
                $sqlParams['is_certified'.$rowNumber] = $this->stringToBool((string) $cells[10]->getValue());
                $sqlParams['ml_consent'.$rowNumber] = $this->stringToBool((string) $cells[11]->getValue());
                $sqlParams['firstname'.$rowNumber] = (string) $cells[12]->getValue();
                $sqlParams['is_contributor'.$rowNumber] = $this->stringToBool((string) $cells[13]->getValue());
                try {
                    $timeUpdate = new \DateTime(date((string) $cells[14]->getValue()));
                } catch (\Exception $exception) {
                    $timeUpdate = null;
                }
                $sqlParams['time_update'.$rowNumber] = $timeUpdate ? $timeUpdate->format('Y-m-d H:i:s') : null;
                $sqlParams['perimeter_id'.$rowNumber] = isset($perimetersById[(int) $cells[15]->getValue()]) ? (int) $cells[15]->getValue() : null;
                $sqlParams['beneficiary_function'.$rowNumber] = $this->stringOrNull((string) $cells[16]->getValue());
                $sqlParams['beneficiary_role'.$rowNumber] = $this->stringOrNull((string) $cells[18]->getValue());
                $sqlParams['is_beneficiary'.$rowNumber] = $this->stringToBool((string) $cells[19]->getValue());
                $sqlParams['image'.$rowNumber] = $this->stringOrNull((string) $cells[20]->getValue());
                $sqlParams['proposed_organization_id'.$rowNumber] = isset($organizationsById[(int) $cells[21]->getValue()]) ? (int) $cells[21]->getValue() : null;
                $sqlParams['invitation_author_id'.$rowNumber] = isset($usersById[(int) $cells[22]->getValue()]) ? (int) $cells[22]->getValue() : null;

                try {
                    $timeInvitation = new \DateTime(date((string) $cells[23]->getValue()));
                } catch (\Exception $exception) {
                    $timeInvitation = null;
                }
                $sqlParams['invitation_time'.$rowNumber] = $timeInvitation ? $timeInvitation->format('Y-m-d H:i:s') : null;

                try {
                    $timeJoinOrganization = new \DateTime(date((string) $cells[24]->getValue()));
                } catch (\Exception $exception) {
                    $timeJoinOrganization = null;
                }
                $sqlParams['time_join_organization'.$rowNumber] = $timeJoinOrganization ? $timeJoinOrganization->format('Y-m-d H:i:s') : null;
                $sqlParams['acquisition_channel'.$rowNumber] = $this->stringOrNull((string) $cells[25]->getValue());
                $sqlParams['acquisition_channel_comment'.$rowNumber] = $this->stringOrNull((string) $cells[26]->getValue());
                $sqlParams['notification_counter'.$rowNumber] = (int) $cells[27]->getValue();
                $sqlParams['notification_email_frequency'.$rowNumber] = (string) $cells[28]->getValue();

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }
            }
        }

        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }

        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // tableau des users par id
        // ==================================================================

        $usersById = [];
        $users = $this->managerRegistry->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }
        unset($users);

        // ==================================================================
        // LIBERE MEMOIRES
        // ==================================================================

        unset($perimetersById);
        unset($organizationsById);
        
        // ==================================================================
        // USER LOGINS
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('Users LOGINS');

        $userGroupPemissionById = [];

        // fichier
        $filePath = $this->findCsvFile('accounts_userlastconnexion_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `log_user_login`
        (
        user_id,
        `action`,
        time_create,
        date_create
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];
        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);


        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $sql .= "
                (
                    :user_id".$rowNumber.",
                    :action".$rowNumber.",
                    :time_create".$rowNumber.",
                    :date_create".$rowNumber."         
                ),";

                $sqlParams['user_id'.$rowNumber] = (int) $cells[2]->getValue();
                try {
                    $timeCreate = new \DateTime(date((string) $cells[1]->getValue()));
                } catch (\Exception $exception) {
                    $timeCreate = new \DateTime(date('Y-m-d H:i:s'));
                }
                $sqlParams["time_create".$rowNumber] = $timeCreate ? $timeCreate->format('Y-m-d H:i:s') : null;
                $sqlParams["date_create".$rowNumber] = $timeCreate ? $timeCreate->format('Y-m-d') : null;
                $sqlParams['action'.$rowNumber] = 'login';

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }
            }
        }

        // sauvegarde
        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();

        // ==================================================================
        // LIAISON USER / GROUP
        // ==================================================================

        $io = new SymfonyStyle($input, $output);
        $io->info('LIAISON USER / GROUP');

        $userGroupPemissionById = [];

        // fichier
        $filePath = $this->findCsvFile('accounts_user_groups_');
        if (!$filePath) {
            throw new \Exception('File missing');
        }
        // ouverture du fichier
        $options = new Options();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '"';
        $reader = new Reader($options);
        $reader->open($filePath);

        // estime le nombre de lignes à importé
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $nbToImport = $file->key() + 1;
        unset($file);

        // batch
        $nbByBatch = 10000;
        $nbBatch = ceil($nbToImport / $nbByBatch);

        // progressbar
        $io->createProgressBar($nbBatch);

        // starts and displays the progress bar
        $io->progressStart();
        
        $sqlBase = "INSERT INTO `user_group_user`
        (
        user_group_id,
        user_id
        )
        VALUES ";
        $sql = $sqlBase;
        $sqlParams = [];
        $this->managerRegistry->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);


        // importe les lignes
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                if ($rowNumber == 1) {
                    continue;
                }
                // do stuff with the row
                $cells = $row->getCells();

                $sql .= "
                (
                    :user_group_id".$rowNumber.",
                    :user_id".$rowNumber."     
                ),";

                $sqlParams['user_group_id'.$rowNumber] = (int) $cells[0]->getValue();
                $sqlParams['user_id'.$rowNumber] = (int) $cells[1]->getValue();

                if ($rowNumber % $nbByBatch == 0) {
                    $sql = substr($sql, 0, -1);

                    $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
                    $stmt->execute($sqlParams);

                    $sqlParams = [];
                    $sql = $sqlBase;

                    // advances the progress bar 1 unit
                    $io->progressAdvance();
                }
            }
        }

        // sauvegarde
        if (count($sqlParams) > 0) {
            $sql = substr($sql, 0, -1);
            $stmt = $this->managerRegistry->getManager()->getConnection()->prepare($sql);
            $stmt->execute($sqlParams);
        }
        
        // ensures that the progress bar is at 100%
        $io->progressFinish();


        // ==================================================================
        // success
        // ==================================================================
        $io->success('import ok');
    }

}