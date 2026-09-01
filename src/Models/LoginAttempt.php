<?php
/**
 * LoginAttempt — Rate limiting real de login, por IP, en base de datos.
 * Reemplaza a Security::rateLimit() (basado en sesión PHP, evadible sin
 * enviar cookie). Ver AUDITORIA_SEGURIDAD_CSET.md (C3) / PLAN_BLINDAJE_CSET.md (E3).
 */

class LoginAttempt {

    /** Registra todo intento — exista o no el usuario, exitoso o no. */
    public static function record(
        string $ip,
        ?string $username,
        bool $success,
        ?string $failureReason,
        ?string $userAgent
    ): void {
        Database::getInstance()->insert('login_attempts', [
            'ip_address'     => $ip,
            'username'       => ($username !== null && $username !== '') ? $username : null,
            'success'        => $success,
            'failure_reason' => $success ? null : $failureReason,
            'user_agent'     => ($userAgent !== null && $userAgent !== '') ? $userAgent : null,
        ]);
        self::cleanupOld();
    }

    /** true si la IP superó el máximo de intentos fallidos en la ventana de minutos. */
    public static function isBlocked(string $ip, int $maxAttempts, int $windowMinutes): bool {
        $count = (int) Database::getInstance()->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = ? AND success = FALSE
               AND created_at > now() - make_interval(mins => ?::int)",
            [$ip, $windowMinutes]
        );
        return $count >= $maxAttempts;
    }

    /** Retención de 90 días — igual criterio que SmartCSET (tab_intentos_login). */
    private static function cleanupOld(): void {
        Database::getInstance()->query(
            "DELETE FROM login_attempts WHERE created_at < now() - INTERVAL '90 days'"
        );
    }
}
