<?php
/**
 * CSET-TV — Pruebas Unitarias SQL: Restricciones de Tablas de Contenido
 * Suite: UnitariasSql  |  Estándar: ISO/IEC/IEEE 29119-3:2021
 *
 * Convenio de IDs: UT-SQL-CNT-NNN
 * Tablas evaluadas: academic_content, center_content, general_content, gallery_content, media_files
 */

namespace Tests\UnitariasSql;

require_once __DIR__ . '/bootstrap_sql.php';
require_once __DIR__ . '/SqlTestCase.php';

use PDOException;

class ContentConstraintsTest extends SqlTestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-001  academic_content.program_type CHECK
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-001 academic_content rechaza program_type fuera de la lista permitida
     */
    public function testAcademicProgramTypeCheckConstraint(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO academic_content (title, program_type) 
             VALUES ('Curso Invalido', 'semestral')" // 'semestral' no está en el CHECK
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-002  academic_content.media_type CHECK
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-002 academic_content rechaza media_type no admitido
     */
    public function testAcademicMediaTypeCheckConstraint(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type) 
             VALUES ('Taller Tecnico', 'quarterly', 'audio')" // 'audio' no permitido
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-003  center_content.media_type CHECK
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-003 center_content rechaza media_type fuera de image video text
     */
    public function testCenterMediaTypeCheckConstraint(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO center_content (sector, title, media_type) 
             VALUES ('salud', 'Induccion Centro', 'pdf')" // center_content no admite 'pdf'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-004  general_content.category CHECK
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-004 general_content rechaza categoria invalida
     */
    public function testGeneralCategoryCheckConstraint(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO general_content (category, title) 
             VALUES ('promocion', 'Aviso Invalido')" // 'promocion' no existe en categorías
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-005  Obligatoriedad NOT NULL en titulos y rutas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-005 gallery_content rechaza inserciones sin image_path por NOT NULL
     */
    public function testGalleryRequiresImagePathNotNull(): void
    {
        $this->expectException(PDOException::class);

        $this->execute(
            "INSERT INTO gallery_content (title, image_path) 
             VALUES ('Foto Sin Archivo', NULL)"
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-006  Valores por defecto en duration_seconds e is_active
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-006 Tablas de contenido aplican duration_seconds e is_active por defecto
     */
    public function testContentDefaultsApplication(): void
    {
        // 1. academic_content default duration = 15, is_active = TRUE
        $this->execute(
            "INSERT INTO academic_content (title, program_type, media_type) 
             VALUES ('Curso Defaults', 'quarterly', 'image')"
        );
        $academic = $this->fetchOne("SELECT duration_seconds, is_active FROM academic_content WHERE title = 'Curso Defaults'");
        $this->assertNotNull($academic);
        $this->assertSame(15, (int) $academic['duration_seconds']);
        $this->assertTrue($academic['is_active']);

        // 2. general_content default duration = 10, is_active = TRUE
        $this->execute(
            "INSERT INTO general_content (category, title) 
             VALUES ('news', 'Noticia Defaults')"
        );
        $general = $this->fetchOne("SELECT duration_seconds, is_active FROM general_content WHERE title = 'Noticia Defaults'");
        $this->assertNotNull($general);
        $this->assertSame(10, (int) $general['duration_seconds']);
        $this->assertTrue($general['is_active']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-SQL-CNT-007  media_files.filename  —  Restricción UNIQUE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-SQL-CNT-007 media_files exige unicidad estricta en filename
     */
    public function testMediaFilesFilenameUniqueConstraint(): void
    {
        $filename = 'media_' . uniqid() . '.jpg';

        $this->execute(
            "INSERT INTO media_files (filename, file_path) 
             VALUES (:f, '/uploads/test.jpg')",
            ['f' => $filename]
        );

        $this->expectException(PDOException::class);

        // Mismo nombre de archivo debe violar UNIQUE
        $this->execute(
            "INSERT INTO media_files (filename, file_path) 
             VALUES (:f, '/uploads/otro.jpg')",
            ['f' => $filename]
        );
    }
}

