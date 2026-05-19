<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // Find or create user
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    // Google users can be treated as verified (email comes from Google)
                    $user->setIsVerified(true);
                    // Set a random password (they won't use it for Google login)
                    $user->setPassword(bin2hex(random_bytes(32)));
                    // Google sign-in grants staff access (ROLE_USER is still implied by User::getRoles())
                    $user->setRoles(['ROLE_STAFF']);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                } else {
                    // On Google login, ensure staff role (do not downgrade admins)
                    $effective = $user->getRoles();
                    if (!\in_array('ROLE_ADMIN', $effective, true)) {
                        $toPersist = array_values(array_diff($effective, ['ROLE_USER']));
                        if (!\in_array('ROLE_STAFF', $toPersist, true)) {
                            $toPersist[] = 'ROLE_STAFF';
                            $user->setRoles($toPersist);
                            $this->entityManager->flush();
                        }
                    }
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_account_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('app_login'));
    }
}