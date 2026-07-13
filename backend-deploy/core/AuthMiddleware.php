<?php

class AuthMiddleware
{
    public static function authenticate(): array
    {
        $header = Request::getHeader('Authorization');
        if (!$header) {
            Response::error('Authorization header required', 401);
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            Response::error('Invalid authorization format', 401);
        }

        $token = $matches[1];
        $payload = JWT::decode($token);

        if ($payload === null) {
            Response::error('Invalid or expired token', 401);
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT id, is_active FROM users WHERE id = ?", [$payload['user_id']]);

        if (!$user || !$user['is_active']) {
            Response::error('User not found or inactive', 401);
        }

        return $payload;
    }

    public static function requirePermission(string $permission): array
    {
        $payload = self::authenticate();

        if (($payload['role_name'] ?? '') === 'super_admin') {
            return $payload;
        }

        $db = Database::getInstance();
        $hasPermission = $db->fetch(
            "SELECT 1 FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE r.name = ? AND p.name = ?",
            [$payload['role_name'] ?? '', $permission]
        );

        if (!$hasPermission) {
            Response::error('Insufficient permissions', 403);
        }

        return $payload;
    }
}
