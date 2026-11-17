<?php
/**
 * CSET-TV — Pruebas Unitarias SQL: Configuración, Auditoría y Rate Limiting
 * Suite: UnitariasSql  |  Estándar: ISO/IEC/IEEE 29119-3:2021
 *
 * Convenio de IDs: UT-SQL-CFG-NNN
 * Tablas evaluadas: display_config, login_attempts, activity_log
 */

namespace Tests\UnitariasSql;

require_once __DIR__ . '/bootstrap_sql.php';
require_once __DIR__ . '/SqlTestCase.php';

use PDOException;

class DisplayConfigAuditTest extends SqlTestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CFG-001  display_config.config_key  —  Restricción UNIQUE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CFG-001 display_config exige unicidad en config_key
     */
    public function testDisplayConfigKeyUnique(): void
    {
        $key = 'cfg_key_' . uniqid();

        $this->execute(
            "INSERT INTO display_config (config_key, config_value, description) 
             VALUES (:k, 'valor_1', 'Configuracion de prueba')",
            ['k' => $key]
        );

        $this->expectException(PDOException::class);

        // Clave duplicada debe violar UNIQUE
        $this->execute(
            "INSERT INTO display_config (config_key, config_value, description) 
             VALUES (:k, 'valor_2', 'Configuracion duplicada')",
            ['k' => $key]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CFG-002  display_config.updated_by  —  Auditoría de usuario
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CFG-002 display_config vincula correctamente updated_by con la tabla users
     */
    public function testDisplayConfigUpdatedByRelation(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $key = 'cfg_audit_' . uniqid();

        $this->execute(
            "INSERT INTO display_config (config_key, config_value, updated_by) 
             VALUES (:k, 'activo', :uid)",
            ['k' => $key, 'uid' => $userId]
        );

        $row = $this->fetchOne("SELECT * FROM display_config WHERE config_key = :k", ['k' => $key]);
        $this->assertNotNull($row);
        $this->assertSame($userId, (int) $row['updated_by']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CFG-003  login_attempts  —  Registro de intentos exitosos y fallidos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CFG-003 login_attempts almacena intentos fallidos con ip y failure_reason
     */
    public function testLoginAttemptsRegistroFallos(): void
    {
        $ip = '10.0.5.88';
        $usuario = 'atacante_test_' . uniqid();
        $motivo = 'invalid_password';

        $this->execute(
            "INSERT INTO login_attempts (ip_address, username, success, failure_reason) 
             VALUES (:ip, :u, FALSE, :r)",
            [
                'ip' => $ip,
                'u'  => $usuario,
                'r'  => $motivo,
            ]
        );

        $intento = $this->fetchOne(
            "SELECT * FROM login_attempts WHERE ip_address = :ip AND username = :u",
            ['ip' => $ip, 'u' => $usuario]
        );

        $this->assertNotNull($intento);
        $this->assertFalse($intento['success']);
        $this->assertSame($motivo, $intento['failure_reason']);
        $this->assertNotNull($intento['created_at']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CFG-004  login_attempts  —  Consultas eficientes por ventana de tiempo
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CFG-004 login_attempts calcula fallos acumulados en ventana de tiempo para rate limiting
     */
    public function testLoginAttemptsVentanaRateLimit(): void
    {
        $ip = '192.168.99.12';

        // 3 fallos recientes (últimos 5 min)
        for ($i = 0; $i < 3; $i++) {
            $this->execute(
                "INSERT INTO login_attempts (ip_address, username, success, failure_reason, created_at) 
                 VALUES (:ip, 'admin', FALSE, 'bad_pass', now() - INTERVAL '2 minutes')",
                ['ip' => $ip]
            );
        }

        // 1 fallo antiguo (hace 2 horas)
        $this->execute(
            "INSERT INTO login_attempts (ip_address, username, success, failure_reason, created_at) 
             VALUES (:ip, 'admin', FALSE, 'bad_pass', now() - INTERVAL '2 hours')",
            ['ip' => $ip]
        );

        // Contar fallos en la ventana de 15 minutos
        $fallosVentana = (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE ip_address = :ip AND success = FALSE AND created_at > now() - INTERVAL '15 minutes'",
            ['ip' => $ip]
        );

        $this->assertSame(3, $fallosVentana);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CFG-005  activity_log  —  Índices y consultas de auditoría por acción
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CFG-005 activity_log registra y recupera eventos ordenados cronologicamente
     */
    public function testActivityLogRecuperacionCronologica(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $prefijo = 'AUDIT_CRON_' . uniqid() . '_';

        $this->execute(
            "INSERT INTO activity_log (user_id, action, created_at) 
             VALUES (:uid, :a1, now() - INTERVAL '10 minutes'),
                    (:uid, :a2, now() - INTERVAL '1 minute')",
            [
                'uid' => $userId,
                'a1'  => $prefijo . 'PASO_1',
                'a2'  => $prefijo . 'PASO_2',
            ]
        );

        $logs = $this->fetchAll(
            "SELECT action FROM activity_log WHERE user_id = :uid AND action LIKE :p ORDER BY created_at ASC",
            [
                'uid' => $userId,
                'p'   => $prefijo . '%',
            ]
        );

        $this->assertCount(2, $logs);
        $this->assertSame($prefijo . 'PASO_1', $logs[0]['action']);
        $this->assertSame($prefijo . 'PASO_2', $logs[1]['action']);
    }
}
