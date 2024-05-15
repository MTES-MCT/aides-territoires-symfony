<?php

namespace App\Validator;

use App\Src\Exception\CustomValidatorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordValidator extends ConstraintValidator
{
	const PASSWORD_COMMONS = [
		'1234',
		'123456789',
		'azerty',
		'password',
		'motdepasse'
	];

	protected $translator;

	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}

	public function validate($value, Constraint $constraint): void
    {
		$errorMessage = 'Le mot de passe ne respècte pas les règles';

		try {
			// longueur
			if (strlen($value) < 9) {
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
			if (in_array($value, self::PASSWORD_COMMONS)) {
				throw new CustomValidatorException($errorMessage);
			}
		} catch (CustomValidatorException $exception) {
			$this->context->buildViolation($this->translator->trans($exception->getMessage()))
			->addViolation();
		}
    }
}
