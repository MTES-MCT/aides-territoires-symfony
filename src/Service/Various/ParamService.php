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
        $param = null;
        try {
            $param = $this->parameterBagInterface->resolve($paramKey);
        } catch (\Exception $e) {
            $param = null;
        }
        if (!$param || $param == '') {
            try {
                $param = $this->parameterBagInterface->get($paramKey);
            } catch (\Exception $e) {
                $param = null;
            }
        }
        return $param;
    }
}
