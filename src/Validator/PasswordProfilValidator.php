<?php

namespace App\Validator;

use App\Exception\CustomValidatorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordProfilValidator extends ConstraintValidator
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        $errorMessage = 'Le mot de passe ne respècte pas les règles';
        if ('' != $value) {
            try {
                // longueur
                if (strlen($value) < PasswordValidator::PASSWORD_MIN_LENGTH) {
                    throw new CustomValidatorException($errorMessage);
                }
                // max length allowed by Symfony for security reasons
                if (strlen($value) > 4096) {
                    throw new CustomValidatorException($errorMessage);
                }
                // au moins 1 chiffre
                if (!preg_match('/[\d]+/', $value)) {
                    throw new CustomValidatorException($errorMessage);
                }
                // au moins 1 lettre
                if (!preg_match('/[a-zA-Z]+/', $value)) {
                    throw new CustomValidatorException($errorMessage);
                }
                if (in_array($value, PasswordValidator::PASSWORD_COMMONS)) {
                    throw new CustomValidatorException($errorMessage);
                }
            } catch (CustomValidatorException $exception) {
                $this->context->buildViolation($this->translator->trans($exception->getMessage()))
                    ->addViolation();
            }
        }
    }
}
