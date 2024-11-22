<?php

namespace App\Validator;

use App\Exception\CustomValidatorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordValidator extends ConstraintValidator
{
    public const PASSWORD_MIN_LENGTH = 12;
    public const PASSWORD_COMMONS = [
        'Password123!', 'Admin123$', 'Azerty123#', 'Bienvenue1*', 'MotdePasse1!',
        'Paris2024!', 'Qwerty123$', 'Bonjour123#', 'Welcome1@', 'Football2022!',
        'Sunshine1#', 'Princess123$', 'Dragon123!', 'Passw0rd!', '1234Qwerty#'
    ];
    public const PASSWORD_SPECIAL_CHARACTERS = '#?!@$%^&*-\'_+()[]';

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        $errorMessage = 'Le mot de passe ne respècte pas les règles';

        try {
            // Longueur minimum
            if (strlen($value) < self::PASSWORD_MIN_LENGTH) {
                throw new CustomValidatorException($errorMessage);
            }

            // Longueur maximale autorisé par Symfony pour des raisons de sécurité
            if (strlen($value) > 4096) {
                throw new CustomValidatorException($errorMessage);
            }

            // Vérifie au moins une lettre minuscule
            if (!preg_match('/[a-z]/', $value)) {
                throw new CustomValidatorException($errorMessage);
            }

            // Vérifie au moins une lettre majuscule
            if (!preg_match('/[A-Z]/', $value)) {
                throw new CustomValidatorException($errorMessage);
            }

            // Vérifie au moins un chiffre
            if (!preg_match('/\d/', $value)) {
                throw new CustomValidatorException($errorMessage);
            }

            // Vérifie au moins un caractère spécial parmi ceux autorisés
            if (!preg_match('/[' . preg_quote(self::PASSWORD_SPECIAL_CHARACTERS, '/') . ']/', $value)) {
                throw new CustomValidatorException($errorMessage);
            }

            // Vérifie que le mot de passe n'est pas dans la liste des mots de passe courants
            if (in_array($value, self::PASSWORD_COMMONS)) {
                throw new CustomValidatorException($errorMessage);
            }
        } catch (CustomValidatorException $exception) {
            $this->context->buildViolation($this->translator->trans($exception->getMessage()))
                ->addViolation();
        }
    }
}
