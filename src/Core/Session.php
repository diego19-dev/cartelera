<?php
/**
 * Clase Session - Manejo seguro de sesiones PHP
 */

class Session {

    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => COOKIE_SECURE,
            'httponly' => COOKIE_HTTPONLY,
            'samesite' => COOKIE_SAMESITE,
        ]);

        // Configuración segura de PHP session
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

        session_start();

        // Regenerar ID periódicamente
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        } elseif (time() - $_SESSION['_created_at'] > 1800) {
            self::regenerate();
        }
    }

    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
        }
    }

    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $value = null) {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $msg = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isAuthenticated(): bool {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }

    public static function hasRole(string|array $roles): bool {
        if (!self::isAuthenticated()) {
            return false;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($_SESSION['user_role'], $roles, true);
    }

    public static function userId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function userRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public static function userProgramArea(): ?string {
        return $_SESSION['user_program_area'] ?? null;
    }

    public static function userName(): ?string {
        return $_SESSION['user_name'] ?? null;
    }
}