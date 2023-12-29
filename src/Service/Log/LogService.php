<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAccountRegisterFromNextPageWarningClickEvent;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidOriginUrlClick;
use AWS\CRT\Log;
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

            case 'originUrl':
                $log = new LogAidOriginUrlClick();
                $log->setQuerystring($params['querystring']);
                $log->setSource($this->getSiteFromHost($params['host']));
                $aid = null;
                if (isset($params['aidSlug'])) {
                    $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => $params['aidSlug']]);
                }
                $log->setAid($aid);
                break;

                case 'applicationUrl':
                    $log = new LogAidApplicationUrlClick();
                    $log->setQuerystring($params['querystring']);
                    $log->setSource($this->getSiteFromHost($params['host']));
                    $aid = null;
                    if (isset($params['aidSlug'])) {
                        $aid = $this->managerRegistry->getRepository(Aid::class)->findOneBy(['slug' => $params['aidSlug']]);
                    }
                    $log->setAid($aid);
                    break;

                case 'createDsFolder':
                    dd($params);
                    $log = new LogAidCreatedsFolder();
                    // $log->set
                    break;
            default:
                // Code à exécuter si aucune des conditions précédentes n'est remplie
                break;
        }

        $this->managerRegistry->getManager()->persist($log);
        $this->managerRegistry->getManager()->flush();
        
    }

    public function getSiteFromHost($host)
    {
        /**
         * Return the string bit that identify a site.
         * This can be the subdomain or a minisite slug.
         * aides-territoires.beta.gouv.fr --> aides-territoires
         * staging.aides-territoires.beta.gouv.fr --> staging
         * francemobilites.aides-territoires.beta.gouv.fr --> francemobilites
         * aides.francemobilites.fr --> francemobilites  # Using the mapping
         */
        $mapDnsToMinisites = [
            ['aides-territoires.beta.gouv.fr', 'aides-territoires'],
            ['staging.aides-territoires.beta.gouv.fr', 'staging'],
            ['francemobilites.aides-territoires.beta.gouv.fr', 'francemobilites'],
            ['aides.francemobilites.fr', 'francemobilites'],
            ['centre-val-de-loire.aides-territoires.beta.gouv.fr', 'france-relance-cvl'],
            ['guyane.aides-territoires.beta.gouv.fr', 'france-relance-guyane'],
            ['martinique.aides-territoires.beta.gouv.fr', 'france-relance-martinique'],
            ['mayotte.aides-territoires.beta.gouv.fr', 'france-relance-mayotte'],
            ['reunion.aides-territoires.beta.gouv.fr', 'france-relance-reunion'],
            // Add more mappings here
        ];

        foreach ($mapDnsToMinisites as $mapping) {
            $minisite_host = $mapping[0];
            $minisite_slug = $mapping[1];
            // If we detect that a mapping is defined for the incoming
            // DNS host, then we get the minisite slug from that mapping.
            if (strpos($host, $minisite_host) !== false) {
                return $minisite_slug;
            }
        }

        if (strpos($host, "aides-territoires") !== false) {
            return explode(".", $host)[0];
        }

        return $host;
    }
}