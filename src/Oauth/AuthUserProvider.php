<?php

namespace Oauth;

use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Oauth\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly AuthService     $authService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @throws IdentityProviderException
     */
    public function refreshUser(UserInterface|User $user): UserInterface
    {
        $introspection = $this->authService->getTokenIntrospection($user->getToken());

        if ($introspection->revoked) {
            $this->logger->info('AccessToken is revoked. Restarting authentication', ['revoked' => $introspection->revoked]);
            throw new AuthenticationException('User access has been revoked. Need to reauthenticate');
        } elseif ($introspection->expired) {
            $this->logger->info('AccessToken is expired');
            $user = $this->authService->refreshAccessToken($user->getRefreshToken());
        } else {
            $this->logger->info('AccessToken is valid - continuing', ['revoked' => $introspection->revoked]);
            $user = $this->loadUserByIdentifier($user->getUserIdentifier());
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['uuid' => $identifier]);

        return $this->attachToUser($user, $this->authService->getUserDetails($user->getToken()));
    }


    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // TODO: Implement upgradePassword() method.
    }

    public function attachToUser(User $user, array $data): User
    {
        $user->setEmail($data['email']);
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);
        $user->setUsername($data['username']);
        $user->setType($data['type']);
        $user->setStatus($data['status']);

        return $user;
    }
}
