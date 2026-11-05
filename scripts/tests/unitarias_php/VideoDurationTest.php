<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase VideoDuration
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-videoduration.md
 *
 * Convenio de IDs: UT-PHP-VD-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3, para que la salida --testdox de PHPUnit
 * muestre la línea "✔ UT-PHP-VD-NNN Título".
 *
 * Dependencias externas: NINGUNA. El bootstrap define las constantes
 * MAX_VIDEO_DURATION_SECONDS = 600, MAX_SLIDE_DURATION_SECONDS = 600 y
 * MIN_SLIDE_DURATION_SECONDS = 5, y carga VideoDuration.php directamente.
 * No se arranca sesión, BD ni headers HTTP.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class VideoDurationTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-001  toSlideSeconds()  —  ceil y clamp mínimo
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-001 toSlideSeconds redondea hacia arriba con ceil y aplica el mínimo de 5 s
     */
    public function testToSlideSecondsCeilYMinimoDecinco(): void
    {
        // 14.2 s → ceil = 15 → dentro del rango [5, 600]
        $this->assertSame(15, \VideoDuration::toSlideSeconds(14.2));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-002  toSlideSeconds()  —  exactamente 600 s (límite válido)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-002 toSlideSeconds acepta exactamente 600 segundos (límite superior válido)
     */
    public function testToSlideSecondsAceptaExactamente600(): void
    {
        // 600 s = 10 min exacto; no debe lanzar excepción ni recortar
        $this->assertSame(600, \VideoDuration::toSlideSeconds(600.0));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-003  toSlideSeconds()  —  supera 10 minutos (inválido)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-003 toSlideSeconds rechaza video que supera 10 minutos
     */
    public function testToSlideSecondsRechazaMasDeDiezMinutos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('10 minutos');

        // 601 s está por encima de MAX_VIDEO_DURATION_SECONDS + 0.5
        \VideoDuration::toSlideSeconds(601.0);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-004  toSlideSeconds()  —  valor menor que el mínimo de slide
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-004 toSlideSeconds aplica el mínimo de 5 s cuando el valor es inferior
     */
    public function testToSlideSecondsAplicaMinimoQuando(): void
    {
        // 3.1 s → ceil = 4 → inferior a MIN_SLIDE_DURATION_SECONDS(5) → retorna 5
        $this->assertSame(5, \VideoDuration::toSlideSeconds(3.1));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-005  toSlideSeconds()  —  ceil supera MAX_SLIDE_DURATION_SECONDS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-005 toSlideSeconds aplica el máximo de 600 s cuando ceil supera el tope de slide
     */
    public function testToSlideSecondsAplicaMaximoCuandoCeilSupera(): void
    {
        // 599.9 s → ceil = 600 → igual al máximo, retorna 600
        $this->assertSame(600, \VideoDuration::toSlideSeconds(599.9));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-006  clampSlideSeconds()  —  valor dentro del rango
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-006 clampSlideSeconds devuelve el mismo valor si está dentro del rango
     */
    public function testClampSlideSecondsDevuelveMismoValorEnRango(): void
    {
        // 42 s está entre 5 y 600 → sin modificación
        $this->assertSame(42, \VideoDuration::clampSlideSeconds(42));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-007  clampSlideSeconds()  —  valor inferior al mínimo
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-007 clampSlideSeconds devuelve 5 si el valor es inferior al mínimo
     */
    public function testClampSlideSecondsDevuelveMinimoSiMenor(): void
    {
        // 1 s < MIN_SLIDE_DURATION_SECONDS(5) → retorna 5
        $this->assertSame(5, \VideoDuration::clampSlideSeconds(1));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-008  clampSlideSeconds()  —  valor superior al máximo
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-008 clampSlideSeconds devuelve 600 si el valor supera el máximo
     */
    public function testClampSlideSecondsDevuelveMaximoSiMayor(): void
    {
        // 999 s > MAX_SLIDE_DURATION_SECONDS(600) → retorna 600
        $this->assertSame(600, \VideoDuration::clampSlideSeconds(999));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VD-009  clampSlideSeconds()  —  exactamente 5 s (límite inferior exacto)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VD-009 clampSlideSeconds acepta exactamente 5 s como límite inferior válido
     */
    public function testClampSlideSecondsAceptaExactamenteCinco(): void
    {
        // 5 s = MIN_SLIDE_DURATION_SECONDS → no debe recortarse
        $this->assertSame(5, \VideoDuration::clampSlideSeconds(5));
    }
}
