<?php

namespace RemoteTech\ComAxe\Client\Oauth\UserProvider;

use Doctrine\Persistence\ManagerRegistry;
use RemoteTech\ComAxe\Client\Oauth\AxeUserProviderInterface;
use RemoteTech\ComAxe\Client\Oauth\Model\User;
use RemoteTech\ComAxe\Client\Oauth\Model\UserModel;

class DoctrineUserProvider implements AxeUserProviderInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry
    )
    {
    }

    public function loadUserFromStorage(string $identifier): UserModel
    {
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $user = $userRepository->findOneBy(['uuid' => $identifier]);

        $userModel = new UserModel();
        if ($user !== null) {
            $userModel->setUuid($user->getUuid());
            $userModel->setRoles($user->getRoles());
            $userModel->setToken($user->getToken());
            $userModel->setRefreshToken($user->getRefreshToken());
        }

        return $userModel;
    }

    public function setUserToStorage(UserModel $userModel): void
    {
        $user = $this->managerRegistry->getRepository(User::class)->findOneBy([
            'uuid' => $userModel->getUuid()
        ]);
        if (!($user instanceof User))
        {
            $user = new User();
        }
        $user->setType($userModel->getType());
        $user->setUuid($userModel->getUuid());
        $user->setStatus($userModel->getStatus());
        $user->setToken($userModel->getToken());
        $user->setRefreshToken($userModel->getRefreshToken());
        $user->setRoles($userModel->getRoles());
        $user->setEmail($userModel->getEmail());
        $user->setUsername($userModel->getUsername());

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();
    }
}
