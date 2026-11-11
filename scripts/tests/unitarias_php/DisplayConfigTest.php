<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase DisplayConfig
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-displayconfig.md
 *
 * Convenio de IDs: UT-PHP-DC-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA. No se conecta a base de datos.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class DisplayConfigTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-001  sanitizeWelcomeHtml()  —  conserva <b> y </b>
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-001 sanitizeWelcomeHtml conserva etiquetas permitidas b y cierre b
     */
    public function testSanitizeWelcomeHtmlConservaEtiquetaNegrita(): void
    {
        $entrada = 'Texto con <b>negrita</b> permitida';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertSame('Texto con <b>negrita</b> permitida', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-002  sanitizeWelcomeHtml()  —  escapa scripts
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-002 sanitizeWelcomeHtml escapa scripts y atributos maliciosos
     */
    public function testSanitizeWelcomeHtmlEscapaScripts(): void
    {
        $entrada = 'Bienvenido <script>alert("xss")</script>';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertStringNotContainsString('<script>', $resultado);
        $this->assertStringContainsString('&lt;script&gt;', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-003  sanitizeWelcomeHtml()  —  escapa otras etiquetas html
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-003 sanitizeWelcomeHtml convierte otras etiquetas como cursiva o encabezados
     */
    public function testSanitizeWelcomeHtmlEscapaOtrasEtiquetas(): void
    {
        $entrada = 'Hola <i>mundo</i> <u>subrayado</u>';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertStringNotContainsString('<i>', $resultado);
        $this->assertStringContainsString('&lt;i&gt;mundo&lt;/i&gt;', $resultado);
        $this->assertStringContainsString('&lt;u&gt;subrayado&lt;/u&gt;', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-004  sanitizeWelcomeHtml()  —  cierre automático de negrita
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-004 sanitizeWelcomeHtml cierra automaticamente etiqueta b al final
     */
    public function testSanitizeWelcomeHtmlCierraEtiquetaAbierta(): void
    {
        $entrada = 'Texto que abre <b>negrita sin cerrar';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertStringEndsWith('</b>', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-005  sanitizeWelcomeHtml()  —  ignora cierre huerfano
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-005 sanitizeWelcomeHtml ignora etiquetas de cierre b sin apertura previa
     */
    public function testSanitizeWelcomeHtmlIgnoraCierreHuerfano(): void
    {
        $entrada = 'Texto sin abrir</b> fin';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertSame('Texto sin abrir fin', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-006  sanitizeWelcomeHtml()  —  caracteres especiales
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-006 sanitizeWelcomeHtml escapa caracteres especiales como comillas y ampersand
     */
    public function testSanitizeWelcomeHtmlEscapaCaracteresEspeciales(): void
    {
        $entrada = 'SENA & CSET "Regional Santander"';
        $resultado = \DisplayConfig::sanitizeWelcomeHtml($entrada);

        $this->assertStringContainsString('&amp;', $resultado);
        $this->assertStringContainsString('&quot;', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-007  welcomeDefaults()  —  estructura completa
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-007 welcomeDefaults retorna las 21 claves predeterminadas del SENA CSET
     */
    public function testWelcomeDefaultsRetornaEstructuraCompleta(): void
    {
        $defaults = \DisplayConfig::welcomeDefaults();

        $this->assertIsArray($defaults);
        $this->assertCount(21, $defaults);
        $this->assertArrayHasKey('saludo', $defaults);
        $this->assertArrayHasKey('titulo', $defaults);
        $this->assertArrayHasKey('centro', $defaults);
        $this->assertArrayHasKey('regional', $defaults);
        $this->assertArrayHasKey('duration', $defaults);
        $this->assertArrayHasKey('bg_color', $defaults);
        $this->assertArrayHasKey('accent_color', $defaults);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-008  welcomeDefaults()  —  tipos y duracion
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-008 welcomeDefaults tiene duracion entera positiva y colores validos
     */
    public function testWelcomeDefaultsTieneValoresValidos(): void
    {
        $defaults = \DisplayConfig::welcomeDefaults();

        $this->assertIsInt($defaults['duration']);
        $this->assertGreaterThanOrEqual(3, $defaults['duration']);
        $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $defaults['bg_color']);
        $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $defaults['accent_color']);
        $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $defaults['text_color']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-DC-009  welcomeEditableKeys()  —  lista blanca de campos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-DC-009 welcomeEditableKeys contiene exactamente las 19 claves permitidas
     */
    public function testWelcomeEditableKeysContieneListaBlanca(): void
    {
        $keys = \DisplayConfig::welcomeEditableKeys();

        $this->assertIsArray($keys);
        $this->assertCount(19, $keys);
        $this->assertContains('welcome_saludo', $keys);
        $this->assertContains('welcome_duration', $keys);
        $this->assertContains('welcome_bg_color', $keys);
        $this->assertNotContains('welcome_bg_path', $keys);
        $this->assertNotContains('welcome_bg_url', $keys);
    }
}
