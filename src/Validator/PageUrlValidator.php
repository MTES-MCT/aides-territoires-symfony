<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageUrlValidator extends ConstraintValidator
{
	protected $translator;

	public function __construct(TranslatorInterface $translator) {
		$this->translator = $translator;
	}

	public function validate($value, Constraint $constraint): void
    {
		try {
			// verifie / en debut et fin
			if (substr($value, 0, 1) !== '/' || substr($value, -1) !== '/' ) {
				throw new \Exception('Doit commencer et finir par /');
			}
			// que des lettres minuscules, chiffres ou -
			if (!preg_match('/^[a-z0-9\/\-]+$/', $value)) {
				throw new \Exception('Uniquement des minuscules non accentuées, des chiffres ou -');
			}
		} catch (\Exception $exception) {
		$this->context->buildViolation($this->translator->trans($exception->getMessage()))
		->addViolation();
		}
    }
}