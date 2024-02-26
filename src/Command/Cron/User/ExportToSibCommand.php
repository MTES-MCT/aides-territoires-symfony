<?php

namespace App\Command\Cron\User;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use App\Service\Email\EmailService;
use App\Service\Various\ParamService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'at:cron:user:export_to_sib', description: 'Export les utilisateurs vers le système de mailing (SIB)')]
class ExportToSibCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Cron';
    protected string $commandTextEnd = '>Cron';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry,
        protected ParamService $paramService,
        protected EmailService $emailService
    )
    {
        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit', '1G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            if ($this->kernelInterface->getEnvironment() != 'prod') {
                throw new \Exception('Commande uniquement disponible en prod');
            }
            // generate menu
            $this->cronTask($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function cronTask($input, $output)
    {
        $io = new SymfonyStyle($input, $output);

        // charge les utilisateurs connectés au moins 1 fois
        $users = $this->managerRegistry->getRepository(User::class)->findUsersConnectedSinceYesterday();

        $nbOk = 0;
        $nbError = 0;
        $errors = [];
        /** @var User $user */
        foreach ($users as $user) {
            $aids = $user->getAids();
            $nbActive = 0;
            $nbAidsDraft = 0;
            $nbExpired = 0;
            foreach ($aids as $aid) {
                if ($aid->isPublished()) {
                    $nbActive++;
                }
                if ($aid->getStatus() == Aid::STATUS_DRAFT) {
                    $nbAidsDraft++;
                }
                if ($aid->hasExpired()) {
                    $nbExpired++;
                }
            }

            // les attrbitus que l'on va envoyé à SIB
            $attributes = [
                "PRENOM" =>  $user->getFirstname(),
                "NOM" => $user->getLastname(),
                "NOMBRE_AIDES_ACTIVES" => $nbActive,
                "DATE_CREATION_COMPTE" => $user->getTimeCreate()->format(DateTime::ATOM),
                "NOMBRE_AIDES_BROUILLONS" =>  $nbAidsDraft,
                "NOMBRE_AIDES_AFICHEES" => $user->getNbAidsLive(),
                "NOMBRE_AIDES_EXPIREES" => $nbExpired,
                "DATE_DERNIERE_AIDE_PUBLIEE" => null,
                "DATE_DERNIER_BROUILLON" => null,
                "DATE_DERNIERE_EXPIRATION" => null,
            ];

            if ($user->getLastestAidPublished() instanceof Aid) {
                $attributes['DATE_DERNIERE_AIDE_PUBLIEE'] = $user->getLastestAidPublished()->getTimePublished()->format(DateTime::ATOM);
            }
            if ($user->getLastestAidDraft() instanceof Aid) {
                $attributes['DATE_DERNIER_BROUILLON'] = $user->getLastestAidDraft()->getTimeCreate()->format(DateTime::ATOM);
            }
            if ($user->getLatestAidExpired() instanceof Aid) {
                $attributes['DATE_DERNIERE_EXPIRATION'] = $user->getLatestAidExpired()->getDateSubmissionDeadline()->format(DateTime::ATOM);
            }

            $update = $this->emailService->updateUser(
                $user,
                [
                    'attributes' => $attributes,
                    'lislistIdsts' => explode(',', $this->paramService->get('sib_export_contacts_list_id')),
                    // 'updateEnabled' => true // a priori n'existe plus
                ]
            );

            if ($update) {
                $nbOk++;
            } else {
                $errors[] = $user->getEmail();
                $nbError++;
            }
        }

        if (count($errors) > 0) {
            // envoi un mail à l'admin
            $this->emailService->sendEmail(
                $this->paramService->get('email_super_admin'),
                $nbError. ' utilisateurs non envoyés à SIB',
                'emails/cron/user/errors_export_sib.html.twig',
                [
                    'errors' => $errors,
                ]
            );
        }

        // success
        $io->success('Envoi de '.count($users).' à l\'api. OK : '.$nbOk.' / Erreur : '.$nbError);
        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
    }
}