<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase User (reglas de eliminación de cuenta)
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-userdeletion.md
 *
 * Convenio de IDs: UT-PHP-USR-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3, para que la salida --testdox de PHPUnit
 * muestre la línea "✔ UT-PHP-USR-NNN Título".
 *
 * Dependencias externas: NINGUNA. El bootstrap carga User.php directamente
 * con require_once. No se arranca sesión, BD ni headers HTTP.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class UserDeletionTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-001  deletionBlockReason() — target es null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-001 deletionBlockReason retorna error cuando el usuario objetivo es null
     */
    public function testDeletionBlockReasonTargetNuloRetornaUsuarioNoEncontrado(): void
    {
        $this->assertSame(
            'Usuario no encontrado.',
            \User::deletionBlockReason(null, 1, 2)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-002  deletionBlockReason() — auto-eliminación por mismo ID
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-002 deletionBlockReason impide que un usuario elimine su propia cuenta
     */
    public function testDeletionBlockReasonImpideAutoEliminacionMismoId(): void
    {
        $target = ['id' => 5, 'username' => 'editor1', 'role' => 'editor'];
        $this->assertSame(
            'No puede eliminar su propia cuenta.',
            \User::deletionBlockReason($target, 5, 2)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-003  deletionBlockReason() — ID como string numérico
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-003 deletionBlockReason maneja ID como string numérico en auto-eliminación
     */
    public function testDeletionBlockReasonManejaIdStringNumericoEnAutoEliminacion(): void
    {
        // En consultas PDO, los IDs pueden recibirse como strings numéricos
        $target = ['id' => '10', 'username' => 'jefe_salud', 'role' => 'program_head'];
        $this->assertSame(
            'No puede eliminar su propia cuenta.',
            \User::deletionBlockReason($target, 10, 2)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-004  deletionBlockReason() — bloqueo de último admin (adminCount = 1)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-004 deletionBlockReason bloquea la eliminación del último administrador si adminCount es 1
     */
    public function testDeletionBlockReasonBloqueaEliminacionDeUltimoAdmin(): void
    {
        $target = ['id' => 3, 'username' => 'admin_principal', 'role' => 'admin'];
        $this->assertSame(
            'No se puede eliminar al último administrador del sistema.',
            \User::deletionBlockReason($target, 1, 1)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-005  deletionBlockReason() — conteo de admin <= 0 (anomalía de conteo)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-005 deletionBlockReason bloquea la eliminación de admin si adminCount es menor o igual a cero
     */
    public function testDeletionBlockReasonBloqueaEliminacionDeAdminConConteoCeroONegativo(): void
    {
        $target = ['id' => 3, 'username' => 'admin_test', 'role' => 'admin'];
        $this->assertSame(
            'No se puede eliminar al último administrador del sistema.',
            \User::deletionBlockReason($target, 1, 0)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-006  deletionBlockReason() — permite eliminar admin si existen 2 o más
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-006 deletionBlockReason permite eliminar un administrador cuando existen 2 o más administradores
     */
    public function testDeletionBlockReasonPermiteEliminarAdminSiHayDosOMas(): void
    {
        $target = ['id' => 4, 'username' => 'admin_secundario', 'role' => 'admin'];
        $this->assertNull(
            \User::deletionBlockReason($target, 1, 2)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-007  deletionBlockReason() — permite eliminar usuario no-admin con adminCount = 1
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-007 deletionBlockReason permite eliminar un usuario no admin aunque solo exista un administrador
     */
    public function testDeletionBlockReasonPermiteEliminarUsuarioNoAdminConUnSoloAdmin(): void
    {
        $target = ['id' => 8, 'username' => 'jefe_turismo', 'role' => 'program_head'];
        $this->assertNull(
            \User::deletionBlockReason($target, 1, 1)
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-008  deletionBlockReason() — permite eliminar usuario con rol ausente o null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-008 deletionBlockReason permite eliminar un usuario con rol ausente o null si no es auto-eliminación
     */
    public function testDeletionBlockReasonPermiteEliminarUsuarioConRolAusenteONull(): void
    {
        $targetSinRol = ['id' => 9, 'username' => 'invitado'];
        $this->assertNull(\User::deletionBlockReason($targetSinRol, 1, 1));

        $targetRolNull = ['id' => 12, 'username' => 'sin_rol', 'role' => null];
        $this->assertNull(\User::deletionBlockReason($targetRolNull, 1, 1));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-USR-009  deletionBlockReason() — auto-eliminación prevalece sobre último admin
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-USR-009 deletionBlockReason prioriza la validación de auto-eliminación sobre la regla de último admin
     */
    public function testDeletionBlockReasonPriorizaAutoEliminacionSobreUltimoAdmin(): void
    {
        // El actor 1 intenta borrarse a sí mismo, siendo el único admin (adminCount = 1).
        // Debe primar la razón de auto-eliminación por orden de evaluación de guardas.
        $target = ['id' => 1, 'username' => 'admin_super', 'role' => 'admin'];
        $this->assertSame(
            'No puede eliminar su propia cuenta.',
            \User::deletionBlockReason($target, 1, 1)
        );
    }
}
