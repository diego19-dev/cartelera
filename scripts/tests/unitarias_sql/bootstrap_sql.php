<?php
/**
 * Bootstrap para la suite de pruebas unitarias/integración SQL (PostgreSQL).
 * Suite: UnitariasSql
 *
 * Carga .env, inicializa la conexión PDO y provee acceso singleton al conector.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/src/EnvLoader.php';
EnvLoader::load(BASE_PATH . '/.env');

class TestDatabaseConnection
{
    private static ?PDO $pdo = null;

    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $host   = EnvLoader::get('DB_HOST', '127.0.0.1');
            $port   = EnvLoader::get('DB_PORT', '5432');
            $db     = EnvLoader::get('DB_NAME', 'db_cset');
            $user   = EnvLoader::get('DB_USER', 'postgres');
            $pass   = EnvLoader::get('DB_PASS', '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$pdo;
    }
}

