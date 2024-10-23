<?php

namespace App\Service\Various;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParamService
{
    public function __construct(
        protected ParameterBagInterface $parameterBagInterface
    ) {
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
            $this->parameterBagInterface->resolve();

            $param = $this->parameterBagInterface->get($paramKey);

            if ($param == '') {
                $param = null;
            }

            return $param;
        } catch (\Exception $e) {
            return null;
        }
    }
}
