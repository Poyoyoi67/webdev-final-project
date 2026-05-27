<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $googleWebClientId,
    ) {
    }

    /**
     * @return array{email: string, name: string|null, picture: string|null}
     */
    public function verify(string $idToken): array
    {
        if ($idToken === '') {
            throw new \InvalidArgumentException('Missing Google ID token.');
        }

        if ($this->googleWebClientId === '') {
            throw new \RuntimeException('GOOGLE_WEB_CLIENT_ID is not configured on the server.');
        }

        $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
            'query' => ['id_token' => $idToken],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Invalid Google ID token.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $aud = (string) ($payload['aud'] ?? '');
        if ($aud !== $this->googleWebClientId) {
            throw new \InvalidArgumentException('Google token audience does not match this app.');
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if ($emailVerified !== true && $emailVerified !== 'true') {
            throw new \InvalidArgumentException('Google account email is not verified.');
        }

        $email = (string) ($payload['email'] ?? '');
        if ($email === '') {
            throw new \InvalidArgumentException('Google token did not include an email.');
        }

        return [
            'email' => $email,
            'name' => isset($payload['name']) ? (string) $payload['name'] : null,
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
        ];
    }
}
