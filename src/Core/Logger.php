<?php
/**
 * Clase Logger - Registro de actividad
 */

class Logger {

    /**
     * Registra acción del usuario
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
     * Escribe a archivo cuando falla BD
     */
    public static function writeToFile(array $data): void {
        $logFile = LOG_PATH . '/activity-' . date('Y-m-d') . '.log';
        $line = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Escribe error crítico
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
     * Obtiene últimos registros
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