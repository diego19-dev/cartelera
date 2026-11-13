<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase GalleryContent
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-gallerycontent.md
 *
 * Convenio de IDs: UT-PHP-GAL-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class GalleryContentTest extends TestCase
{
    private \ReflectionMethod $normalizeMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizeMethod = new \ReflectionMethod(\GalleryContent::class, 'normalizeData');
    }

    private function normalize(array $data): array
    {
        return $this->normalizeMethod->invoke(null, $data);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-001  normalizeData()  —  casting booleano de is_active
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-001 normalizeData convierte is_active a booleano estricto
     */
    public function testNormalizeDataCastingBooleano(): void
    {
        $this->assertTrue($this->normalize(['is_active' => true])['is_active']);
        $this->assertTrue($this->normalize(['is_active' => '1'])['is_active']);
        $this->assertTrue($this->normalize(['is_active' => 1])['is_active']);

        $this->assertFalse($this->normalize(['is_active' => false])['is_active']);
        $this->assertFalse($this->normalize(['is_active' => '0'])['is_active']);
        $this->assertFalse($this->normalize(['is_active' => 'false'])['is_active']);
        $this->assertFalse($this->normalize([])['is_active']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-002  normalizeData()  —  casting entero de campos numéricos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-002 normalizeData realiza casting a entero en duration_seconds y created_by
     */
    public function testNormalizeDataCastingEntero(): void
    {
        $entrada = [
            'duration_seconds' => '25',
            'created_by'       => '3',
            'display_order'    => '10',
        ];

        $resultado = $this->normalize($entrada);

        $this->assertSame(25, $resultado['duration_seconds']);
        $this->assertSame(3, $resultado['created_by']);
        $this->assertSame(10, $resultado['display_order']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-003  normalizeData()  —  título vacío a null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-003 normalizeData convierte titulos con solo espacios en blanco a null
     */
    public function testNormalizeDataTituloVacioANull(): void
    {
        $this->assertNull($this->normalize(['title' => '   '])['title']);
        $this->assertNull($this->normalize(['title' => ''])['title']);
        $this->assertSame('Foto Oficial', $this->normalize(['title' => 'Foto Oficial'])['title']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-004  normalizeData()  —  fechas vacías a null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-004 normalizeData convierte fechas show_from y show_until vacias a null
     */
    public function testNormalizeDataFechasVaciasANull(): void
    {
        $entrada = [
            'show_from'  => '   ',
            'show_until' => '',
        ];

        $resultado = $this->normalize($entrada);

        $this->assertNull($resultado['show_from']);
        $this->assertNull($resultado['show_until']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-005  normalizeData()  —  conserva fechas válidas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-005 normalizeData preserva valores de fecha no vacios
     */
    public function testNormalizeDataConservaFechasValidas(): void
    {
        $entrada = [
            'show_from'  => '2026-11-13 08:00:00',
            'show_until' => '2026-11-20 18:00:00',
        ];

        $resultado = $this->normalize($entrada);

        $this->assertSame('2026-11-13 08:00:00', $resultado['show_from']);
        $this->assertSame('2026-11-20 18:00:00', $resultado['show_until']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-GAL-006  Firma estática de métodos de GalleryContent
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-GAL-006 GalleryContent expone metodos estaticos publicos para ciclo de vida
     */
    public function testMetodosEstaticosGalleryContent(): void
    {
        $ref = new \ReflectionClass(\GalleryContent::class);

        $this->assertTrue($ref->getMethod('all')->isStatic());
        $this->assertTrue($ref->getMethod('find')->isStatic());
        $this->assertTrue($ref->getMethod('create')->isStatic());
        $this->assertTrue($ref->getMethod('update')->isStatic());
        $this->assertTrue($ref->getMethod('delete')->isStatic());
        $this->assertTrue($ref->getMethod('toggleActive')->isStatic());
        $this->assertTrue($ref->getMethod('getActiveForTv')->isStatic());
    }
}
