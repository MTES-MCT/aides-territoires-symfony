<?php

namespace App\Service\Security;

use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Validator;

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
    private string $codeToken = '';
    private string $accessToken = '';
    private string $idToken = '';

    const SESSION_KEY_STATE = 'pr_state';
    const SESSION_KEY_NONCE = 'pr_nonce';
    const SESSION_KEY_CODE_TOKEN = 'pr_code_token';

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
        
        return $content;
    }

    private function verifyIdToken(string $idToken)
    {
        $parser = new Parser(new JoseEncoder());

        $token = $parser->parse($idToken);

        $validator = new Validator();

        dd($this->getNonce(), $token);
        if (!$validator->validate($token, new RelatedTo($this->getNonce()))) {
            dd('Invalid token (1)!');
        }

        dd('ok');
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