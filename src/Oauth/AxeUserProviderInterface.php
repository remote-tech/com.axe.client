<?php

namespace RemoteTech\ComAxe\Client\Oauth;

use RemoteTech\ComAxe\Client\Oauth\Model\User;

interface AxeUserProviderInterface
{
    public function loadUserFromStorage(string $identifier);

    public function setUserToStorage(User $userModel);
}