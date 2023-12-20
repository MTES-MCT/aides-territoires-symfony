<?php
namespace App\Validator;

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
				throw new \Exception($errorMessage);
			}
			// max length allowed by Symfony for security reasons
			if (strlen($value) > 4096) {
				throw new \Exception($errorMessage);
			}
			// au moins 1 chiffre
			if (!preg_match('/[0-9]+/', $value)) {
				throw new \Exception($errorMessage);
			}
			// au moins 1 lettre
			if (!preg_match('/[a-zA-Z]+/', $value)) {
				throw new \Exception($errorMessage);
			}
			if (in_array($value, self::PASSWORD_COMMONS)) {
				throw new \Exception($errorMessage);
			}
		} catch (\Exception $exception) {
		$this->context->buildViolation($this->translator->trans($exception->getMessage()))
		->addViolation();
		}
    }
	
}