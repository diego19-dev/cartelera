<?php
/**
 * Bootstrap aislado para la suite unitarias_php.
 *
 * Carga únicamente las clases y constantes que necesita Security.php,
 * SIN arrancar sesión, base de datos ni headers HTTP. Esto permite
 * ejecutar los tests directamente con phpunit.phar (sin composer) igual
 * que se hizo en SMARTCSET, y también funciona cuando la suite se lanza
 * desde composer (las constantes ya definidas se saltan con defined()).
 */

// ── Constantes mínimas requeridas por Security y EnvLoader ────────────────
defined('BASE_PATH')    or define('BASE_PATH',    dirname(__DIR__, 3));
defined('APP_ENV')      or define('APP_ENV',      'testing');
defined('BCRYPT_COST')  or define('BCRYPT_COST',  12);

// ── Constantes requeridas por VideoDuration ────────────────────────────────
// Valores idénticos a los de src/Config.php para que los tests sean fieles
// al comportamiento real del sistema.
defined('MAX_VIDEO_DURATION_SECONDS') or define('MAX_VIDEO_DURATION_SECONDS', 600);
defined('MAX_SLIDE_DURATION_SECONDS') or define('MAX_SLIDE_DURATION_SECONDS', 600);
defined('MIN_SLIDE_DURATION_SECONDS') or define('MIN_SLIDE_DURATION_SECONDS', 5);

// TRUST_PROXY se lee desde EnvLoader en Security::clientIp(); simulamos 0
if (!isset($_ENV['TRUST_PROXY'])) {
    $_ENV['TRUST_PROXY'] = '0';
}

// ── Stub de Session ────────────────────────────────────────────────────────
// Security::verifyCsrf() y Security::csrfToken() llaman Session::has/get/set.
// En lugar de arrancar la sesión real (que requiere BD y Config completo),
// usamos un stub que opera directamente sobre $_SESSION. PHP permite asignar
// $_SESSION en CLI/tests sin session_start().
if (!class_exists('Session', false)) {
    class Session
    {
        /** @param mixed $default */
        public static function get(string $key, $default = null): mixed
        {
            return $_SESSION[$key] ?? $default;
        }

        /** @param mixed $value */
        public static function set(string $key, $value): void
        {
            $_SESSION[$key] = $value;
        }

        public static function has(string $key): bool
        {
            return isset($_SESSION[$key]);
        }
    }
}

// ── Clases requeridas (orden de dependencia) ───────────────────────────────
$srcPath = BASE_PATH . '/src';

require_once $srcPath . '/EnvLoader.php';
require_once $srcPath . '/Security.php';
require_once $srcPath . '/ContentValidity.php';
require_once $srcPath . '/VideoDuration.php';

// EnvLoader usa self::$loaded; lo inicializamos sin leer ningún .env
// (no existe en entorno de pruebas y no hace falta).
