<?php

namespace RemoteTech\ComAxe\Client\Oauth;


use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessEventListener implements EventSubscriberInterface
{
    // set the app widget cookie
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var \RemoteTech\ComAxe\Client\Oauth\Model\UserModel $user */
        $user = $event->getUser();
        $cookie = new Cookie(
            name: 'rt_app_widget',
            value: $user->getToken(),
            expire: strtotime('+1 day'),
            path: '/',
            httpOnly: false,
        );

        $response = $event->getResponse();

        $response->headers->setCookie($cookie);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
