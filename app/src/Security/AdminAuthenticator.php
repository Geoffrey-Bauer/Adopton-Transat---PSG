<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class AdminAuthenticator extends AbstractAuthenticator
{
  private $urlGenerator;

  public function __construct(UrlGeneratorInterface $urlGenerator)
  {
    $this->urlGenerator = $urlGenerator;
  }

  public function supports(Request $request): ?bool
  {
    return $request->attributes->get('_route') === 'admin_login' && $request->isMethod('POST');
  }

  public function authenticate(Request $request): Passport
  {
    $password = $request->request->get('password', '');
    if (trim($password) === '') {
      throw new CustomUserMessageAuthenticationException('Le mot de passe ne peut pas être vide.');
    }

    return new Passport(
      new UserBadge('admin'),
      new PasswordCredentials($password)
    );
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
  {
    return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
  {
    $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
    return new RedirectResponse($this->urlGenerator->generate('admin_login'));
  }
}