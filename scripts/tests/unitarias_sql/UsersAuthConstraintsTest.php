<?php
/**
 * CSET-TV — Pruebas Unitarias SQL: Restricciones de Usuarios, Roles y Sesiones
 * Suite: UnitariasSql  |  Estándar: ISO/IEC/IEEE 29119-3:2021
 *
 * Convenio de IDs: UT-SQL-USR-NNN
 * Tabla(s) evaluada(s): users, user_sessions
 */

namespace Tests\UnitariasSql;

require_once __DIR__ . '/bootstrap_sql.php';
require_once __DIR__ . '/SqlTestCase.php';

use PDOException;

class UsersAuthConstraintsTest extends SqlTestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-001  users.role  —  CHECK (role IN ('admin','program_head','editor'))
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-001 Rechazo de usuarios con rol no permitido por restriccion CHECK
     */
    public function testCheckRoleRechazaRolesInvalidos(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO users (username, password_hash, full_name, role) 
             VALUES (:u, :p, :n, :r)",
            [
                'u' => 'test_invalido_' . uniqid(),
                'p' => '$2y$10$hashdeprueba1234567890123456789012345678901234567890',
                'n' => 'Usuario Rol No Permitido',
                'r' => 'superadmin', // Rol no permitido
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-002  users.username  —  Restricción UNIQUE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-002 Unicidad estricta de nombre de usuario en users
     */
    public function testUsernameUniqueConstraint(): void
    {
        $username = 'usuario_unico_' . uniqid();

        // Primera inserción válida
        $this->execute(
            "INSERT INTO users (username, password_hash, full_name, role) 
             VALUES (:u, :p, :n, :r)",
            [
                'u' => $username,
                'p' => '$2y$10$hashdeprueba1234567890123456789012345678901234567890',
                'n' => 'Usuario Original',
                'r' => 'editor',
            ]
        );

        // Segunda inserción con el mismo username debe arrojar excepción por UNIQUE
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO users (username, password_hash, full_name, role) 
             VALUES (:u, :p, :n, :r)",
            [
                'u' => $username,
                'p' => '$2y$10$hashdeprueba1234567890123456789012345678901234567890',
                'n' => 'Usuario Duplicado',
                'r' => 'editor',
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-003  user_sessions  —  ON DELETE CASCADE de users a user_sessions
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-003 Cascada de eliminacion ON DELETE CASCADE de users a user_sessions
     */
    public function testOnDeleteCascadeUsersToSessions(): void
    {
        // 1. Insertar usuario temporal
        $username = 'usuario_cascade_' . uniqid();
        $this->execute(
            "INSERT INTO users (username, password_hash, full_name, role) 
             VALUES (:u, :p, :n, :r)",
            [
                'u' => $username,
                'p' => '$2y$10$hashdeprueba1234567890123456789012345678901234567890',
                'n' => 'Usuario Para Borrar',
                'r' => 'editor',
            ]
        );
        $userId = (int) $this->fetchColumn("SELECT id FROM users WHERE username = :u", ['u' => $username]);
        $this->assertGreaterThan(0, $userId);

        // 2. Insertar sesión vinculada
        $token = 'tok_' . bin2hex(random_bytes(16));
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (:uid, :tok, now() + INTERVAL '2 hours')",
            [
                'uid' => $userId,
                'tok' => $token,
            ]
        );

        $sessionId = (int) $this->fetchColumn("SELECT id FROM user_sessions WHERE token = :tok", ['tok' => $token]);
        $this->assertGreaterThan(0, $sessionId);

        // 3. Eliminar usuario
        $this->execute("DELETE FROM users WHERE id = :uid", ['uid' => $userId]);

        // 4. Verificar que la sesión fue eliminada automáticamente por CASCADE
        $remainingSessions = $this->fetchColumn("SELECT COUNT(*) FROM user_sessions WHERE id = :sid", ['sid' => $sessionId]);
        $this->assertSame(0, (int) $remainingSessions);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-004  users  —  Inserción válida con área y valores por defecto
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-004 Insercion valida de usuario program_head y valores por defecto
     */
    public function testInsertValidoProgramHeadDefaults(): void
    {
        $username = 'jp_test_' . uniqid();
        $this->execute(
            "INSERT INTO users (username, password_hash, full_name, role, program_area) 
             VALUES (:u, :p, :n, :r, :a)",
            [
                'u' => $username,
                'p' => '$2y$10$hashdeprueba1234567890123456789012345678901234567890',
                'n' => 'Jefe de Prueba',
                'r' => 'program_head',
                'a' => 'tecnologia',
            ]
        );

        $user = $this->fetchOne("SELECT * FROM users WHERE username = :u", ['u' => $username]);
        $this->assertNotNull($user);
        $this->assertTrue($user['is_active']);
        $this->assertSame('program_head', $user['role']);
        $this->assertSame('tecnologia', $user['program_area']);
        $this->assertNotNull($user['created_at']);
        $this->assertNotNull($user['updated_at']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-005  user_sessions.token  —  Restricción UNIQUE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-005 Unicidad estricta de token en user_sessions
     */
    public function testTokenUniqueConstraint(): void
    {
        $user = $this->fetchOne("SELECT id FROM users LIMIT 1");
        $userId = (int) $user['id'];
        $token = 'duptoken_' . bin2hex(random_bytes(10));

        // Primera inserción
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (:uid, :tok, now() + INTERVAL '1 hour')",
            ['uid' => $userId, 'tok' => $token]
        );

        // Segunda inserción con token repetido debe fallar
        $this->expectException(PDOException::class);
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (:uid, :tok, now() + INTERVAL '1 hour')",
            ['uid' => $userId, 'tok' => $token]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-USR-006  user_sessions.user_id  —  Restricción FOREIGN KEY
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-USR-006 Rechazo de creacion de sesion con user_id inexistente
     */
    public function testForeignKeyUserIdInexistente(): void
    {
        $this->expectException(PDOException::class);

        // ID no existente en users
        $this->execute(
            "INSERT INTO user_sessions (user_id, token, expires_at) 
             VALUES (99999999, :tok, now() + INTERVAL '1 hour')",
            ['tok' => 'token_huerfano_' . uniqid()]
        );
    }
}

