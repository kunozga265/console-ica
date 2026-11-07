<?php

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a Firebase Auth ID token (Google / Apple sign-in in the app) against
 * Google's published signing certificates. Returns the token's claims.
 */
class FirebaseToken
{
    private const CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /** @return array{sub:string, email?:string, firebase?:array} */
    public static function verify(string $idToken): array
    {
        $project = config('services.firebase.project_id');
        $keys = collect(self::certificates())->map(fn ($pem) => new Key($pem, 'RS256'))->all();

        JWT::$leeway = 60;
        $claims = (array) JWT::decode($idToken, $keys);

        if (($claims['aud'] ?? null) !== $project || ($claims['iss'] ?? null) !== "https://securetoken.google.com/{$project}" || empty($claims['sub'])) {
            throw new \UnexpectedValueException('Token is not for this Firebase project');
        }

        return json_decode(json_encode($claims), true);
    }

    private static function certificates(): array
    {
        return Cache::remember('firebase-securetoken-certs', now()->addHours(6), function () {
            return Http::timeout(10)->get(self::CERTS_URL)->throw()->json();
        });
    }
}
