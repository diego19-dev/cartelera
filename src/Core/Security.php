<?php
/**
 * Clase Security - Funciones de seguridad comunes
 */

class Security {

    /**
     * Genera un token CSRF único por sesión
     */
    public static function csrfToken(): string {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }

    /**
     * Verifica token CSRF (timing-safe)
     */
    public static function verifyCsrf(?string $token): bool {
        if (empty($token) || !Session::has('_csrf_token')) {
            return false;
        }
        return hash_equals(Session::get('_csrf_token'), $token);
    }

    /**
     * Renderiza el campo hidden CSRF
     */
    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    /**
     * Sanitiza entrada para evitar XSS
     */
    public static function sanitize(?string $text): string {
        if ($text === null) return '';
        return htmlspecialchars(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitiza recursivamente un array
     */
    public static function sanitizeArray(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            $cleanKey = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $key);
            // Never persist CSRF tokens into model/DB payloads
            if ($cleanKey === '' || $cleanKey === '_csrf') {
                continue;
            }
            if (is_array($value)) {
                $clean[$cleanKey] = self::sanitizeArray($value);
            } elseif ($cleanKey === 'password' || $cleanKey === 'password_hash') {
                // Nunca aplicar htmlspecialchars() a contraseñas: se hashean tal cual
                // las escribió el usuario. Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo A4 —
                // antes esto rompía el login de cualquier cuenta con &,<,>,",' en la clave.
                $clean[$cleanKey] = (string) $value;
            } else {
                $clean[$cleanKey] = self::sanitize($value);
            }
        }
        return $clean;
    }

    /**
     * Valida contraseña (mínimo 10 caracteres, combinación)
     */
    public static function isStrongPassword(string $password): bool {
        if (strlen($password) < 10) return false;
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasDigit = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
        return $hasLower && $hasUpper && $hasDigit && $hasSpecial;
    }

    /**
     * Genera una contraseña aleatoria segura
     */
    public static function generatePassword(int $length = 16): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }

    /**
     * Hash de contraseña (bcrypt)
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    /**
     * Verifica hash de contraseña
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Valida que un hash necesite rehash
     */
    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    /**
     * Genera token aleatorio criptográficamente seguro
     */
    public static function randomToken(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * IP real del cliente para auditoría/rate limiting.
     * Los headers X-Forwarded-For / X-Real-IP / CF-Connecting-IP son falsificables por
     * cualquier cliente si no hay un proxy de confianza en frente — solo se confía en
     * ellos si TRUST_PROXY=1 en .env (mismo criterio que SESION_TRUST_PROXY de SmartCSET).
     * Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo M2.
     */
    public static function clientIp(): string {
        if (EnvLoader::get('TRUST_PROXY', '0') === '1') {
            $candidates = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
            foreach ($candidates as $key) {
                if (!empty($_SERVER[$key])) {
                    $ip = trim(explode(',', $_SERVER[$key])[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function verifyCsrfRequest(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_POST['_csrf']
            ?? null;
        if (!self::verifyCsrf($token)) {
            Security::jsonResponse(['ok' => false, 'error' => 'Token CSRF inválido'], 403);
        }
    }

    /**
     * Lee JSON del body
     */
    public static function jsonBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Headers para JSON
     */
    public static function jsonResponse($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        /* Signage / proxies no deben cachear el feed ni el ping de la TV. */
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirige a una URL
     */
    public static function redirect(string $url, int $status = 302): void {
        header('Location: ' . $url, true, $status);
        exit;
    }

    /**
     * Verifica método HTTP
     */
    public static function requireMethod(string $method): void {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            http_response_code(405);
            die('Método no permitido');
        }
    }

    /**
     * Sanitiza nombre de archivo
     */
    public static function sanitizeFilename(string $filename): string {
        // Eliminar caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '._');
    }
}