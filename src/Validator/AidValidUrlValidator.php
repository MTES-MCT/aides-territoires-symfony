<?php

namespace App\Validator;

use App\Entity\Aid\Aid;
use App\Exception\CustomValidatorException;
use App\Repository\Aid\AidRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class AidValidUrlValidator extends ConstraintValidator
{
    protected string $message =
        'Cette URL ne correspond pas à une aide actuellement publiée. Merci de saisir une URL correcte. ';

    public function __construct(
        protected TranslatorInterface $translator,
        protected ManagerRegistry $managerRegistry,
        protected RequestStack $requestStack,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        try {
            // parse l'url données
            $infos = parse_url($value);

            // check host
            if (!isset($infos['host']) || $infos['host'] !== $this->requestStack->getCurrentRequest()->getHost()) {
                throw new CustomValidatorException($this->message);
            }

            // check aid url
            preg_match('/\/aides\/([a-z0-9\-]+)\//', $infos['path'], $matches);
            if (!isset($matches[1])) { // ça ne matche pas avec le format attendu
                throw new CustomValidatorException($this->message);
            }

            // check base
            /** @var AidRepository $aidRepo */
            $aidRepo = $this->managerRegistry->getRepository(Aid::class);
            $aidTest = $aidRepo->findOneCustom([
                'showInSearch' => true,
                'slug' => $matches[1],
            ]);
            if (!$aidTest instanceof Aid) {
                throw new CustomValidatorException($this->message);
            }
        } catch (CustomValidatorException $exception) {
            $this->context->buildViolation($this->translator->trans($exception->getMessage()))->addViolation();
        }
    }
}
