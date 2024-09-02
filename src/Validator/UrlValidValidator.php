<?php

namespace App\Validator;

use App\Exception\CustomValidatorException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class UrlValidValidator extends ConstraintValidator
{
    protected string $message = 'Cette URL n\'est pas valide.';
    public function __construct(
        protected TranslatorInterface $translator,
        protected ManagerRegistry $managerRegistry,
        protected RequestStack $requestStack
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        try {
        } catch (CustomValidatorException $exception) {
            $this->context->buildViolation($this->translator->trans($exception->getMessage()))->addViolation();
        }
    }
}
