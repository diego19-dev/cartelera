<?php
/**
 * CSET-TV — Pruebas Unitarias SQL: Procedimientos Almacenados y Funciones PL/pgSQL
 * Suite: UnitariasSql  |  Estándar: ISO/IEC/IEEE 29119-3:2021
 *
 * Convenio de IDs: UT-SQL-FNC-NNN
 * Funciones evaluadas: log_activity, cleanup_expired_sessions
 */

namespace Tests\UnitariasSql;

require_once __DIR__ . '/bootstrap_sql.php';
require_once __DIR__ . '/SqlTestCase.php';

class StoredFunctionsTest extends SqlTestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-FNC-001  log_activity()  —  Registro mínimo requerido
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-FNC-001 log_activity inserta exitosamente en activity_log con datos minimos
     */
    public function testLogActivityDatosMinimos(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $action = 'TEST_ACTION_' . uniqid();

        $this->execute(
            "SELECT log_activity(:uid, :act)",
            ['uid' => $userId, 'act' => $action]
        );

        $log = $this->fetchOne(
            "SELECT * FROM activity_log WHERE user_id = :uid AND action = :act",
            ['uid' => $userId, 'act' => $action]
        );

        $this->assertNotNull($log);
        $this->assertSame($action, $log['action']);
        $this->assertNull($log['table_name']);
        $this->assertNull($log['record_id']);
        $this->assertNull($log['details']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-FNC-002  log_activity()  —  Metadatos JSONB e IP de auditoría
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-FNC-002 log_activity almacena correctamente JSONB estructurado y direccion INET
     */
    public function testLogActivityConDetallesJsonbEIp(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $action = 'UPDATE_SETTINGS_' . uniqid();
        $detailsJson = json_encode(['modulo' => 'academic', 'operacion' => 'toggle_active', 'ip_vpn' => true]);
        $ip = '192.168.1.100';

        $this->execute(
            "SELECT log_activity(:uid, :act, 'academic_content', 42, :det::jsonb, :ip::inet)",
            [
                'uid' => $userId,
                'act' => $action,
                'det' => $detailsJson,
                'ip'  => $ip,
            ]
        );

        $log = $this->fetchOne(
            "SELECT * FROM activity_log WHERE action = :act",
            ['act' => $action]
        );

        $this->assertNotNull($log);
        $this->assertSame('academic_content', $log['table_name']);
        $this->assertSame(42, (int) $log['record_id']);
        $this->assertSame($ip, $log['ip_address']);

        $detalles = json_decode($log['details'], true);
        $this->assertIsArray($detalles);
        $this->assertSame('academic', $detalles['modulo']);
        $this->assertTrue($detalles['ip_vpn']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-FNC-003  cleanup_expired_sessions()  —  Purga de expiradas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-FNC-003 cleanup_expired_sessions elimina sesiones con expires_at anterior a now
     */
    public function testCleanupExpiredSessionsPurgaExpiradas(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $tokenExpirado = 'expired_' . uniqid();

        // Inserción de sesión caducada
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (:uid, :tok, now() - INTERVAL '1 hour')",
            ['uid' => $userId, 'tok' => $tokenExpirado]
        );

        // Ejecutar función PL/pgSQL
        $this->execute("SELECT cleanup_expired_sessions()");

        // Verificar que ya no existe
        $existe = $this->fetchOne("SELECT id FROM user_sessions WHERE token = :tok", ['tok' => $tokenExpirado]);
        $this->assertNull($existe);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-FNC-004  cleanup_expired_sessions()  —  Preserva activas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-FNC-004 cleanup_expired_sessions conserva sesiones vigentes sin afectarlas
     */
    public function testCleanupExpiredSessionsPreservaActivas(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $tokenActivo = 'active_' . uniqid();

        // Inserción de sesión vigente por 2 horas
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (:uid, :tok, now() + INTERVAL '2 hours')",
            ['uid' => $userId, 'tok' => $tokenActivo]
        );

        // Ejecutar función
        $this->execute("SELECT cleanup_expired_sessions()");

        // Debe seguir intacta
        $existe = $this->fetchOne("SELECT id FROM user_sessions WHERE token = :tok", ['tok' => $tokenActivo]);
        $this->assertNotNull($existe);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-FNC-005  log_activity()  —  Tolerancia a actor nulo o sistema
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-FNC-005 log_activity permite registrar acciones del sistema con user_id null
     */
    public function testLogActivityAccionSistemaUserIdNull(): void
    {
        $action = 'CRON_MAINTENANCE_' . uniqid();

        $this->execute(
            "SELECT log_activity(NULL, :act, NULL, NULL, '{\"job\":\"cleanup\"}'::jsonb, NULL)",
            ['act' => $action]
        );

        $log = $this->fetchOne("SELECT * FROM activity_log WHERE action = :act", ['act' => $action]);
        $this->assertNotNull($log);
        $this->assertNull($log['user_id']);
        $this->assertSame($action, $log['action']);
    }
}
