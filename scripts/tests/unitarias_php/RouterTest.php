<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase Router
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-router.md
 *
 * Convenio de IDs: UT-PHP-RTR-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private string $originalMethod;
    private string $originalUri;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->originalUri    = $_SERVER['REQUEST_URI'] ?? '/';
    }

    protected function tearDown(): void
    {
        $_SERVER['REQUEST_METHOD'] = $this->originalMethod;
        $_SERVER['REQUEST_URI']    = $this->originalUri;
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-001  Enrutamiento exacto con handler
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-001 Router ejecuta el handler correcto para una ruta estatica
     */
    public function testRouterEjecutaRutaEstatica(): void
    {
        $router = new \Router('/tv');
        $ejecutado = false;

        $router->add('GET', '/saludo', function () use (&$ejecutado) {
            $ejecutado = true;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/tv/saludo';

        $router->dispatch();

        $this->assertTrue($ejecutado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-002  Extracción de parámetros de ruta {param}
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-002 Router extrae parametros dinamicos delimitados por llaves
     */
    public function testRouterExtraeParametrosDinamicos(): void
    {
        $router = new \Router('/tv');
        $paramRecibido = null;

        $router->add('GET', '/academic/{id}', function (array $params) use (&$paramRecibido) {
            $paramRecibido = $params['id'] ?? null;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/tv/academic/105';

        $router->dispatch();

        $this->assertSame('105', $paramRecibido);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-003  Diferenciación de método HTTP
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-003 Router respeta el metodo HTTP solicitado evitando ejecuciones cruzadas
     */
    public function testRouterDiferenciaMetodoHttp(): void
    {
        $router = new \Router('/tv');
        $postEjecutado = false;
        $getEjecutado  = false;

        $router->add('POST', '/api/guardar', function () use (&$postEjecutado) {
            $postEjecutado = true;
        });
        $router->add('GET', '/api/guardar', function () use (&$getEjecutado) {
            $getEjecutado = true;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/tv/api/guardar';

        $router->dispatch();

        $this->assertTrue($getEjecutado);
        $this->assertFalse($postEjecutado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-004  Ejecución de middlewares
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-004 Router ejecuta funciones middleware antes del handler principal
     */
    public function testRouterEjecutaMiddlewares(): void
    {
        $router = new \Router();
        $orden = [];

        $mw1 = function () use (&$orden) { $orden[] = 'mw1'; };
        $mw2 = function () use (&$orden) { $orden[] = 'mw2'; };

        $router->add('GET', '/proceso', function () use (&$orden) {
            $orden[] = 'handler';
        }, [$mw1, $mw2]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/proceso';

        $router->dispatch();

        $this->assertSame(['mw1', 'mw2', 'handler'], $orden);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-005  Respuesta 404 HTML para rutas inexistentes
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-005 Router emite encabezado 404 ante rutas no coincidentes
     */
    public function testRouterEmite404ConRutaInexistente(): void
    {
        $router = new \Router('/tv');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/tv/ruta_que_no_existe_404';

        ob_start();
        $router->dispatch();
        $salida = ob_get_clean();

        $this->assertStringContainsString('404 - Página no encontrada', $salida);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-RTR-006  Base path sin barra final
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-RTR-006 Router normaliza base path eliminando barras finales
     */
    public function testRouterNormalizaBasePath(): void
    {
        $router = new \Router('/tv///');
        $ejecutado = false;

        $router->add('GET', '/test', function () use (&$ejecutado) {
            $ejecutado = true;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/tv/test';

        $router->dispatch();

        $this->assertTrue($ejecutado);
    }
}
