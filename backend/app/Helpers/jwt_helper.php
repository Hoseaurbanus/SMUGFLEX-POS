<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generate_jwt')) {
    function generate_jwt(array $payload): string
    {
        $secret = getenv('JWT_SECRET');
        $expiration = (int) getenv('JWT_EXPIRATION');

        $payload['iss'] = 'smugflex-pos';
        $payload['iat'] = time();
        $payload['exp'] = time() + ($expiration ?: 86400);
        $payload['jti'] = bin2hex(random_bytes(16));

        return JWT::encode($payload, $secret, 'HS256');
    }
}

if (!function_exists('generate_refresh_jwt')) {
    function generate_refresh_jwt(array $payload): string
    {
        $secret = getenv('JWT_SECRET');
        $expiration = (int) getenv('JWT_REFRESH_EXPIRATION');

        $payload['iss'] = 'smugflex-pos';
        $payload['iat'] = time();
        $payload['exp'] = time() + ($expiration ?: 604800);
        $payload['type'] = 'refresh';

        return JWT::encode($payload, $secret, 'HS256');
    }
}

if (!function_exists('verify_jwt')) {
    function verify_jwt(string $token): ?object
    {
        $secret = getenv('JWT_SECRET');

        try {
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('get_user_id_from_token')) {
    function get_user_id_from_token(): ?int
    {
        $authHeader = service('request')->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $decoded = verify_jwt($token);

        return $decoded ? (int) ($decoded->user_id ?? 0) : null;
    }
}
