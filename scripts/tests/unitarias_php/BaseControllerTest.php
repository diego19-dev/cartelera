<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase BaseController
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-basecontroller.md
 *
 * Convenio de IDs: UT-PHP-BC-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class TestableBaseController extends \BaseController
{
    public function callMediaUrl(?string $path, string $category): ?string
    {
        return $this->mediaUrl($path, $category);
    }

    public function callFilterByArea(array $items, string $areaField): array
    {
        return $this->filterByArea($items, $areaField);
    }

    public function callRequirePanelModule(string $module): void
    {
        $this->requirePanelModule($module);
    }
}

class BaseControllerTest extends TestCase
{
    private TestableBaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableBaseController();
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_program_area']);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_program_area']);
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-001  mediaUrl()  —  retorna null si path está vacío
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-001 mediaUrl retorna null cuando la ruta es null o cadena vacia
     */
    public function testMediaUrlRetornaNullConPathVacio(): void
    {
        $this->assertNull($this->controller->callMediaUrl(null, 'academic'));
        $this->assertNull($this->controller->callMediaUrl('', 'academic'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-002  mediaUrl()  —  conserva URLs absolutas HTTP y HTTPS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-002 mediaUrl conserva rutas externas absolutas http y https
     */
    public function testMediaUrlConservaUrlsExternas(): void
    {
        $urlHttps = 'https://sena.edu.co/assets/logo.png';
        $urlHttp  = 'http://repositorio.sena.edu.co/foto.jpg';

        $this->assertSame($urlHttps, $this->controller->callMediaUrl($urlHttps, 'academic'));
        $this->assertSame($urlHttp, $this->controller->callMediaUrl($urlHttp, 'general'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-003  mediaUrl()  —  construye ruta relativa con APP_BASE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-003 mediaUrl construye rutas relativas bajo APP_BASE y la categoria especificada
     */
    public function testMediaUrlConstruyeRutaRelativa(): void
    {
        $resultado = $this->controller->callMediaUrl('archivo_evento.jpg', 'general');

        $this->assertSame('/tv/img/uploads/general/archivo_evento.jpg', $resultado);

        // Sin doble barra si el path trae barra inicial
        $resultadoConBarra = $this->controller->callMediaUrl('/imagen.png', 'center');
        $this->assertSame('/tv/img/uploads/center/imagen.png', $resultadoConBarra);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-004  filterByArea()  —  admin ve todos los items
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-004 filterByArea retorna todos los registros sin filtrar para rol admin
     */
    public function testFilterByAreaAdminVeTodos(): void
    {
        $_SESSION['user_id']   = 1;
        $_SESSION['user_role'] = 'admin';

        $items = [
            ['id' => 1, 'program_area' => 'salud'],
            ['id' => 2, 'program_area' => 'tecnologia'],
            ['id' => 3, 'program_area' => null],
        ];

        $resultado = $this->controller->callFilterByArea($items, 'program_area');

        $this->assertCount(3, $resultado);
        $this->assertSame($items, $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-005  filterByArea()  —  program_head solo ve su área o generales
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-005 filterByArea filtra por area asignada para el rol program_head
     */
    public function testFilterByAreaProgramHeadFiltraArea(): void
    {
        $_SESSION['user_id']           = 2;
        $_SESSION['user_role']         = 'program_head';
        $_SESSION['user_program_area'] = 'tecnologia';

        $items = [
            ['id' => 1, 'program_area' => 'salud'],
            ['id' => 2, 'program_area' => 'tecnologia'],
            ['id' => 3, 'program_area' => null],
            ['id' => 4, 'program_area' => 'turismo'],
        ];

        $resultado = $this->controller->callFilterByArea($items, 'program_area');

        // Debe ver el de tecnología y el null/general, pero no salud ni turismo
        $this->assertCount(2, $resultado);
        $this->assertSame(2, $resultado[0]['id']);
        $this->assertSame(3, $resultado[1]['id']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-006  filterByArea()  —  editor ve todos los items
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-006 filterByArea retorna todos los registros para rol editor
     */
    public function testFilterByAreaEditorVeTodos(): void
    {
        $_SESSION['user_id']   = 3;
        $_SESSION['user_role'] = 'editor';

        $items = [
            ['id' => 1, 'sector' => 'industrial'],
            ['id' => 2, 'sector' => 'comercio'],
        ];

        $resultado = $this->controller->callFilterByArea($items, 'sector');

        $this->assertCount(2, $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-BC-007  Estructura y métodos protegidos de BaseController
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-BC-007 BaseController expone metodos protegidos esperados por la API
     */
    public function testMetodosProtegidosDisponibles(): void
    {
        $ref = new \ReflectionClass(\BaseController::class);

        $this->assertTrue($ref->hasMethod('ok'));
        $this->assertTrue($ref->hasMethod('fail'));
        $this->assertTrue($ref->hasMethod('requireAuth'));
        $this->assertTrue($ref->hasMethod('requireAdmin'));
        $this->assertTrue($ref->hasMethod('requirePanelModule'));
        $this->assertTrue($ref->hasMethod('filterByArea'));
        $this->assertTrue($ref->hasMethod('mediaUrl'));
    }
}
