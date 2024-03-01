<?php

namespace RemoteTech\ComAxe\Client\Oauth;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use RemoteTech\ComAxe\Client\Oauth\Model\TokenIntrospect;
use RemoteTech\ComAxe\Client\Oauth\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    private ?array $userData = null;

    private GenericProvider $provider;


    public function __construct(
        private readonly array            $config,
        private readonly RequestStack     $requestStack,
        private readonly ?LoggerInterface $logger,
        private readonly AxeUserProviderInterface $userProvider
    )
    {
        $this->provider = new GenericProvider([
            'clientId' => $this->config['client_id'],
            'clientSecret' => $this->config['client_secret'],
            'redirectUri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost(),
            'urlAuthorize' => $this->config['url'] . '/authorize',
            'urlAccessToken' => $this->config['url'] . '/token',
            'urlResourceOwnerDetails' => $this->config['url'] . '/auth/me'
        ]);
    }

    public function generateLoginUrl(): string
    {
        return $this->provider->getAuthorizationUrl();
    }

    /**
     * @throws IdentityProviderException
     */
    public function authenticateUser(string $code): User
    {
        $this->logger?->info('SSO Authentication Start  -get Token from AXE');
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);

        $userData = $this->getUserDetails($token->getToken());

        return $this->generateUser($userData, $token);
    }

    /**
     * @throws IdentityProviderException
     */
    public function refreshAccessToken(string $refreshToken): User
    {
        $this->logger?->info('SSO Authentication Refresh  - getToken by RefreshToken from AXE');
        $token = $this->provider->getAccessToken('refresh_token',
            ['refresh_token' => $refreshToken]
        );

        $userData = $this->getUserDetails($token->getToken());

        return $this->generateUser($userData, $token);
    }

    public function getUserDetails(string $token): array
    {
        $parsedToken = (new Parser(new JoseEncoder()))->parse($token);
        
        return $parsedToken->claims()->get('user_info');
    }

    public function getTokenIntrospection(string $token): TokenIntrospect
    {
        $this->logger?->info('SSO Authentication TokenValidation  -Introspect To AXE');
        try {
            $response = $this->provider->getHttpClient()->request(
                Request::METHOD_GET,
                $this->config['url'] . '/auth/introspect?token=' . $token
            );

            $decodeResponse = json_decode($response->getBody()->getContents(), true);
            return TokenIntrospect::fromArray($decodeResponse);
        } catch (\Throwable $exception) {
            $this->logger?->error($exception->getMessage(), $exception->getTrace());
            throw new \RuntimeException("Something went wrong in the authentication process. Please try again or contact the system administrator.");
        }
    }

    private function generateUser(array $userData, AccessToken $token): User
    {

        $user = $this->loadUserFromProvider($userData['id']);

        if (!$user instanceof User) {
            $user = (new User())->setUuid($userData['id']);
        }
        $user
            ->setRoles(["ROLE_USER"])
            ->setToken($token->getToken())
            ->setRefreshToken($token->getRefreshToken());

        $this->userProvider->setUserToStorage($user);

        return $user;
    }

    public function loadUserFromProvider(string $identifier): ?User
    {
        return $this->userProvider->loadUserFromStorage($identifier);
    }
}
