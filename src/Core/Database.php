<?php
/**
 * Clase Database - Singleton para conexión PDO PostgreSQL
 */

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        try {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',DB_HOST, DB_PORT, DB_NAME);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Search path (schema)
            if (DB_SCHEMA && DB_SCHEMA !== 'public') {
                $this->pdo->exec('SET search_path TO ' . DB_SCHEMA . ', public');
            }
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            if (APP_DEBUG) {
                throw $e;
            }
            http_response_code(500);
            die(json_encode([
                'ok'    => false,
                'error' => 'Error de conexión a la base de datos'
            ]));
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array {
        $row = $this->query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function insert(string $table, array $data): int {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );
        $stmt = $this->pdo->prepare($sql);
        // Bind explícito para tipos correctos (especialmente boolean)
        $this->bindValues($stmt, $data);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = "$col = :$col";
        }
        $allParams = array_merge($data, $whereParams);
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt, $allParams);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Bind de parámetros con tipos PDO correctos
     * Crítico para booleanos que PDO no convierte automáticamente
     */
    private function bindValues(\PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $value) {
            $pdoType = match (true) {
                is_bool($value)   => PDO::PARAM_BOOL,
                is_int($value)    => PDO::PARAM_INT,
                is_null($value)   => PDO::PARAM_NULL,
                default           => PDO::PARAM_STR,
            };
            $stmt->bindValue(':' . $key, $value, $pdoType);
        }
    }

    public function delete(string $table, string $where, array $params = []): int {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollback(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function __clone() {}
}
