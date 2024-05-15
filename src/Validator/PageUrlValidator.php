<?php

namespace App\Validator;

use App\Src\Exception\CustomValidatorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageUrlValidator extends ConstraintValidator
{
	protected $translator;

	public function __construct(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}

	public function validate($value, Constraint $constraint): void
	{
		try {
			// Replace tab characters with spaces
			$value = str_replace("\t", ' ', $value);

			// verifie / en debut et fin
			if (substr($value, 0, 1) !== '/' || substr($value, -1) !== '/') {
				throw new CustomValidatorException('Doit commencer et finir par /');
			}
			// que des lettres minuscules, chiffres ou -
			if (!preg_match('/^[a-z0-9\/\-]+$/', $value)) {
				throw new CustomValidatorException('Uniquement des minuscules non accentuées, des chiffres ou -');
			}
		} catch (CustomValidatorException $exception) {
			$this->context->buildViolation($this->translator->trans($exception->getMessage()))
			->addViolation();
		}
	}
}
