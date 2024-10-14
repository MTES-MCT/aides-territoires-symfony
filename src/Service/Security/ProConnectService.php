<?php

namespace App\Service\Security;

use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Signer\Key\InMemory;

class ProConnectService
{
    private string $proconnectClientId = '';
    private string $proconnectClientSecret = '';
    private string $proconnectDomain = '';

    private string $urlRedirectLogin = '';
    private string $urlRedirectLogout = '';

    private string $authorizationEndpoint = '';
    private string $tokenEndpoint = '';
    private string $userInfoEndpoint = '';
    private string $endSessionEndpoint = '';
    private string $jwksUri = '';

    private string $codeToken = '';
    private string $accessToken = '';
    private string $idToken = '';

    const SESSION_KEY_STATE = 'pr_state';
    const SESSION_KEY_NONCE = 'pr_nonce';
    const SESSION_KEY_CODE_TOKEN = 'pr_code_token';
    const SESSION_KEY_ID_TOKEN = 'pr_id_token';

    public function __construct(
        private ParamService $paramService,
        private HttpClientInterface $client,
        private RouterInterface $routerInterface
    )
    {
        $this->proconnectClientId =  $this->paramService->get('proconnect_client_id');
        $this->proconnectClientSecret =  $this->paramService->get('proconnect_client_secret');
        $this->proconnectDomain =  $this->paramService->get('proconnect_domain');

        // $this->urlRedirectLogin = $this->routerInterface->generate('app_user_parameter_profil', [], RouterInterface::ABSOLUTE_URL);
        $this->urlRedirectLogin = $this->routerInterface->generate('app_user_dashboard', [], RouterInterface::ABSOLUTE_URL);
        $this->urlRedirectLogout = $this->routerInterface->generate('app_home', [], RouterInterface::ABSOLUTE_URL);
    }

    /**
     * Pour récupérer les différentes urls de base à utiliser lors du process
     *
     * @return void
     */
    private function getDiscovery(): void
    {
        $url = 'https://'.$this->proconnectDomain.'/api/v2/.well-known/openid-configuration';
        $response = $this->client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $content = $response->toArray();
        
        $this->authorizationEndpoint = $content['authorization_endpoint'] ?? '';
        $this->tokenEndpoint = $content['token_endpoint'] ?? '';
        $this->userInfoEndpoint = $content['userinfo_endpoint'] ?? '';
        $this->endSessionEndpoint = $content['end_session_endpoint'] ?? '';
        $this->jwksUri = $content['jwks_uri'] ?? '';
    }

    /**
     * Pour récupérer le authorization endpoint en lui donnant les paramètres nécessaires
     * C'est l'url sur laquelle on envoit l'utilisateur lorsqu'il clique sur le bouton ProConnect
     *
     * @return string
     */
    public function getAuthorizationEndpoint(): string
    {
        if (!$this->authorizationEndpoint) {
            $this->getDiscovery();
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->proconnectClientId,
            'redirect_uri' => $this->urlRedirectLogin,
            'acr_values' => 'eidas1',
            'scope' => 'openid given_name usual_name email uid',
            'state' => $this->getState(),
            'nonce' => $this->getNonce(),
        ];
        $query = http_build_query($params);
        return $this->authorizationEndpoint . '?' . $query;
    }

    public function generateToken()
    {
        if (!$this->tokenEndpoint) {
            $this->getDiscovery();
        }

        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->proconnectClientId,
            'client_secret' => $this->proconnectClientSecret,
            'redirect_uri' => $this->urlRedirectLogin,
            'code' => $this->getCodeToken(),
        ];

        $response = $this->client->request('POST', $this->tokenEndpoint, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($params),
        ]);

        $content = $response->toArray();

        $this->accessToken = $content['access_token'] ?? '';
        $this->idToken = $content['id_token'] ?? '';

        // Vérification de l'id_token et du nonce
        $this->verifyIdToken($this->idToken);
        
        // stockage du id_token dans la session
        $session = new Session();
        $session->set(self::SESSION_KEY_ID_TOKEN, $this->idToken);

        // recupération des infos de l'utilisateur
        $this->getUserInfo();
        return $content;
    }

    private function getUserInfo()
    {
        if (!$this->userInfoEndpoint) {
            $this->getDiscovery();
        }

        $response = $this->client->request('GET', $this->userInfoEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
        ]);

        // JSON Web Token signé par l'algorithme spécifié à ProConnect, contenant les claims transmis par le FI
        dd($response->getContent());
        return $response->getContent();
    }

    private function verifyIdToken(string $idToken)
    {
        $response = $this->client->request('GET', $this->jwksUri, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $jwksData = json_decode($response->getContent(), true);

        // Extraire la clé publique pour RS256
        $publicKeyData = null;
        foreach ($jwksData['keys'] as $key) {
            if ($key['alg'] === 'RS256') {
                $publicKeyData = $key;
                break;
            }
        }
        
        if (!$publicKeyData) {
            throw new \Exception('Clé publique RS256 non trouvée');
        }
        
        // Convertir la clé publique en format PEM
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode(
                "\x30\x82\x01\x22\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00\x03\x82\x01\x0f\x00" .
                hex2bin($publicKeyData['n']) .
                "\x02\x03\x01\x00\x01"
            ), 64) .
            "\n-----END PUBLIC KEY-----";
        
        // Configuration de JWT
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText(''),
            InMemory::plainText($publicKey)
        );
        
        // Parser le token
        $parsedToken = $configuration->parser()->parse($idToken);

        // Contraintes de validation
        $constraints = [
            new SignedWith(new Sha256(), InMemory::plainText($publicKey)),
            new ValidAt(new \DateTimeImmutable()),
        ];

        // Validateur
        $validator = new Validator();

        // Vérifier le token
        try {
            $validator->assert($parsedToken, ...$constraints);
            dump('Token valide');
        } catch (RequiredConstraintsViolated $e) {
            dd('Token invalide: ' . $e->getMessage());
        }

        // Extraire le nonce du token
        $nonce = $parsedToken->claims()->get('nonce');

        // Vérifier le nonce avec celui stocké dans la session
        $sessionNonce = $_SESSION['nonce']; // Assurez-vous que le nonce est stocké dans la session
        if ($nonce === $sessionNonce) {
            dump('Nonce valide');
        } else {
            dd('Nonce invalide');
        }

        // Afficher les claims du token
        dd($parsedToken->claims()->all());

        return true;
    }

    public function getCodeToken(): string
    {
        // Si il est déjà assigné
        if ($this->codeToken !== '') {
            return $this->codeToken;
        }

        // On vérifie si le codeToken est en session
        $session = new Session();
        $codeToken = $session->get(self::SESSION_KEY_CODE_TOKEN);

        if ($codeToken) {
            $this->codeToken = $codeToken;
            return $codeToken;
        }

        return '';
    }

    public function setCodeToken(string $codeToken): void
    {
        $this->codeToken = $codeToken;

        // le met également en session
        $session = new Session();
        $session->set(self::SESSION_KEY_STATE, $codeToken);
    }

    /**
     * Génère un state
     *
     * @return string
     */
    private function getState(): string
    {
        // On vérifie si le state est déjà en session
        $session = new Session();
        $state = $session->get(self::SESSION_KEY_STATE);
        if ($state) {
            return $state;
        }

        // On génère un state
        $state = bin2hex(random_bytes(16));
        $session->set(self::SESSION_KEY_STATE, $state);

        return $state;
    }

    /**
     * Génère un nonce
     *
     * @return string
     */
    private function getNonce(): string
    {
        // On vérifie si le nonce est déjà en session
        $session = new Session();
        $nonce = $session->get(self::SESSION_KEY_NONCE);
        if ($nonce) {
            return $nonce;
        }

        // On génère un nonce
        $nonce = bin2hex(random_bytes(16));
        $session->set(self::SESSION_KEY_NONCE, $nonce);

        return $nonce;
    }

    /**
     * Vérifie si le state est valide
     *
     * @param string $state
     * @return boolean
     */
    public function isValidState(string $state): bool
    {
        $session = new Session();
        $stateSession = $session->get(self::SESSION_KEY_STATE);

        return $stateSession === $state;
    }
}