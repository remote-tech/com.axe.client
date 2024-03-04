<?php

namespace RemoteTech\ComAxe\Client\Oauth\UserProvider;

use RemoteTech\ComAxe\Client\Oauth\Model\User;
use RemoteTech\ComAxe\Client\Oauth\Model\UserModel;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionUserProvider implements AxeUserProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    )
    {
    }

    public function loadUserFromStorage(string $identifier): UserModel
    {
        $user = $this->requestStack->getSession()->get('user');
        if ($user === null) {
            return new UserModel();
        }
        return unserialize($user);
    }

    public function setUserToStorage(UserModel $userModel): void
    {
        $this->requestStack->getSession()->set('user', serialize($userModel));
    }
}