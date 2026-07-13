<?php

function api_success(mixed $data = null, string $message = 'Success', int $code = 200): void
{
    Response::success($data, $message, $code);
}

function api_error(string $message = 'Error', int $code = 400, mixed $errors = null): void
{
    Response::error($message, $code, $errors);
}

function paginated_response(mixed $data, int $total, int $page, int $perPage): void
{
    Response::paginated($data, $total, $page, $perPage);
}

function format_currency(float $amount): string
{
    return number_format($amount, 2, '.', ',');
}

function generate_reference(string $prefix = 'REF'): string
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

function get_user_id(): ?int
{
    $header = Request::getHeader('Authorization');
    if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return null;
    }
    $payload = JWT::decode($matches[1]);
    return $payload['user_id'] ?? null;
}

function has_permission(string $permission): bool
{
    $header = Request::getHeader('Authorization');
    if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return false;
    }
    $payload = JWT::decode($matches[1]);
    if (!$payload) {
        return false;
    }
    if (($payload['role_name'] ?? '') === 'super_admin') {
        return true;
    }
    $db = Database::getInstance();
    $result = $db->fetch(
        "SELECT 1 FROM role_permissions rp
         JOIN roles r ON r.id = rp.role_id
         JOIN permissions p ON p.id = rp.permission_id
         WHERE r.name = ? AND p.name = ?",
        [$payload['role_name'] ?? '', $permission]
    );
    return (bool)$result;
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}
