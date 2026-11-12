<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase ContentDisplayOrder
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-contentdisplayorder.md
 *
 * Convenio de IDs: UT-PHP-CDO-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class ContentDisplayOrderTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-001  assertTable()  —  acepta academic_content
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-001 assertTable acepta academic_content como tabla valida
     */
    public function testAssertTableAceptaAcademicContent(): void
    {
        $tabla = \ContentDisplayOrder::assertTable('academic_content');

        $this->assertSame('academic_content', $tabla);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-002  assertTable()  —  acepta center_content
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-002 assertTable acepta center_content como tabla valida
     */
    public function testAssertTableAceptaCenterContent(): void
    {
        $tabla = \ContentDisplayOrder::assertTable('center_content');

        $this->assertSame('center_content', $tabla);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-003  assertTable()  —  acepta general_content
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-003 assertTable acepta general_content como tabla valida
     */
    public function testAssertTableAceptaGeneralContent(): void
    {
        $tabla = \ContentDisplayOrder::assertTable('general_content');

        $this->assertSame('general_content', $tabla);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-004  assertTable()  —  acepta gallery_content
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-004 assertTable acepta gallery_content como tabla valida
     */
    public function testAssertTableAceptaGalleryContent(): void
    {
        $tabla = \ContentDisplayOrder::assertTable('gallery_content');

        $this->assertSame('gallery_content', $tabla);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-005  assertTable()  —  rechaza tabla no autorizada
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-005 assertTable lanza InvalidArgumentException con tablas fuera de la lista blanca
     */
    public function testAssertTableRechazaTablaInvalida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tabla no permitida para display_order: users');

        \ContentDisplayOrder::assertTable('users');
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-006  assertTable()  —  previene inyeccion SQL
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-006 assertTable previene inyeccion de sentencias SQL en nombre de tabla
     */
    public function testAssertTablePrevieneInyeccionSql(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        \ContentDisplayOrder::assertTable('academic_content; DROP TABLE users;');
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CDO-007  Constante MAX
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CDO-007 ContentDisplayOrder define un tope maximo de 999999 para orden
     */
    public function testConstanteMaxDefinida(): void
    {
        $this->assertSame(999999, \ContentDisplayOrder::MAX);
    }
}
