<?php
/**
 * Rate limiting de login por IP (tabla login_attempts), no por cookie de sesión.
 *
 * Lo usa AuthController antes de Auth::attempt(). LOGIN_MAX_ATTEMPTS /
 * LOGIN_WINDOW_MINUTES en Config. En development no bloquea loopback.
 *
 * @see docs/AUDITORIA_SEGURIDAD_CSET.md hallazgo C3
 */
class LoginAttempt {

    /**
     * Inserta el intento (éxito o fallo) y expurga filas de más de 90 días.
     *
     * @param string|null $failureReason p. ej. usuario_no_existe, contrasena_incorrecta
     */
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
        if (self::shouldSkipRateLimit($ip)) {
            return false;
        }
        $count = (int) Database::getInstance()->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = ? AND success = FALSE
               AND created_at > now() - make_interval(mins => ?::int)",
            [$ip, $windowMinutes]
        );
        return $count >= $maxAttempts;
    }

    /** En desarrollo local no bloquear loopback (evita quedar fuera tras pruebas). */
    public static function shouldSkipRateLimit(string $ip): bool {
        if (defined('LOGIN_RATE_LIMIT_DISABLED_UNTIL') && LOGIN_RATE_LIMIT_DISABLED_UNTIL !== '') {
            $until = strtotime(LOGIN_RATE_LIMIT_DISABLED_UNTIL);
            if ($until !== false && time() < $until) {
                return true;
            }
        }
        if (APP_ENV !== 'development') {
            return false;
        }
        return in_array($ip, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'], true);
    }

    /** Limpia intentos fallidos recientes de una IP (desbloqueo manual). */
    public static function clearFailuresForIp(string $ip, int $windowMinutes = 15): int {
        return Database::getInstance()->query(
            "DELETE FROM login_attempts
             WHERE ip_address = ? AND success = FALSE
               AND created_at > now() - make_interval(mins => ?::int)",
            [$ip, $windowMinutes]
        )->rowCount();
    }

    /** Retención de 90 días — igual criterio que SmartCSET (tab_intentos_login). */
    private static function cleanupOld(): void {
        Database::getInstance()->query(
            "DELETE FROM login_attempts WHERE created_at < now() - INTERVAL '90 days'"
        );
    }
}
