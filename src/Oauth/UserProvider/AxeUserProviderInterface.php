<?php

namespace RemoteTech\ComAxe\Client\Oauth\UserProvider;

use RemoteTech\ComAxe\Client\Oauth\Model\UserModel;

interface AxeUserProviderInterface
{
    public function loadUserFromStorage(string $identifier): UserModel;

    public function setUserToStorage(UserModel $userModel);
}