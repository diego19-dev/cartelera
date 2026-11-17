<?php
/**
 * Clase base para pruebas de Base de Datos y SQL con aislamiento transaccional.
 *
 * Cada test abre una transacción en setUp() y la revierte en tearDown() (ROLLBACK),
 * asegurando cero contaminación o persistencia de datos en la base de datos de pruebas.
 */

namespace Tests\UnitariasSql;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class SqlTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$pdo = \TestDatabaseConnection::getPdo();
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$pdo->inTransaction()) {
            self::$pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Ejecuta una sentencia SQL con parámetros preparados.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Obtiene una sola fila como array asociativo.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene todas las filas resultantes.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el valor de una sola columna.
     */
    protected function fetchColumn(string $sql, array $params = [], int $colNumber = 0): mixed
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($colNumber);
    }
}

