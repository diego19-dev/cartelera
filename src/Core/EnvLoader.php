<?php
/**
 * EnvLoader — Cargador de variables de entorno para CSET
 * Lee .env (nunca versionado) y lo expone via getenv()/$_ENV.
 * Mismo patrón que backend/core/EnvLoader.php de SmartCSET.
 */

class EnvLoader {
    private static bool $loaded = false;

    public static function load(string $path = ''): void {
        if (self::$loaded) {
            return;
        }

        if ($path === '') {
            $path = __DIR__ . '/.env';
        }

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (strlen($value) >= 2 &&
                    (($value[0] === '"' && str_ends_with($value, '"')) ||
                     ($value[0] === "'" && str_ends_with($value, "'")))) {
                    $value = substr($value, 1, -1);
                }
                if (!isset($_ENV[$key])) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        $value = getenv($key);
        return ($value !== false && $value !== '') ? $value : $default;
    }
}
