<?php
/**
 * Auditoría de acciones (función SQL log_activity) y errores a logs/.
 *
 * Si PostgreSQL falla, log() cae a activity-YYYY-MM-DD.log. error() siempre
 * escribe a archivo (no depende de BD).
 */
class Logger {

    /**
     * INSERT vía log_activity(...) en activity_log.
     *
     * @param array<string, mixed>|null $details Se serializa a JSON
     */
    public static function log(
        ?int $userId,
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?array $details = null
    ): void {
        try {
            Database::getInstance()->query(
                'SELECT log_activity(?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $action,
                    $table,
                    $recordId,
                    $details ? json_encode($details) : null,
                    Security::clientIp(),
                ]
            );
        } catch (Exception $e) {
            // Si falla, escribir a archivo
            self::writeToFile([
                'user_id'    => $userId,
                'action'     => $action,
                'table'      => $table,
                'record_id'  => $recordId,
                'details'    => $details,
                'ip'         => Security::clientIp(),
                'timestamp'  => date('c'),
            ]);
        }
    }

    /**
     * Fallback JSONL en LOG_PATH cuando falla la BD.
     *
     * @param array<string, mixed> $data
     */
    public static function writeToFile(array $data): void {
        $logFile = LOG_PATH . '/activity-' . date('Y-m-d') . '.log';
        $line = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Error crítico a errors-YYYY-MM-DD.log (JSONL).
     *
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void {
        $entry = [
            'level'     => 'ERROR',
            'message'   => $message,
            'context'   => $context,
            'timestamp' => date('c'),
        ];
        $logFile = LOG_PATH . '/errors-' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX);
    }

    /**
     * Últimas filas de activity_log con username/full_name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRecent(int $limit = 50): array {
        return Database::getInstance()->fetchAll(
            'SELECT al.*, u.username, u.full_name
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }
}