<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: función cset_icon en icons.php
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-icons.md
 *
 * Convenio de IDs: UT-PHP-ICO-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class IconsTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-001  cset_icon()  —  renderizado de icono válido
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-001 cset_icon genera un elemento svg valido con atributos correctos
     */
    public function testCsetIconGeneraSvgValido(): void
    {
        $svg = \cset_icon('home');

        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringEndsWith('</svg>', $svg);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $svg);
        $this->assertStringContainsString('stroke="currentColor"', $svg);
        $this->assertStringContainsString('aria-hidden="true"', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-002  cset_icon()  —  dimensiones personalizadas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-002 cset_icon aplica correctamente el tamano especificado en width y height
     */
    public function testCsetIconAplicaTamanoPersonalizado(): void
    {
        $svg = \cset_icon('users', 28);

        $this->assertStringContainsString('width="28"', $svg);
        $this->assertStringContainsString('height="28"', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-003  cset_icon()  —  clases CSS adicionales
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-003 cset_icon concatena clases CSS preservando shrink-0 y sanitizando
     */
    public function testCsetIconAplicaClasesCss(): void
    {
        $svg = \cset_icon('settings', 18, 'text-green-500 mr-2');

        $this->assertStringContainsString('class="shrink-0 text-green-500 mr-2"', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-004  cset_icon()  —  fallback de icono desconocido a info
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-004 cset_icon utiliza el path de info como fallback ante nombres inexistentes
     */
    public function testCsetIconFallbackAInfo(): void
    {
        $svgInexistente = \cset_icon('icono_que_no_existe_xyz');
        $svgInfo        = \cset_icon('info');

        // Deben contener el mismo path interno de info
        $this->assertStringContainsString('<circle cx="12" cy="12" r="10"/>', $svgInexistente);
        $this->assertStringContainsString('<circle cx="12" cy="12" r="10"/>', $svgInfo);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-005  cset_icon()  —  soporte para iconos principales
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-005 cset_icon resuelve correctamente iconos institucionales del CSET
     */
    public function testCsetIconResuelveIconosPrincipales(): void
    {
        $iconos = ['tv', 'building-2', 'newspaper', 'images', 'calendar', 'dollar-sign'];

        foreach ($iconos as $nombre) {
            $svg = \cset_icon($nombre);
            $this->assertNotEmpty($svg);
            $this->assertStringContainsString('</svg>', $svg);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ICO-006  cset_icon()  —  tamano por defecto
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ICO-006 cset_icon utiliza 18 pixeles como dimension por defecto
     */
    public function testCsetIconTamanoPorDefecto(): void
    {
        $svg = \cset_icon('home');

        $this->assertStringContainsString('width="18"', $svg);
        $this->assertStringContainsString('height="18"', $svg);
    }
}
