<?php

namespace App\Service\Security;

use App\Exception\Security\ProConnectException;
use App\Service\Various\ParamService;
use CoderCat\JWKToPEM\JWKConverter;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

        $this->urlRedirectLogin = $this->routerInterface->generate('app_user_proconnect', [], RouterInterface::ABSOLUTE_URL);
        $this->urlRedirectLogout = $this->routerInterface->generate('app_logout', [], RouterInterface::ABSOLUTE_URL).'/';
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

    /**
     * Redirige l'utilisateur sur l'url de déconnexion de ProConnect si besoin
     *
     * @return RedirectResponse|null
     */
    public function getLogoutUrl() : ?string {
        // On vérifie qu'il y a bien un idToken en session
        $idToken = $this->getIdToken();
        if (empty($idToken)) {
            return null;
        }

        // On supprime le token de la session
        $session = new Session();
        $session->remove(self::SESSION_KEY_ID_TOKEN);

        // on appelle le end_session_endpoint
        if (!$this->endSessionEndpoint) {
            $this->getDiscovery();
        }

        // les paramètres pour l'appel
        $params = [
            'id_token_hint' => $idToken,
            'state' => $this->getState(),
            'post_logout_redirect_uri' => $this->urlRedirectLogout,
        ];

        $query = http_build_query($params);

        // on redirige
        return $this->endSessionEndpoint . '?' . $query;
    }
        
    /**
     * Recupère les données de l'utilisateur depuis ProConnect
     *
     * @param array $params
     * @return array
     */
    public function getDataFromProconnect(array $params) : array {
        $state = $params['state'] ?? null;

        // si le state est null ou non valide
        if (!$state || !$this->isValidState($state)) {
            throw new ProConnectException('Erreur lors de la récupération du state');
        }

        // On stocke le code
        $code = $params['code'] ?? null;
        if (!$code) {
            throw new ProConnectException('Erreur lors de la récupération du code');
        }
        $this->setCodeToken($code);

        // On récupère les infos de l'utilisateur
        return $this->getUserInfo();
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
     * Stocke les tokens liés à l'utilisateur dans la session
     *
     * @return array
     */
    public function storeUserTokens(): void
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

        // les tokens
        $accessToken = $content['access_token'] ?? '';
        $idToken = $content['id_token'] ?? '';

        // on vérifie que non vide
        if (empty($accessToken) || empty($idToken)) {
            throw new ProConnectException('Erreur lors de la récupération des tokens');
        }

        // Vérification de l'id_token et du nonce
        if (!$this->verifyIdToken($idToken)) {
            throw new ProConnectException('Erreur lors de la vérification du token');
        }
        
        // On assigne à l'objet
        $this->accessToken = $accessToken;
        $this->idToken = $idToken;

        // stockage du id_token dans la session
        $session = new Session();
        $session->set(self::SESSION_KEY_ID_TOKEN, $this->idToken);
    }

    /**
     * Pour récupérer les infos de l'utilisateurs (email, etc..)
     *
     * @return array
     */
    private function getUserInfo(): array
    {
        if (!$this->userInfoEndpoint) {
            $this->getDiscovery();
        }

        // recupère les tokens de l'utilisateurs
        $this->storeUserTokens();

        $response = $this->client->request('GET', $this->userInfoEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
        ]);

        // JSON Web Token signé par l'algorithme spécifié à ProConnect, contenant les claims transmis par le FI
        $userToken = $response->getContent();

        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($userToken);

        if (!$this->verifyIdToken($this->idToken)) {
            throw new ProConnectException('Erreur lors de la vérification du token');
        }
        // les infos utilisateurs sont dans claims
        return $token->claims()->all();
    }

    /**
     * Vérifie que le token est bien signé par ProConnect
     *
     * @return void
     */
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
            if ($key['alg'] === 'RS256' && $key['kty'] === 'RSA') {
                $publicKeyData = $key;
                break;
            }
        }
    
        if (!$publicKeyData) {
            throw new ProConnectException('Clé publique RS256 non trouvée');
        }

        $publicKey = $this->convertJwkToPem($publicKeyData);

        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($idToken);

        $validator = new Validator();

        try {
            // Valide que le token est bien signé en Sha256 avec la clé publique récupérée
            $validator->assert(
                $token,
                new SignedWith(
                    new Sha256(),
                    InMemory::plainText($publicKey)
                )
            );
            // valide que le nonce est bien celui que nous avons fourni
            $validator->assert($token, new HasClaimWithValue('nonce', $this->getNonce()));
        } catch (RequiredConstraintsViolated $e) {
            return false;
        }
        
        return true;
    }

    /**
     * Convertir la cle JWK en cle PEM pour la validation
     *
     * @param array $jwk
     * @return string
     */
    private function convertJwkToPem(array $jwk): string
    {
        $jwkConverter = new JWKConverter();
        return $jwkConverter->toPEM($jwk);
    }
    
    /**
     * Récupère le id_token
     *
     * @return string
     */
    public function getIdToken(): string
    {
        // Si il est déjà assigné
        if ($this->idToken !== '') {
            return $this->idToken;
        }

        // On vérifie si le codeToken est en session
        $session = new Session();
        $idToken = $session->get(self::SESSION_KEY_ID_TOKEN);

        if ($idToken) {
            $this->idToken = $idToken;
            return $idToken;
        }

        return '';
    }

    /**
     * Récupère le code token
     *
     * @return string
     */
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


    /**
     * Stocke le code token
     *
     * @param string $codeToken
     * @return void
     */
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
