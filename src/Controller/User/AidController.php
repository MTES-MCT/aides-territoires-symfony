<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\User\User;
use App\Form\Aid\AidDeleteType;
use App\Form\Aid\AidEditType;
use App\Form\User\Aid\AidExportType;
use App\Form\User\Aid\AidFilterType;
use App\Form\User\Aid\AidStatsPeriodType;
use App\Repository\Aid\AidProjectRepository;
use App\Repository\Aid\AidRepository;
use App\Repository\Log\LogAidApplicationUrlClickRepository;
use App\Repository\Log\LogAidOriginUrlClickRepository;
use App\Repository\Log\LogAidViewRepository;
use App\Service\Aid\AidService;
use App\Service\User\UserService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AidController extends FrontController
{
    #[Route('/comptes/aides/publier/', name: 'app_user_aid_detail_publish')]
    public function publish(
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry
    ) : Response {
        // le user
        $user = $userService->getUserLogged();

        // formulaire création aide
        $aid = new Aid();
        $formAid = $this->createForm(AidEditType::class, $aid);
        $formAid->handleRequest($requestStack->getCurrentRequest());
        if ($formAid->isSubmitted()) {
            if ($formAid->isValid()) {
                $status = $requestStack->getCurrentRequest()->get('_status');
                if (!in_array($status, [Aid::STATUS_REVIEWABLE, Aid::STATUS_DRAFT])) {
                    $status = Aid::STATUS_DRAFT;
                }
                
                $aid->setStatus($status);
                $aid->setAuthor($user);
                // les financers
                $financers = $formAid->get('financers')->getData();
                foreach ($financers as $financer) {
                    $aidFinancer = new AidFinancer();
                    $aidFinancer->setBacker($financer);
                    $aid->addAidFinancer($aidFinancer);
                }
                // les instructors
                $instructors = $formAid->get('instructors')->getData();
                foreach ($instructors as $instructor) {
                    $aidInstructor = new AidInstructor();
                    $aidInstructor->setBacker($instructor);
                    $aid->addAidInstructor($aidInstructor);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($aid);
                $managerRegistry->getManager()->flush();

                // message
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre aide a été créée. Vous pouvez poursuivre l’édition ou <a href="'.$this->generateUrl('app_aid_aid_details', ['slug' => $aid->getSlug()]).'" target="_blank">la prévisualiser <span class="fr-sr-only">Ouvre une nouvelle fenêtre</span></a>.'
                );

                // redirection
                return $this->redirectToRoute('app_user_aid_edit', ['slug' => $aid->getSlug()]);
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Nous n’avons pas pu traiter votre formulaire car les données saisies sont invalides et / ou incomplètes. Merci de bien vouloir vérifier votre saisie et corriger les erreurs avant de réessayer. '
                );
            }
        }

        // rendu template
        return $this->render('user/aid/publish.html.twig', [
            'no_breadcrumb' => true,
            'formAid' => $formAid->createView(),
            'aid' => $aid
        ]);
    }


    #[Route('/comptes/aides/publications/', name: 'app_user_aid_publications')]
    public function publications(
        UserService $userService,
        AidRepository $aidRepository,
        LogAidViewRepository $logAidViewRepository,
        RequestStack $requestStack
    ) : Response {
        // le user
        $user = $userService->getUserLogged();

        // paramètre filtre aides
        $aidsParams = [
            'author' => $user
        ];

        // formulaire filtre aides
        $formAidFilter = $this->createForm(AidFilterType::class, null, [
            'action' => '#aids-filters',
            'attr' => [
                'id' => 'aids-filters'
            ]
        ]);
        $formAidFilter->handleRequest($requestStack->getCurrentRequest());
        if ($formAidFilter->isSubmitted()) {
            if ($formAidFilter->isValid()) {
                if ($formAidFilter->get('state')->getData()) {
                    $aidsParams['state'] = $formAidFilter->get('state')->getData();
                }
                if ($formAidFilter->get('statusDisplay')->getData()) {
                    $aidsParams['statusDisplay'] = $formAidFilter->get('statusDisplay')->getData();
                }
            }
        }

        // formulaire export aide
        $formExport = $this->createForm(AidExportType::class, null, [
            'action' => $this->generateUrl('app_user_aids_export'),
            'attr' => [
                'target' => '_blank'
            ]
        ]);

        // les aides du user
        $aids = $aidRepository->findCustom($aidsParams);

        // nb aides publiées
        $nbAidsLive = $aidRepository->countByUser(
            $user,
            [
                'showInSearch' => true
            ]
        );

        
        // nb vues des aides du user
        $nbAidsViews = $logAidViewRepository->countCustom(
            [
                'author' => $user,
            ]
        );
        foreach ($aids as $aid) {
            $aid->setNbViews(
                $logAidViewRepository->countCustom(
                    [
                        'aid' => $aid
                    ]
                )
            );
        }

        // nb vues des aides du user sur les 30 derniers jours
        $lastMonth = new \DateTime(date('Y-m-d'));
        $lastMonth->sub(new \DateInterval('P30D'));

        $nbAidsViewsMonth = $logAidViewRepository->countCustom(
            [
                'author' => $user,
                'dateMin' => $lastMonth
            ]
        );

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Mon portefeuille d’aides'
        );

        // rendu template
        return $this->render('user/aid/publications.html.twig', [
            'aids' => $aids,
            'nbAidsLive' => $nbAidsLive,
            'nbAidsViews' => $nbAidsViews,
            'nbAidsViewsMonth' => $nbAidsViewsMonth,
            'formAidFilter' => $formAidFilter->createView(),
            'formExport' => $formExport->createView()
        ]);
    }

    #[Route('/comptes/aides/publications/{slug}', name: 'app_user_aid_edit', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function edit(
        $slug,
        UserService $userService,
        AidService $aidService,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        AidRepository $aidRepository,
        HtmlSanitizerInterface $htmlSanitizerInterface
    ) : Response {
        // user
        $user = $userService->getUserLogged();

        // charge aide
        $aid = $aidRepository->findOneBy([
            'slug' => $slug
        ]);
        if (!$aid instanceof Aid) {
            throw new NotFoundException('Cette aide n\'existe pas');
        }

        // verifie que l'aide appartienne à l'utilisateur ou que l'utilisateur est un admin
        if (!$aidService->userCanEdit($aid, $user)) {
            return $this->redirectToRoute('app_user_aid_publications');
        }

        // regarde si aide(s) avec meme originUrl
        $aidDuplicates = [];
        if ($aid->getOriginUrl()) {
            $aidDuplicates = $aidRepository->findCustom(
                [
                    'originUrl' => $aid->getOriginUrl(),
                    'exclude' => $aid
                ]
            );
        }
        // formulaire suppression
        $formDelete = $this->createForm(AidDeleteType::class);
        $formDelete->handleRequest($requestStack->getCurrentRequest());
        if ($formDelete->isSubmitted()) {
            if ($formDelete->isValid()) {
                // suppression aide
                $managerRegistry->getManager()->remove($aid);
                $managerRegistry->getManager()->flush();

                // message
                $this->tAddFlash(
                    FrontController::FLASH_SUCCESS,
                    'Votre aide a été supprimée.'
                );

                // redirection
                $this->redirectToRoute('app_user_aid_publications');
            }
        }

        // formulaire edition
        $formAid = $this->createForm(AidEditType::class, $aid, ['allowStatusPublished' => true]);
        $formAid->handleRequest($requestStack->getCurrentRequest());
        if ($formAid->isSubmitted()) {
            if ($formAid->isValid()) {
                // les financers
                foreach ($aid->getAidFinancers() as $aidFinancer) {
                    $aid->removeAidFinancer($aidFinancer);
                }
                $financers = $formAid->get('financers')->getData();
                foreach ($financers as $financer) {
                    $aidFinancer = new AidFinancer();
                    $aidFinancer->setBacker($financer);
                    $aid->addAidFinancer($aidFinancer);
                }
                // les instructors
                foreach ($aid->getAidInstructors() as $aidInstructor) {
                    $aid->removeAidInstructor($aidInstructor);
                }
                $instructors = $formAid->get('instructors')->getData();
                foreach ($instructors as $instructor) {
                    $aidInstructor = new AidInstructor();
                    $aidInstructor->setBacker($instructor);
                    $aid->addAidInstructor($aidInstructor);
                }

                // sauvegarde
                $managerRegistry->getManager()->persist($aid);
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'L’aide a bien été mise à jour. Vous pouvez poursuivre l’édition.'
                );

                // redirection
                return $this->redirectToRoute('app_user_aid_edit', ['slug' => $aid->getSlug()]);
            } else {
                // message
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Nous n’avons pas pu traiter votre formulaire car les données saisies sont invalides et / ou incomplètes. Merci de bien vouloir vérifier votre saisie et corriger les erreurs avant de réessayer. '
                );
            }
        }

        // rendu template
        return $this->render('user/aid/edit.html.twig', [
            'no_breadcrumb' => true,
            'formDelete' => $formDelete->createView(),
            'formAid' => $formAid->createView(),
            'aid' => $aid,
            'aidDuplicates' => $aidDuplicates
        ]);
    }

    #[Route('/comptes/aides/exporter-aides/', name: 'app_user_aids_export')]
    public function aidsExport(
        UserService $userService,
        RequestStack $requestStack,
        AidRepository $aidRepository,
        AidService $aidService,
        StringService $stringService,
        RouterInterface $routerInterface
    )
    {
        // le user
        $user = $userService->getUserLogged();

        // le formulaire
        $formExport = $this->createForm(AidExportType::class);
        $formExport->handleRequest($requestStack->getCurrentRequest());
        if ($formExport->isSubmitted()) {
            if ($formExport->isValid()) {
                // les aides
                $aids = $aidRepository->findCustom(
                    [
                        'author' => $user
                    ]
                );

                // alimente la réponse
                $response = $this->getExportStreamedResponse(
                    $formExport->get('format')->getData(),
                    $aids,
                    $user,
                    $stringService,
                    $routerInterface
                );
            }
        }

        return $response;
    }

    private function getExportStreamedResponse(
        string $format,
        array $aids,
        User $user,
        StringService $stringService,
        RouterInterface $routerInterface
    ): StreamedResponse
    {
        // nom de fichier
        $today = new \DateTime(date('Y-m-d'));
        $organizationName = $user->getDefaultOrganization() ? $stringService->getSLug($user->getDefaultOrganization()->getName()) : '';
        $filename = 'Aides-territoires-'.$today->format('d_m_Y').'-'.$organizationName;

        
        // alimente la réponse
        if ($format == 'pdf') {
            $pdfOptions = new Options();
            $pdfOptions->setIsRemoteEnabled(true);
    
            // instantiate and use the dompdf class
            $dompdf = new Dompdf($pdfOptions);
    
            $dompdf->loadHtml(
                $this->renderView('user/aid/aids_export_pdf.html.twig', [
                    'aids' => $aids,
                    'organization' => $user->getDefaultOrganization() ?? null
                ])
            );
    
            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
    
            // Render the HTML as PDF
            $dompdf->render();
    
            // Output the generated PDF to Browser (inline view)
            $response = $dompdf->stream($filename.'.pdf', [
                "Attachment" => false
            ]);
        } elseif (in_array($format, ['csv', 'xlsx'])) {
            $response = new StreamedResponse(function () use ($aids, $routerInterface) {
                $csv = fopen('php://output', 'w+');
                fputcsv($csv, [
                    'Adresse de la fiche aide',
                    'Nom',
                    'Description complète de l’aide et de ses objectifs',
                    'Exemples de projets réalisables',
                    'État d’avancement du projet pour bénéficier du dispositif',
                    'Types d’aide',
                    'Types de dépenses / actions couvertes',
                    'Date d’ouverture',
                    'Date de clôture',
                    'Taux de subvention, min. et max. (en %, nombre entier)',
                    'Taux de subvention (commentaire optionnel)',
                    'Montant de l’avance récupérable',
                    'Montant du prêt maximum',
                    'Autre aide financière (commentaire optionnel)',
                    'Contact',
                    'Récurrence',
                    'Appel à projet / Manifestation d’intérêt',
                    'Sous-thématiques',
                    'Porteurs d’aides',
                    'Instructeurs',
                    'Programmes',
                ]);
                /** @var Aid $aid */
                foreach ($aids as $aid) {
                    $aidStepsString = '';
                    if ($aid->getAidSteps()) {
                        $i=0;
                        foreach ($aid->getAidSteps() as $aidStep) {
                            $aidStepsString = $aidStep->getName();
                            if ($i < count($aid->getAidSteps()) -1) {
                                $aidStepsString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $aidTypesString = '';
                    if ($aid->getAidTypes()) {
                        $i=0;
                        foreach ($aid->getAidTypes() as $aidType) {
                            $aidTypesString .= $aidType->getName();
                            if ($i < count($aid->getAidTypes()) -1) {
                                $aidTypesString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $aidDestinationsString = '';
                    if ($aid->getAidDestinations()) {
                        $i=0;
                        foreach ($aid->getAidDestinations() as $aidDestination) {
                            $aidDestinationsString .= $aidDestination->getName();
                            if ($i < count($aid->getAidDestinations()) -1) {
                                $aidDestinationsString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $subventionRatesString = '';
                    if ($aid->getSubventionRateMin()) {
                        $subventionRatesString .= 'Min : '.$aid->getSubventionRateMin().' ';
                    }
                    if ($aid->getSubventionRateMax()) {
                        $subventionRatesString .= 'Max : '.$aid->getSubventionRateMax();
                    }

                    $categoriesString = '';
                    if ($aid->getCategories()) {
                        $i=0;
                        foreach ($aid->getCategories() as $category) {
                            $categoriesString .= $category->getName();
                            if ($i < count($aid->getCategories()) -1) {
                                $categoriesString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $aidFinancersString = '';
                    if ($aid->getAidFinancers()) {
                        $i=0;
                        foreach ($aid->getAidFinancers() as $aidFinancer) {
                            $aidFinancersString .= $aidFinancer->getBacker() ? $aidFinancer->getBacker()->getName() : '';
                            if ($i < count($aid->getAidFinancers()) -1) {
                                $aidFinancersString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $aidInstructorsString = '';
                    if ($aid->getAidInstructors()) {
                        $i=0;
                        foreach ($aid->getAidInstructors() as $aidInstructor) {
                            $aidInstructorsString .= $aidInstructor->getBacker() ? $aidInstructor->getBacker()->getName() : '';
                            if ($i < count($aid->getAidInstructors()) -1) {
                                $aidInstructorsString .= ', ';
                            }
                            $i++;
                        }
                    }

                    $programsString = '';
                    if ($aid->getPrograms()) {
                        $i=0;
                        foreach ($aid->getPrograms() as $program) {
                            $programsString .= $program->getName();
                            if ($i < count($aid->getPrograms()) -1) {
                                $programsString .= ', ';
                            }
                            $i++;
                        }
                    }

                    fputcsv($csv, [
                        $routerInterface->generate('app_aid_aid_details', ['slug' => $aid->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                        $aid->getName(),
                        $aid->getDescription(),
                        $aid->getProjectExamples(),
                        $aidStepsString,
                        $aidTypesString,
                        $aidDestinationsString,
                        $aid->getDateStart() ? $aid->getDateStart()->format('d/m/Y') : '',
                        $aid->getDateSubmissionDeadline() ? $aid->getDateSubmissionDeadline()->format('d/m/Y') : '',
                        $subventionRatesString,
                        $aid->getSubventionComment() ?? '',
                        $aid->getRecoverableAdvanceAmount() ?? '',
                        $aid->getLoanAmount() ?? '',
                        $aid->getOtherFinancialAidComment() ?? '',
                        $aid->getContact() ?? '',
                        $aid->getAidRecurrence() ? $aid->getAidRecurrence()->getName() : '',
                        $aid->isIsCallForProject() ? 'Oui' : 'Non',
                        $categoriesString,
                        $aidFinancersString,
                        $aidInstructorsString,
                        $programsString
                    ]);
                }
                fclose($csv);
            });
        } else {
            return new StreamedResponse(function() {
                echo 'Format invalide';
            });
        }

        // header selon format demandé
        switch ($format) {
            case 'csv':
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'.csv"');
                break;

            case 'xlsx':
                $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'.xlsx"');
                break;

            case 'pdf':
                break;
        }


        return $response;
    }

    #[Route('/comptes/aides/exporter-aide-en-pdf/{slug}/', name: 'app_user_aid_export_pdf', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function aidExport(
        $slug,
        UserService $userService,
        AidRepository $aidRepository,
        AidService $aidService
    )
    {
        // le user
        $user = $userService->getUserLogged();

        $aid = $aidRepository->findOneBy(
            [
                'slug' => $slug
            ]
            );
        if (!$aid instanceof Aid) {
            throw new NotFoundException('Cette aide n\'existe pas');
        }
        if (!$aidService->userCanExportPdf($aid, $user)) {
            throw $this->createNotFoundException('Cette aide n\'existe pas');
        }

        $pdfOptions = new Options();
        $pdfOptions->setIsRemoteEnabled(true);

        // instantiate and use the dompdf class
        $dompdf = new Dompdf($pdfOptions);

        $dompdf->loadHtml(
            $this->renderView('user/aid/aid_export_pdf.html.twig', [
                'aid' => $aid
            ])
        );

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (inline view)
        $dompdf->stream('aide.pdf', [
            "Attachment" => false
        ]);
    }

    #[Route('/comptes/aides/dupliquer/{slug}/', name: 'app_user_aid_duplicate', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function duplicateAid(
        $slug,
        AidRepository $aidRepository,
        UserService $userService,
        AidService $aidService,
        ManagerRegistry $managerRegistry
    ) {
        // le user
        $user = $userService->getUserLogged();

        // l'aide
        $aid = $aidRepository->findOneBy([
            'author' => $user,
            'slug' => $slug
        ]);
        if (!$aid instanceof Aid) {
            throw new NotFoundHttpException('Cette aide n\'exite pas');
        }

        if (!$aidService->userCanDuplicate($aid, $user)) {
            throw new NotFoundHttpException('Cette aide n\'exite pas');
        }

        // limlitation des champs à ce qui était fait dans Django
        $newAid = new Aid();
        $newAid->setName($aid->getName());
        // $newAid->setDescription($aid->getDescription());
        $newAid->setStatus(Aid::STATUS_DRAFT);
        // $newAid->setOriginUrl($aid->getOriginUrl());
        // foreach ($aid->getAidAudiences() as $aidAudience) {
        //     $newAid->addAidAudience($aidAudience);
        // }
        // foreach ($aid->getAidTypes() as $aidType) {
        //     $newAid->addAidType($aidType);
        // }
        // foreach ($aid->getAidDestinations() as $aidDestination) {
        //     $newAid->addAidDestination($aidDestination);
        // }
        // $newAid->setDateStart($aid->getDateStart());
        // $newAid->setDatePredeposit($aid->getDatePredeposit());
        // $newAid->setDateSubmissionDeadline($aid->getDateSubmissionDeadline());
        // $newAid->setContactEmail($aid->getContactEmail());
        // $newAid->setContactPhone($aid->getContactPhone());
        // $newAid->setContactDetail($aid->getContactDetail());
        $newAid->setAuthor($aid->getAuthor());
        // foreach ($aid->getAidSteps() as $aidStep) {
        //     $newAid->addAidStep($aidStep);
        // }
        // $newAid->setEligibility($aid->getEligibility());
        // $newAid->setAidRecurrence($aid->getAidRecurrence());
        // $newAid->setPerimeter($aid->getPerimeter());
        // $newAid->setApplicationUrl($aid->getApplicationUrl());
        // foce à false
        $newAid->setIsImported(false);
        // force a null
        $newAid->setImportUniqueid(null);
        // $newAid->setFinancerSuggestion($aid->getFinancerSuggestion());
        // $newAid->setImportDataUrl($aid->getImportDataUrl());
        // $newAid->setDateImportLastAccess($aid->getDateImportLastAccess());
        // $newAid->setImportShareLicence($aid->getImportShareLicence());
        // $newAid->setIsCallForProject($aid->isIsCallForProject());
        // $newAid->setAmendedAid($aid->getAmendedAid());
        // $newAid->setIsAmendment($aid->isIsAmendment());
        // $newAid->setAmendmentAuthorName($aid->getAmendmentAuthorName());
        // $newAid->setAmendmentComment($aid->getAmendmentComment());
        // $newAid->setAmendmentAuthorEmail($aid->getAmendmentAuthorEmail());
        // $newAid->setAmendmentAuthorOrg($aid->getAmendmentAuthorOrg());
        // $newAid->setSubventionRateMin($aid->getSubventionRateMin());
        // $newAid->setSubventionRateMax($aid->getSubventionRateMax());
        // $newAid->setSubventionComment($aid->getSubventionComment());
        // $newAid->setContact($aid->getContact());
        // $newAid->setInstructorSuggestion($aid->getInstructorSuggestion());
        // $newAid->setProjectExamples($aid->getProjectExamples());
        // $newAid->setPerimeterSuggestion($aid->getPerimeterSuggestion());
        // $newAid->setShortTitle($aid->getShortTitle());
        // $newAid->setInFranceRelance($aid->isInFranceRelance());
        // $newAid->setGenericAid($aid->getGenericAid());
        // $newAid->setLocalCharacteristics($aid->getLocalCharacteristics());
        // $newAid->setImportDataSource($aid->getImportDataSource());
        // $newAid->setEligibilityTest($aid->getEligibilityTest());
        // $newAid->setIsGeneric($aid->isIsGeneric());
        // $newAid->setImportRawObject($aid->getImportRawObject());
        // $newAid->setLoanAmount($aid->getLoanAmount());
        // $newAid->setOtherFinancialAidComment($aid->getOtherFinancialAidComment());
        // $newAid->setRecoverableAdvanceAmount($aid->getRecoverableAdvanceAmount());
        // $newAid->setNameInitial($aid->getNameInitial());
        // $newAid->setAuthorNotification($aid->isAuthorNotification());
        // $newAid->setImportRawObjectCalendar($aid->getImportRawObjectCalendar());
        // $newAid->setImportRawObjectTemp($aid->getImportRawObjectTemp());
        // $newAid->setImportRawObjectTempCalendar($aid->getImportRawObjectTempCalendar());
        // $newAid->setEuropeanAid($aid->getEuropeanAid());
        // $newAid->setImportDataMention($aid->getImportDataMention());
        // $newAid->setHasBrokenLink($aid->isHasBrokenLink());
        // $newAid->setIsCharged($aid->isIsCharged());
        // $newAid->setImportUpdated($aid->isImportUpdated());
        // $newAid->setDsId($aid->getDsId());
        // $newAid->setDsMapping($aid->getDsMapping());
        // $newAid->setDsSchemaExists($aid->isDsSchemaExists());
        // $newAid->setContactInfoUpdated($aid->isContactInfoUpdated());
        // // on ne reprends pas timePublished
        // foreach ($aid->getCategories() as $category) {
        //     $newAid->addCategory($category);
        // }
        // foreach ($aid->getKeywords() as $keyWord) {
        //     $newAid->addKeyword($keyWord);
        // }
        // foreach ($aid->getPrograms() as $program) {
        //     $newAid->addProgram($program);
        // }
        // foreach ($aid->getAidFinancers() as $aidFinancer) {
        //     $newAidFinancer = new AidFinancer();
        //     $newAidFinancer->setBacker($aidFinancer->getBacker());
        //     $newAidFinancer->setPosition($aidFinancer->getPosition());
        //     $newAid->addAidFinancer($newAidFinancer);
        // }
        // foreach ($aid->getAidInstructors() as $aidInstructor) {
        //     $newAidInstructor = new AidInstructor();
        //     $newAidInstructor->setBacker($aidInstructor->getBacker());
        //     $newAidInstructor->setPosition($aidInstructor->getPosition());
        //     $newAid->addAidInstructor($newAidInstructor);
        // }
        // // on ne reprends pas aidProjects
        // // on ne reprends pas aidSuggestedAidProjects
        // foreach ($aid->getBundles() as $bundle) {
        //     $newAid->addBundle($bundle);
        // }
        // foreach ($aid->getExcludedSearchPages() as $excludedSearchPage) {
        //     $newAid->addExcludedSearchPage($excludedSearchPage);
        // }
        // foreach ($aid->getHighlightedSearchPages() as $highlitedSearchPage) {
        //     $newAid->addHighlightedSearchPage($highlitedSearchPage);
        // }
        // on ne reprends pas tous les logs

        // sauvegarde
        $managerRegistry->getManager()->persist($newAid);
        $managerRegistry->getManager()->flush();

        // message
        $this->tAddFlash(
            FrontController::FLASH_SUCCESS,
            'La nouvelle aide a été créée. Vous pouvez poursuivre l’édition. Et retrouvez l’aide dupliquée sur <a href="'.$this->generateUrl('app_user_aid_publications').'">votre portefeuille d’aides</a>.'
        );

        // redirecition
        return $this->redirectToRoute('app_user_aid_edit', ['slug' => $newAid->getSlug()]);
    }

    #[Route('/comptes/aides/statistiques/{slug}/', name: 'app_user_aid_stats', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function stats(
        $slug,
        AidRepository $aidRepository,
        UserService $userService,
        RequestStack $requestStack,
        LogAidViewRepository $logAidViewRepository,
        LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository,
        LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository,
        AidProjectRepository $aidProjectRepository
    ) {
        // le user
        $user = $userService->getUserLogged();

        // l'aide
        $aid = $aidRepository->findOneBy([
            'author' => $user,
            'slug' => $slug
        ]);
        if (!$aid instanceof Aid) {
            throw new NotFoundHttpException('Cette aide n\'exite pas');
        }

        // formulaire periode
        $dateMinGet = $requestStack->getCurrentRequest()->get('dateMin', null);
        $dateMaxGet = $requestStack->getCurrentRequest()->get('dateMax', null);
        try {
            $dateMin = new \DateTime(date($dateMinGet));
            $dateMax = new \DateTime(date($dateMaxGet));
        } catch (\Exception $e) {
            $dateMin = null;
            $dateMax = null;
        }
        $formAidStatsPeriod = $this->createForm(AidStatsPeriodType::class);
        if ($dateMin) {
            $formAidStatsPeriod->get('dateMin')->setData($dateMin);
        }
        if ($dateMax) {
            $formAidStatsPeriod->get('dateMax')->setData($dateMax);
        }
        $periodParams = [
            'dateMin' => $dateMin,
            'dateMax' => $dateMax
        ];
        $formAidStatsPeriod->handleRequest($requestStack->getCurrentRequest());
        if ($formAidStatsPeriod->isSubmitted()) {
            if ($formAidStatsPeriod->isValid()) {
                $periodParams['dateMin'] = $formAidStatsPeriod->get('dateMin')->getData();
                $periodParams['dateMax'] = $formAidStatsPeriod->get('dateMax')->getData();
            }
        }

        // nb vues
        $nbviews = $logAidViewRepository->countCustom(
            [
                'aid' => $aid,
                'dateMin' => $periodParams['dateMin'],
                'dateMax' => $periodParams['dateMax']
            ]
        );

        // nb click application url
        $nbApplicationUrlClicks = $logAidApplicationUrlClickRepository->countCustom(
            [
                'aid' => $aid,
                'dateMin' => $periodParams['dateMin'],
                'dateMax' => $periodParams['dateMax']
            ]
        );

        // nb click origin url
        $nbOriginUrlClicks = $logAidOriginUrlClickRepository->countCustom(
            [
                'aid' => $aid,
                'dateMin' => $periodParams['dateMin'],
                'dateMax' => $periodParams['dateMax']
            ]
        );

        // nb project public associés
        $nbProjectPublics = $aidProjectRepository->countProjectByAid($aid, [
            'projectPublic' => true,
            'dateMin' => $periodParams['dateMin'],
            'dateMax' => $periodParams['dateMax']
        ]);

        // nb project prive associés
        $nbProjectPrivates = $aidProjectRepository->countProjectByAid($aid, [
            'projectPublic' => false,
            'dateMin' => $periodParams['dateMin'],
            'dateMax' => $periodParams['dateMax']
        ]);

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_dashboard')
        );
        $this->breadcrumb->add(
            'Mon portefeuille d’aides',
            $this->generateUrl('app_user_aid_publications')
        );
        $this->breadcrumb->add(
            'Statistiques de l’aide « '.$aid->getName().' »',
            $this->generateUrl('app_user_aid_publications')
        );

        // rendu template
        return $this->render('user/aid/detail_stats.html.twig', [
            'formAidStatsPeriod' => $formAidStatsPeriod->createView(),
            'aid' => $aid,
            'nbViews' => $nbviews,
            'nbApplicationUrlClicks' => $nbApplicationUrlClicks,
            'nbOriginUrlClicks' => $nbOriginUrlClicks,
            'nbProjectPublics' => $nbProjectPublics,
            'nbProjectPrivates' => $nbProjectPrivates,
            'dateMin' => $periodParams['dateMin'],
            'dateMax' => $periodParams['dateMax']
        ]);
    }

    #[Route('/comptes/aides/exporter-les-stats/{slug}', name: 'app_user_aid_export_stats', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function aidsExportStats(
        $slug,
        UserService $userService,
        RequestStack $requestStack,
        AidRepository $aidRepository,
        LogAidViewRepository $logAidViewRepository,
        LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository,
        LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository,
        AidProjectRepository $aidProjectRepository
    )
    {
        // le user
        $user = $userService->getUserLogged();

        // l'aide
        $aid = $aidRepository->findOneBy(
            [
                'author' => $user,
                'slug' => $slug
            ]
        );
        if (!$aid instanceof Aid) {
            throw new NotFoundHttpException('Cette aide n\'existe pas');
        }

        $dateMinGet = $requestStack->getCurrentRequest()->get('dateMin', null);
        $dateMaxGet = $requestStack->getCurrentRequest()->get('dateMax', null);
        try {
            $dateMin = new \DateTime(date($dateMinGet));
            $dateMax = new \DateTime(date($dateMaxGet));
        } catch (\Exception $e) {
            throw new NotFoundHttpException('Cette aide n\'existe pas');
        }



        // nom de fichier
        $filename = 'Aides-territoires-statistiques.xlxs';

        $response = new StreamedResponse(function () use (
            $aid,
            $dateMin,
            $dateMax,
            $logAidViewRepository,
            $logAidApplicationUrlClickRepository,
            $logAidOriginUrlClickRepository,
            $aidProjectRepository
        ) {
            $csv = fopen('php://output', 'w+');
            fputcsv($csv, [
                'Date',
                'Nombre de vues',
                'Nombre de clics sur Candidater',
                'Nombre de clics sur Plus d’informations',
                'Nombre de projets privés liés',
                'Nombre de projets publics liés',
            ]);

            $currentDay = new \DateTime(date($dateMin->format('Y-m-d')));
            while ($currentDay <= $dateMax) {
                // nb vues
                $nbviews = $logAidViewRepository->countCustom(
                    [
                        'aid' => $aid,
                        'dateCreate' => $currentDay,
                    ]
                );

                // nb click application url
                $nbApplicationUrlClicks = $logAidApplicationUrlClickRepository->countCustom(
                    [
                        'aid' => $aid,
                        'dateCreate' => $currentDay,
                    ]
                );

                // nb click origin url
                $nbOriginUrlClicks = $logAidOriginUrlClickRepository->countCustom(
                    [
                        'aid' => $aid,
                        'dateCreate' => $currentDay,
                    ]
                );

                // nb project public associés
                $nbProjectPublics = $aidProjectRepository->countProjectByAid($aid, [
                    'projectPublic' => true,
                    'dateCreate' => $currentDay,
                ]);

                // nb project prive associés
                $nbProjectPrivates = $aidProjectRepository->countProjectByAid($aid, [
                    'projectPublic' => false,
                    'dateCreate' => $currentDay,
                ]);

                fputcsv($csv, [
                    $currentDay->format('d/m/Y'),
                    $nbviews,
                    $nbApplicationUrlClicks,
                    $nbOriginUrlClicks,
                    $nbProjectPublics,
                    $nbProjectPrivates,
                ]);

                $currentDay->add(new \DateInterval('P1D'));
            }
            fclose($csv);
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'.xlsx"');

        return $response;
    }
}