<?php
/**
 * Singleton PDO PostgreSQL. Lo usan todos los modelos y Logger.
 *
 * Conecta con constantes de Config (DB_HOST, etc.). Fetch asociativo,
 * excepciones PDO, sin prepares emulados. insert() hace RETURNING id.
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

    /** Única conexión por request; crea PDO en el primer llamado. */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** PDO crudo para casos que no cubren query/insert/update. */
    public function pdo(): PDO {
        return $this->pdo;
    }

    /**
     * Prepare + execute. Placeholders posicionales (?) o nombrados (:id).
     *
     * @param array<int|string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Una fila asociativa o null.
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array {
        $row = $this->query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Primer valor de la primera fila.
     *
     * @param array<int|string, mixed> $params
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * INSERT ... RETURNING id. Bindea bool/int/null con tipo PDO correcto.
     *
     * @param array<string, mixed> $data Columnas => valores (nombres de columna crudos)
     */
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

    /**
     * UPDATE; $where usa placeholders nombrados (:id) que van en $whereParams.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $whereParams
     * @return int Filas afectadas
     */
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

    /**
     * @param array<int|string, mixed> $params
     * @return int Filas borradas
     */
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
