<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FcmNotificationService
{
    private ?array $serviceAccount = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $firebaseProjectId,
        private readonly string $firebaseServiceAccountPath,
        private readonly string $firebaseServiceAccountJson,
    ) {
    }

    public function notifyUser(User $user, string $title, string $body, array $data = []): void
    {
        $token = $user->getFcmToken();
        if ($token === null || $token === '') {
            return;
        }

        if (!$this->isConfigured()) {
            $this->logger->info('FCM skipped (not configured)', ['user' => $user->getEmail(), 'title' => $title]);

            return;
        }

        try {
            $accessToken = $this->getAccessToken();
            $url = sprintf(
                'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                $this->firebaseProjectId,
            );

            $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data),
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'healthcare_bookings',
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('FCM send failed', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isConfigured(): bool
    {
        if ($this->firebaseProjectId === '') {
            return false;
        }

        if ($this->firebaseServiceAccountJson !== '') {
            return true;
        }

        return $this->firebaseServiceAccountPath !== ''
            && is_readable($this->firebaseServiceAccountPath);
    }

    private function getServiceAccount(): array
    {
        if ($this->serviceAccount !== null) {
            return $this->serviceAccount;
        }

        if ($this->firebaseServiceAccountJson !== '') {
            /** @var array<string, mixed> $account */
            $account = json_decode($this->firebaseServiceAccountJson, true, 512, JSON_THROW_ON_ERROR);
            $this->serviceAccount = $account;

            return $account;
        }

        $json = file_get_contents($this->firebaseServiceAccountPath);
        if ($json === false) {
            throw new \RuntimeException('Cannot read Firebase service account file.');
        }

        /** @var array<string, mixed> $account */
        $account = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->serviceAccount = $account;

        return $account;
    }

    private function getAccessToken(): string
    {
        $account = $this->getServiceAccount();
        $clientEmail = (string) ($account['client_email'] ?? '');
        $privateKey = (string) ($account['private_key'] ?? '');

        if ($clientEmail === '' || $privateKey === '') {
            throw new \RuntimeException('Invalid Firebase service account JSON.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claim;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('Failed to sign Firebase access JWT.');
        }

        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ]);

        /** @var array{access_token?: string} $data */
        $data = $response->toArray(false);
        $accessToken = $data['access_token'] ?? '';

        if ($accessToken === '') {
            throw new \RuntimeException('Firebase OAuth token exchange failed.');
        }

        return $accessToken;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
