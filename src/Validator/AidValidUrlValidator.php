<?php
namespace App\Validator;

use App\Entity\Aid\Aid;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class AidValidUrlValidator extends ConstraintValidator
{
    protected string $message = 'Cette URL ne correspond pas à une aide actuellement publiée. Merci de saisir une URL correcte. ';
	public function __construct(
        protected TranslatorInterface $translator,
        protected ManagerRegistry $managerRegistry,
        protected RequestStack $requestStack
        ) {
	}

	public function validate($value, Constraint $constraint): void
    {
        
		try {
            // parse l'url données
            $infos = parse_url($value);

            // check host
            if (!isset($infos['host']) || $infos['host'] !== $this->requestStack->getCurrentRequest()->server->get('SERVER_NAME')) {
                dd($infos, $this->requestStack->getCurrentRequest()->server->get('SERVER_NAME'));
                throw new \Exception($this->message);
            }
    
            // check aid url
            preg_match('/\/aides\/([a-z0-9\-]+)\//', $infos['path'], $matches);
            if (!isset($matches[1])) { // ça ne matche pas avec le format attendu
                throw new \Exception($this->message);
            }

            // check base
            $aidTest = $this->managerRegistry->getRepository(Aid::class)->findOneCustom([
                'showInSearch' => true,
                'slug' => $matches[1]
            ]);
            if (!$aidTest instanceof Aid) {
                throw new \Exception($this->message);
            }
		} catch (\Exception $exception) {
            $this->context->buildViolation($this->translator->trans($exception->getMessage()))->addViolation();
		}
    }
}