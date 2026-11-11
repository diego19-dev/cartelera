<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase LoginAttempt
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-loginattempt.md
 *
 * Convenio de IDs: UT-PHP-LA-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA. No se conecta a base de datos ni sesión HTTP.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class LoginAttemptTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-001  shouldSkipRateLimit()  —  IP externa no se omite
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-001 shouldSkipRateLimit retorna false para IP externa estándar
     */
    public function testShouldSkipRateLimitRetornaFalseParaIpExterna(): void
    {
        $resultado = \LoginAttempt::shouldSkipRateLimit('192.168.1.50');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-002  shouldSkipRateLimit()  —  Loopback IPv4 en testing
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-002 shouldSkipRateLimit no omite loopback IPv4 en entorno testing
     */
    public function testShouldSkipRateLimitNoOmiteLoopbackEnTesting(): void
    {
        // En entorno testing (APP_ENV !== 'development'), 127.0.0.1 no tiene bypass por defecto
        $resultado = \LoginAttempt::shouldSkipRateLimit('127.0.0.1');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-003  shouldSkipRateLimit()  —  Loopback IPv6 en testing
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-003 shouldSkipRateLimit no omite loopback IPv6 en entorno testing
     */
    public function testShouldSkipRateLimitNoOmiteLoopbackIpv6EnTesting(): void
    {
        $resultado = \LoginAttempt::shouldSkipRateLimit('::1');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-004  shouldSkipRateLimit()  —  IP pública cualquiera
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-004 shouldSkipRateLimit retorna false para direcciones IP públicas
     */
    public function testShouldSkipRateLimitRetornaFalseParaIpPublica(): void
    {
        $resultado = \LoginAttempt::shouldSkipRateLimit('200.89.45.12');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-005  shouldSkipRateLimit()  —  Loopback alternativo noDevelopment
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-005 shouldSkipRateLimit no omite loopback expandido IPv6 en testing
     */
    public function testShouldSkipRateLimitNoOmiteLoopbackExpandido(): void
    {
        $resultado = \LoginAttempt::shouldSkipRateLimit('0:0:0:0:0:0:0:1');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-006  Firma estática de isBlocked()
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-006 isBlocked tiene firma tipada estática callable con tres parámetros
     */
    public function testIsBlockedTieneFirmaTipadaValida(): void
    {
        $reflection = new \ReflectionMethod(\LoginAttempt::class, 'isBlocked');

        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(3, $reflection->getNumberOfParameters());

        $params = $reflection->getParameters();
        $this->assertSame('ip', $params[0]->getName());
        $this->assertSame('maxAttempts', $params[1]->getName());
        $this->assertSame('windowMinutes', $params[2]->getName());
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-007  Firma estática de record()
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-007 record acepta parámetros opcionales nulos para usuario y agente
     */
    public function testRecordTieneFirmaEstaticaValida(): void
    {
        $reflection = new \ReflectionMethod(\LoginAttempt::class, 'record');

        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
        $this->assertSame(5, $reflection->getNumberOfParameters());

        $params = $reflection->getParameters();
        $this->assertTrue($params[1]->allowsNull(), 'username debe permitir null');
        $this->assertTrue($params[3]->allowsNull(), 'failureReason debe permitir null');
        $this->assertTrue($params[4]->allowsNull(), 'userAgent debe permitir null');
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-LA-008  Firma y valor por defecto de clearFailuresForIp()
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-LA-008 clearFailuresForIp tiene valor por defecto de 15 minutos de ventana
     */
    public function testClearFailuresTieneVentanaPorDefectoQuince(): void
    {
        $reflection = new \ReflectionMethod(\LoginAttempt::class, 'clearFailuresForIp');

        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());

        $params = $reflection->getParameters();
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertSame(15, $params[1]->getDefaultValue());
    }
}
