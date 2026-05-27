<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(default::GOOGLE_CLIENT_ID)%')]
        private readonly string $googleClientId,
        #[Autowire('%env(default::GOOGLE_CLIENT_SECRET)%')]
        private readonly string $googleClientSecret,
    ) {
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        if (trim($this->googleClientId) === '' || trim($this->googleClientSecret) === '') {
            $this->addFlash('error', 'Google login is not configured yet. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in Railway Variables.');

            return $this->redirectToRoute('app_login');
        }

        // Redirect to Google OAuth
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'openid',
                'email',
                'profile'
            ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): never
    {
        // This controller is never executed - it's intercepted by the GoogleAuthenticator
        throw new \LogicException('This should never be reached!');
    }
}