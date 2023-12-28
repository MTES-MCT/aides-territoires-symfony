<?php

namespace App\Service\Log;

use App\Entity\Log\LogAccountRegisterFromNextPageWarningClickEvent;
use Doctrine\Persistence\ManagerRegistry;

class LogService
{
    public function __construct(
        private ManagerRegistry $managerRegistry
    )
    {
    }

    public function log(
        ?string $type,
        ?array $params,
    ): void
    {
        switch ($type) {
            case 'register-from-next-page-warning':
                $querystring = '';
                if (is_array($params)) {
                    foreach ($params as $key => $param) {
                        if ($key == '_token') { // pas besoin de stocker le tocken
                            continue;
                        }
                        $querystring .= $key.'='.$param . '&';
                    }
                    $querystring = substr($querystring, 0, -1); // on enlève le dernier & (qui est en trop)
                }
                if (trim($querystring) == '') {
                    $querystring = null;
                }
                $log = new LogAccountRegisterFromNextPageWarningClickEvent();
                $log->setQuerystring($querystring);
                
                break;
            default:
                // Code à exécuter si aucune des conditions précédentes n'est remplie
                break;
        }

        $this->managerRegistry->getManager()->persist($log);
        $this->managerRegistry->getManager()->flush();
        
    }
}