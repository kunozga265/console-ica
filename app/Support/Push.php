<?php

namespace App\Support;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1) to topics. The app subscribes to:
 *   USER-{id}         personal (birthday wishes, receipts, event reminders)
 *   SERMONS-MIN-{id}  sermons per ministry
 *   PRAYER, EVENTS, GENERAL-V2, CELL-{code}
 *   general           legacy: apps before 1.4.2 get every broadcast here
 *
 * Credentials: FIREBASE_CREDENTIALS (path to the service-account JSON, kept
 * out of git). Without them pushes are logged and skipped. Tests use fake().
 */
class Push
{
    /** @var array<int, array{topic:string, title:string, body:string, data:array}>|null */
    private static ?array $fake = null;

    public static function fake(): void
    {
        self::$fake = [];
    }

    /** @return array<int, array{topic:string, title:string, body:string, data:array}> */
    public static function sent(): array
    {
        return self::$fake ?? [];
    }

    public static function toUser(int $userId, string $title, string $body, array $data = []): void
    {
        self::toTopic("USER-{$userId}", $title, $body, $data);
    }

    public static function toTopic(string $topic, string $title, string $body, array $data = []): void
    {
        // FCM data values must be strings.
        $data = array_map('strval', array_filter($data, fn ($v) => $v !== null));

        if (self::$fake !== null) {
            self::$fake[] = compact('topic', 'title', 'body', 'data');

            return;
        }

        $project = config('services.firebase.project_id');
        $token = self::accessToken();
        if (! $project || ! $token) {
            Log::info('Push skipped (Firebase not configured)', compact('topic', 'title'));

            return;
        }

        try {
            $response = Http::withToken($token)->timeout(10)->post(
                "https://fcm.googleapis.com/v1/projects/{$project}/messages:send",
                ['message' => [
                    'topic'        => $topic,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data'         => (object) $data,
                    'android'      => ['priority' => 'high'],
                    'apns'         => ['payload' => ['aps' => ['sound' => 'default']]],
                ]]
            );
            if ($response->failed()) {
                Log::warning('Push failed', ['topic' => $topic, 'status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Push failed', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }

    /** OAuth token for the service account, cached just under its 1h lifetime. */
    private static function accessToken(): ?string
    {
        $path = config('services.firebase.credentials');
        if (! $path || ! is_readable($path)) {
            return null;
        }

        return Cache::remember('fcm-access-token', now()->addMinutes(50), function () use ($path) {
            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                json_decode(file_get_contents($path), true)
            );

            return $credentials->fetchAuthToken()['access_token'] ?? null;
        });
    }
}
