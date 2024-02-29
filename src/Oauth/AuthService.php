<?php

namespace Oauth;

use Oauth\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    private ?array $userData = null;

    private GenericProvider $provider;


    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack          $requestStack,
        private readonly ManagerRegistry       $managerRegistry,
        private readonly ?LoggerInterface      $logger
    )
    {
        $this->provider = new GenericProvider([
            'clientId' => $this->parameterBag->get('auth')['client_id'],    // The client ID assigned to you by the provider
            'clientSecret' => $this->parameterBag->get('auth')['client_secret'],    // The client password assigned to you by the provider
            'redirectUri' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost(),
            'urlAuthorize' => $this->parameterBag->get('auth')['url'] . '/authorize',
            'urlAccessToken' => $this->parameterBag->get('auth')['url'] . '/token',
            'urlResourceOwnerDetails' => $this->parameterBag->get('auth')['url'] . '/auth/me'// the /me endpoint in com axe  //$this->requestStack->getCurrentRequest()->getSchemeAndHttpHost(),
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
        $token = $this->provider->getAccessToken(
            'refresh_token',
            ['refresh_token' => $refreshToken]
        );

        $userData = $this->getUserDetails($token->getToken());
        return $this->generateUser($userData, $token);
    }

    public function getUserDetails(string $token)
    {
        $parsedToken = (new Parser(new JoseEncoder()))->parse($token);
        return $parsedToken->claims()->get('user_info');
    }

    public function getTokenIntrospection(string $token): TokenIntrospectDto
    {
        $this->logger->info('SSO Authentication TokenValidation  -Introspect To AXE');
        try {
            $response = $this->provider->getHttpClient()->request(
                Request::METHOD_GET,
                $this->parameterBag->get('auth')['url'] . '/auth/introspect?token=' . $token
            );

            $decodeResponse = json_decode($response->getBody()->getContents(), true);
            return TokenIntrospectDto::fromArray($decodeResponse);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
            throw new \RuntimeException("Something went wrong in the authentication process. Please try again or contact the system administrator.");
        }
    }

    private function generateUser(array $userData, AccessToken $token)
    {
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $user = $userRepository->findOneBy(['uuid' => $userData['id']]);
        if (!$user instanceof User) {
            $user = (new User())->setUuid($userData['id']);
        }
        $user
            ->setRoles(["ROLE_USER"])
            ->setToken($token->getToken())
            ->setRefreshToken($token->getRefreshToken());

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        return $user;
    }
}
