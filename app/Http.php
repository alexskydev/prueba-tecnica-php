<?php
/** Helpers HTTP compartidos. PHP 7.4+. */
final class Http
{
    public static function postString(string $key): string
    {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    public static function getString(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
    }

    public static function csrfToken(): string
    {
        return $_SESSION['csrf_token'];
    }

    public static function validCsrf(): bool
    {
        $providedToken = self::postString('csrf_token');
        return hash_equals(self::csrfToken(), $providedToken);
    }

    public static function escapeHtml($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function json(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
