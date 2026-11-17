<?php
/**
 * CSET-TV — Pruebas Unitarias SQL: Lógica de Negocio y Vistas de Cartelera
 * Suite: UnitariasSql  |  Estándar: ISO/IEC/IEEE 29119-3:2021
 *
 * Convenio de IDs: UT-SQL-VEW-NNN
 * Vistas evaluadas: v_active_academic, v_active_center, v_active_general, v_dashboard_stats
 */

namespace Tests\UnitariasSql;

require_once __DIR__ . '/bootstrap_sql.php';
require_once __DIR__ . '/SqlTestCase.php';

class ViewsBusinessLogicTest extends SqlTestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-001  v_active_academic  —  Exclusión de inactivos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-001 v_active_academic excluye registros donde is_active es false
     */
    public function testViewActiveAcademicExcluyeInactivos(): void
    {
        $tituloInactivo = 'Academico Inactivo ' . uniqid();
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type, is_active) 
             VALUES (:t, 'quarterly', 'image', FALSE)",
            ['t' => $tituloInactivo]
        );

        $encontrado = $this->fetchOne(
            "SELECT id FROM v_active_academic WHERE title = :t",
            ['t' => $tituloInactivo]
        );

        $this->assertNull($encontrado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-002  v_active_academic  —  Filtro por vigencia temporal
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-002 v_active_academic filtra adecuadamente por ventana show_from y show_until
     */
    public function testViewActiveAcademicFiltraVigencia(): void
    {
        // 1. Registro con vigencia futura (no debe aparecer aún)
        $tituloFuturo = 'Academico Futuro ' . uniqid();
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type, show_from, show_until) 
             VALUES (:t, 'quarterly', 'image', now() + INTERVAL '1 day', now() + INTERVAL '5 days')",
            ['t' => $tituloFuturo]
        );

        // 2. Registro vencido (no debe aparecer)
        $tituloVencido = 'Academico Vencido ' . uniqid();
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type, show_from, show_until) 
             VALUES (:t, 'quarterly', 'image', now() - INTERVAL '5 days', now() - INTERVAL '1 day')",
            ['t' => $tituloVencido]
        );

        // 3. Registro actualmente vigente (debe aparecer)
        $tituloVigente = 'Academico Vigente ' . uniqid();
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type, show_from, show_until) 
             VALUES (:t, 'quarterly', 'image', now() - INTERVAL '1 hour', now() + INTERVAL '1 hour')",
            ['t' => $tituloVigente]
        );

        $this->assertNull($this->fetchOne("SELECT id FROM v_active_academic WHERE title = :t", ['t' => $tituloFuturo]));
        $this->assertNull($this->fetchOne("SELECT id FROM v_active_academic WHERE title = :t", ['t' => $tituloVencido]));
        $this->assertNotNull($this->fetchOne("SELECT id FROM v_active_academic WHERE title = :t", ['t' => $tituloVigente]));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-003  v_active_center  —  Ordenamiento y vigencia
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-003 v_active_center ordena por display_order ascendente
     */
    public function testViewActiveCenterOrdenamiento(): void
    {
        $prefix = 'CenterOrder_' . uniqid() . '_';
        $this->execute(
            "INSERT INTO center_content (sector, title, media_type, display_order) 
             VALUES ('salud', :t1, 'image', 50), ('salud', :t2, 'image', 10)",
            ['t1' => $prefix . 'segundo', 't2' => $prefix . 'primero']
        );

        $filas = $this->fetchAll(
            "SELECT title FROM v_active_center WHERE title LIKE :p ORDER BY display_order ASC",
            ['p' => $prefix . '%']
        );

        $this->assertCount(2, $filas);
        $this->assertSame($prefix . 'primero', $filas[0]['title']);
        $this->assertSame($prefix . 'segundo', $filas[1]['title']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-004  v_active_general  —  Filtro por expire_date
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-004 v_active_general excluye noticias con expire_date vencido
     */
    public function testViewActiveGeneralFiltraExpirados(): void
    {
        $tituloExpirado = 'General Expirado ' . uniqid();
        $this->execute(
            "INSERT INTO general_content (category, title, expire_date) 
             VALUES ('news', :t, now() - INTERVAL '1 hour')",
            ['t' => $tituloExpirado]
        );

        $tituloVigente = 'General Vigente ' . uniqid();
        $this->execute(
            "INSERT INTO general_content (category, title, expire_date) 
             VALUES ('news', :t, now() + INTERVAL '1 hour')",
            ['t' => $tituloVigente]
        );

        $this->assertNull($this->fetchOne("SELECT id FROM v_active_general WHERE title = :t", ['t' => $tituloExpirado]));
        $this->assertNotNull($this->fetchOne("SELECT id FROM v_active_general WHERE title = :t", ['t' => $tituloVigente]));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-005  v_dashboard_stats  —  Cálculo de métricas globales
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-005 v_dashboard_stats calcula metricas de agregacion del sistema
     */
    public function testViewDashboardStatsAgregacion(): void
    {
        $stats = $this->fetchOne("SELECT * FROM v_dashboard_stats");

        $this->assertNotNull($stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('active_academic', $stats);
        $this->assertArrayHasKey('active_center', $stats);
        $this->assertArrayHasKey('active_general', $stats);
        $this->assertArrayHasKey('active_gallery', $stats);
        $this->assertArrayHasKey('total_media', $stats);
        $this->assertArrayHasKey('active_sessions', $stats);
        $this->assertArrayHasKey('activity_24h', $stats);

        $this->assertGreaterThanOrEqual(1, (int) $stats['active_users']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-VEW-006  Visibilidad en transacción viva
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-VEW-006 Vistas reflejan cambios inmediatamente dentro de la transaccion
     */
    public function testVistasReflejanCambiosTransaccionales(): void
    {
        $titulo = 'Transaccional_' . uniqid();
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type, is_active) 
             VALUES (:t, 'quarterly', 'image', TRUE)",
            ['t' => $titulo]
        );

        // La vista debe reflejar la fila insertada dentro de esta misma transacción
        $fila = $this->fetchOne("SELECT title FROM v_active_academic WHERE title = :t", ['t' => $titulo]);
        $this->assertNotNull($fila);
        $this->assertSame($titulo, $fila['title']);
    }
}
