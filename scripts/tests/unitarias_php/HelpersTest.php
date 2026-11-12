<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: funciones globales en helpers.php
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-helpers.md
 *
 * Convenio de IDs: UT-PHP-HLP-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA. Ejecución pura en memoria.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-001  e()  —  escapado HTML seguro
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-001 e escapa caracteres especiales HTML previniendo XSS
     */
    public function testEEscapaCaracteresHtml(): void
    {
        $entrada = '<script>alert("xss")</script> & \'comillas\'';
        $resultado = \e($entrada);

        $this->assertStringNotContainsString('<script>', $resultado);
        $this->assertStringContainsString('&lt;script&gt;', $resultado);
        $this->assertStringContainsString('&amp;', $resultado);
        $this->assertStringContainsString('&quot;', $resultado);
        $this->assertStringContainsString('&#039;', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-002  e()  —  manejo de valor nulo
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-002 e devuelve cadena vacia cuando el valor es null
     */
    public function testEDevuelveCadenaVaciaConNull(): void
    {
        $this->assertSame('', \e(null));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-003  role_label()  —  etiquetas institucionales en español
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-003 role_label mapea roles del sistema a nombres legibles en espanol
     */
    public function testRoleLabelMapeaRolesCorrectamente(): void
    {
        $this->assertSame('Administrador del Sistema', \role_label('admin'));
        $this->assertSame('Jefe de programa', \role_label('program_head'));
        $this->assertSame('Editor', \role_label('editor'));
        $this->assertSame('Solo lectura', \role_label('viewer'));
        $this->assertSame('Usuario', \role_label(null));
        $this->assertSame('Usuario', \role_label(''));
        $this->assertSame('rol_personalizado', \role_label('rol_personalizado'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-004  cset_url()  —  construccion de rutas bajo APP_BASE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-004 cset_url antepone el prefijo institucional APP_BASE de forma consistente
     */
    public function testCsetUrlConstruyeRutasCorrectamente(): void
    {
        // APP_BASE está configurado como '/tv'
        $this->assertSame('/tv/', \cset_url(''));
        $this->assertSame('/tv/admin/users', \cset_url('admin/users'));
        $this->assertSame('/tv/admin/users', \cset_url('/admin/users'));
        $this->assertSame('/tv/login.php', \cset_url('login.php'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-005  cset_validate_user_fields()  —  admin exitoso sin area
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-005 cset_validate_user_fields asigna program_area null a usuarios con rol admin
     */
    public function testValidateUserFieldsAdminLimpiaArea(): void
    {
        // Incluso si se envía un área accidentalmente, un admin nunca debe tener program_area
        $resultado = \cset_validate_user_fields('admin', 'tecnologia');

        $this->assertSame('admin', $resultado['role']);
        $this->assertNull($resultado['program_area']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-006  cset_validate_user_fields()  —  program_head con area
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-006 cset_validate_user_fields acepta rol program_head cuando tiene area valida
     */
    public function testValidateUserFieldsProgramHeadConArea(): void
    {
        $resultado = \cset_validate_user_fields('program_head', '  salud  ');

        $this->assertSame('program_head', $resultado['role']);
        $this->assertSame('salud', $resultado['program_area']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-007  cset_validate_user_fields()  —  excepcion sin area
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-007 cset_validate_user_fields lanza RuntimeException si program_head no tiene area
     */
    public function testValidateUserFieldsLanzaExcepcionSiFaltaArea(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Los jefes de programa deben tener un área asignada.');

        \cset_validate_user_fields('program_head', null);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-008  cset_validate_user_fields()  —  rol no permitido
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-008 cset_validate_user_fields rechaza roles fuera de PANEL_ASSIGNABLE_ROLES
     */
    public function testValidateUserFieldsRechazaRolNoPermitido(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rol no permitido.');

        \cset_validate_user_fields('superadmin_invalido', null);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-009  cset_panel_module_enabled()  —  respeto a lista de deshabilitados
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-009 cset_panel_module_enabled detecta modulos activos y deshabilitados
     */
    public function testPanelModuleEnabledDetectaModulos(): void
    {
        // PANEL_DISABLED_MODULES contiene ['center', 'gallery']
        $this->assertFalse(\cset_panel_module_enabled('center'));
        $this->assertFalse(\cset_panel_module_enabled('gallery'));

        $this->assertTrue(\cset_panel_module_enabled('academic'));
        $this->assertTrue(\cset_panel_module_enabled('general'));
        $this->assertTrue(\cset_panel_module_enabled('users'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-HLP-010  format_activity_when()  —  conversion a hora Colombia
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-HLP-010 format_activity_when formatea timestamp ISO a zona horaria America/Bogota
     */
    public function testFormatActivityWhenConvierteZonaHoraria(): void
    {
        // 15:00 UTC corresponde a 10:00 en Colombia (UTC-5)
        $isoUtc = '2026-11-12T15:00:00Z';
        $resultado = \format_activity_when($isoUtc);

        $this->assertSame('12/11/2026 10:00', $resultado);

        // Nulo o vacío devuelve cadena vacía
        $this->assertSame('', \format_activity_when(null));
        $this->assertSame('', \format_activity_when(''));
    }
}
