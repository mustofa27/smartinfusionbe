<?php

namespace App\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    private ?string $cachedAccessToken = null;

    private int $tokenExpiresAt = 0;

    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): array
    {
        $projectId = (string) config('services.fcm.project_id');
        $credentialsPath = (string) config('services.fcm.credentials_path');

        if ($projectId === '' || $credentialsPath === '' || ! file_exists($credentialsPath)) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => 'FCM configuration is missing or invalid.',
            ];
        }

        $accessToken = $this->getAccessToken($credentialsPath);
        if (! $accessToken) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => 'Unable to generate FCM access token.',
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => collect($data)->map(fn (mixed $value): string => (string) $value)->all(),
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'message_id' => $response->json('name'),
                'error' => null,
            ];
        }

        return [
            'ok' => false,
            'message_id' => null,
            'error' => $response->body(),
        ];
    }

    private function getAccessToken(string $credentialsPath): ?string
    {
        if ($this->cachedAccessToken && $this->tokenExpiresAt > (time() + 60)) {
            return $this->cachedAccessToken;
        }

        try {
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $credentialsPath,
            );
            $token = $credentials->fetchAuthToken();

            if (! isset($token['access_token'])) {
                return null;
            }

            $this->cachedAccessToken = (string) $token['access_token'];
            $this->tokenExpiresAt = isset($token['expires_in'])
                ? (time() + (int) $token['expires_in'])
                : (time() + 3000);

            return $this->cachedAccessToken;
        } catch (\Throwable $exception) {
            Log::error('Failed generating FCM access token', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
