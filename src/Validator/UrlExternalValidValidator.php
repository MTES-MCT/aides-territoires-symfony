<?php

namespace App\Validator;

use App\Exception\CustomValidatorException;
use App\Service\Various\ParamService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UrlExternalValidValidator extends ConstraintValidator
{
    public string $message = 'L\'url doit être en https avec un format valide.';

    public function __construct(
        private TranslatorInterface $translator,
        private ManagerRegistry $managerRegistry,
        private RequestStack $requestStack,
        private ParamService $paramService
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        try {
            if (null === $value || '' === $value) {
                return;
            }

            if (!$constraint instanceof UrlExternalValid) {
                throw new UnexpectedTypeException($constraint, UrlExternalValid::class);
            }

            if (!is_string($value)) {
                throw new UnexpectedValueException($value, 'string');
            }

            // Verification url valide et en https
            if (!filter_var($value, FILTER_VALIDATE_URL) || parse_url($value, PHP_URL_SCHEME) !== 'https') {
                $this->context->buildViolation($this->message)->addViolation();
                return;
            }

            // Vérifie url externe
            $forbiddenUrls = explode(',', $this->paramService->get('forbidden_external_urls'));
            $host = parse_url($value, PHP_URL_HOST);

            // Vérifie que l'hôte n'est pas une adresse IP
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $this->context->buildViolation('L\'hôte ne doit pas être une adresse IP.')->addViolation();
                return;
            }

            // vérifie que l'hote n'est pas dans la liste des urls interdites
            foreach ($forbiddenUrls as $forbiddenUrl) {
                if (strpos($host, $forbiddenUrl) !== false) {
                    $this->context->buildViolation($this->message)->addViolation();
                    return;
                }
            }
        } catch (CustomValidatorException $exception) {
            $this->context->buildViolation($this->translator->trans($exception->getMessage()))->addViolation();
        }
    }
}
