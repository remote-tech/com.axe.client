<?php

namespace RemoteTech\ComAxe\Client\Oauth;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use RemoteTech\ComAxe\Client\Oauth\Model\TokenIntrospect;
use RemoteTech\ComAxe\Client\Oauth\Model\UserModel;
use RemoteTech\ComAxe\Client\Oauth\UserProvider\AxeUserProviderInterface;
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
            'urlResourceOwnerDetails' => $this->config['url'] . '/me'
        ]);
    }

    public function generateLoginUrl(): string
    {
        return $this->provider->getAuthorizationUrl();
    }

    public function generateLogoutUrl(string $redirectUrl): string
    {
        return $this->config['url'] . '/logout?redirect_url=' . urlencode($redirectUrl);
    }

    /**
     * @throws IdentityProviderException
     */
    public function authenticateUser(string $code): UserModel
    {
        $this->logger?->info('SSO Authentication Start  -get Token from AXE');

        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);

        $userData = $this->getUserDetails($token->getToken());

        return $this->generateUser($userData, $token);
    }

    /**
     * @throws IdentityProviderException
     */
    public function refreshAccessToken(string $refreshToken): UserModel
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
                $this->config['url'] . '/introspect?token=' . $token
            );

            $decodeResponse = json_decode($response->getBody()->getContents(), true);
            return TokenIntrospect::fromArray($decodeResponse);
        } catch (\Throwable $exception) {
            $this->logger?->error($exception->getMessage(), $exception->getTrace());
            throw new \RuntimeException("Something went wrong in the authentication process. Please try again or contact the system administrator.");
        }
    }

    private function generateUser(array $userData, AccessToken $token): UserModel
    {
        $userModel = $this->loadUserFromProvider($userData['id']);

        $userModel
            ->setUuid($this->generateUuid($userData['id']))
            ->setToken($token->getToken())
            ->setRefreshToken($token->getRefreshToken())
            ->setRoles($userData['roles']);

        $this->userProvider->setUserToStorage($userModel);

        return $userModel;
    }

    public function loadUserFromProvider(string $identifier): UserModel
    {
        return $this->userProvider->loadUserFromStorage($identifier);
    }

    private function generateUuid(string $id): string
    {
        $namespace = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'rt_account');

        return Uuid::uuid5($namespace, $id);
    }
}
