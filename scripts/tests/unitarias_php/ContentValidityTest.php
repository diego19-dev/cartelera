<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase ContentValidity
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-contentvalidity.md
 *
 * Convenio de IDs: UT-PHP-CV-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3, para que la salida --testdox de PHPUnit
 * muestre la línea "✔ UT-PHP-CV-NNN Título".
 *
 * Dependencias externas: NINGUNA. El bootstrap carga solo ContentValidity.php;
 * no se arranca sesión, BD ni headers HTTP. ContentValidity usa únicamente
 * DateTimeImmutable y time() de PHP nativo.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use InvalidArgumentException;

class ContentValidityTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-001  ContentValidity::addDuration()  —  suma días
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-001 Suma días a una fecha base y devuelve la fecha correcta
     */
    public function testSumaDiasAFechaBase(): void
    {
        $base      = new DateTimeImmutable('2026-08-01 12:00:00');
        $resultado = \ContentValidity::addDuration(3, 'days', $base);

        $this->assertSame('2026-08-04', $resultado->format('Y-m-d'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-002  ContentValidity::addDuration()  —  suma horas
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-002 Suma horas a una fecha base y devuelve la hora correcta
     */
    public function testSumaHorasAFechaBase(): void
    {
        $base      = new DateTimeImmutable('2026-08-01 10:00:00');
        $resultado = \ContentValidity::addDuration(2, 'hours', $base);

        $this->assertSame('2026-08-01 12:00:00', $resultado->format('Y-m-d H:i:s'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-003  ContentValidity::addDuration()  —  unidad inválida
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-003 Lanza excepción con unidad de vigencia inválida
     */
    public function testLanzaExcepcionConUnidadInvalida(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unidad de vigencia inválida');

        \ContentValidity::addDuration(1, 'weeks');
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-004  ContentValidity::isExpired()  —  fecha pasada
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-004 isExpired devuelve true si la fecha ya pasó
     */
    public function testIsExpiredDevuelveTrueConFechaPasada(): void
    {
        // Fecha 1 hora en el pasado
        $ayer = date('c', time() - 3600);

        $this->assertTrue(\ContentValidity::isExpired($ayer));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-005  ContentValidity::isExpired()  —  null y vacío
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-005 isExpired devuelve false si la fecha es null o cadena vacía
     */
    public function testIsExpiredDevuelveFalseConNullOVacio(): void
    {
        $this->assertFalse(\ContentValidity::isExpired(null));
        $this->assertFalse(\ContentValidity::isExpired(''));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-006  ContentValidity::parseDateTime()  —  ISO 8601 válido
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-006 parseDateTime parsea ISO 8601 válido y retorna string
     */
    public function testParseDateTimeRetornaStringConIsoValido(): void
    {
        $resultado = \ContentValidity::parseDateTime('2026-12-01T15:00:00+00:00');

        $this->assertNotNull($resultado);
        $this->assertNotFalse(strtotime($resultado));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-007  ContentValidity::parseDateTime()  —  null, vacío, espacios
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-007 parseDateTime retorna null con cadena vacía, espacios o null
     */
    public function testParseDateTimeRetornaNullConEntradaVacia(): void
    {
        $this->assertNull(\ContentValidity::parseDateTime(null));
        $this->assertNull(\ContentValidity::parseDateTime(''));
        $this->assertNull(\ContentValidity::parseDateTime('   '));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-008  ContentValidity::assertShowUntil()  —  supera 6 meses
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-008 assertShowUntil lanza excepción si la vigencia supera 6 meses
     */
    public function testAssertShowUntilRechazaMasDeSeisMeses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('6 meses');

        $siete_meses = (new DateTimeImmutable('now'))->modify('+7 months')->format('c');
        \ContentValidity::assertShowUntil($siete_meses);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-CV-009  ContentValidity::assertShowUntil()  —  menos de 1 hora
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-CV-009 assertShowUntil lanza excepción si la vigencia es menos de 1 hora
     */
    public function testAssertShowUntilRechazaMenosDeUnaHora(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('1 hora');

        // 30 minutos en el futuro — por debajo del mínimo de 1 hora
        $treinta_minutos = date('c', time() + 1800);
        \ContentValidity::assertShowUntil($treinta_minutos);
    }
}
