<?php

namespace Oauth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AuthAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AuthService     $authService,
        private readonly RouterInterface $router,
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $user = $this->authService->authenticateUser($request->query->get('code'));
        } catch (IdentityProviderException $ex){

            throw new  AuthenticationException($ex->getMessage());
        }
        $uuid = $user->getUuid();
        return new SelfValidatingPassport(new UserBadge($uuid));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->authService->generateLoginUrl());
    }
}