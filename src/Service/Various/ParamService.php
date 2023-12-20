<?php

namespace App\Service\Various;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParamService
{
    public function __construct(
        protected ParameterBagInterface $parameterBagInterface
    )
    {
    }

    /**
     * Retourne un parametre
     *
     * @param string $paramKey
     * @return string|null
     */
    public function get(string $paramKey): ?string
    {
        try {
            return $this->parameterBagInterface->get($paramKey);
        } catch (\Exception $e) {
            return null;
        }
    }
}