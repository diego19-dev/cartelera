<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase EnvLoader
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-envloader.md
 *
 * Convenio de IDs: UT-PHP-ENV-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class EnvLoaderTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-001  get()  —  recupera valor definido en $_ENV
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-001 get recupera valor existente definido en entorno
     */
    public function testGetRecuperaValorExistente(): void
    {
        $_ENV['TEST_UNIT_KEY_1'] = 'valor_prueba_sena';

        $resultado = \EnvLoader::get('TEST_UNIT_KEY_1');

        $this->assertSame('valor_prueba_sena', $resultado);

        unset($_ENV['TEST_UNIT_KEY_1']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-002  get()  —  fallback por defecto cuando no existe
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-002 get retorna valor por defecto si la variable no existe
     */
    public function testGetRetornaDefaultCuandoNoExiste(): void
    {
        unset($_ENV['VARIABLE_INEXISTENTE_XYZ']);

        $resultado = \EnvLoader::get('VARIABLE_INEXISTENTE_XYZ', 'valor_default_123');

        $this->assertSame('valor_default_123', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-003  get()  —  fallback cuando la variable esta vacia
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-003 get retorna valor por defecto si la variable tiene cadena vacia
     */
    public function testGetRetornaDefaultCuandoEstaVacia(): void
    {
        $_ENV['TEST_UNIT_EMPTY'] = '';

        $resultado = \EnvLoader::get('TEST_UNIT_EMPTY', 'default_para_vacio');

        $this->assertSame('default_para_vacio', $resultado);

        unset($_ENV['TEST_UNIT_EMPTY']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-004  load()  —  no falla con archivo inexistente
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-004 load maneja de forma segura ruta de archivo inexistente
     */
    public function testLoadNoLanzaErrorConArchivoInexistente(): void
    {
        $rutaFalsa = '/tmp/ruta_archivo_env_inexistente_' . uniqid() . '.env';

        // Debe ejecutarse sin lanzar excepciones ni advertencias
        \EnvLoader::load($rutaFalsa);

        $this->assertFileDoesNotExist($rutaFalsa);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-005  Firma y métodos de EnvLoader
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-005 EnvLoader expone metodos estaticos load y get
     */
    public function testMetodosEstaticosDisponibles(): void
    {
        $reflectionLoad = new \ReflectionMethod(\EnvLoader::class, 'load');
        $reflectionGet  = new \ReflectionMethod(\EnvLoader::class, 'get');

        $this->assertTrue($reflectionLoad->isStatic());
        $this->assertTrue($reflectionLoad->isPublic());
        $this->assertTrue($reflectionGet->isStatic());
        $this->assertTrue($reflectionGet->isPublic());
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-006  get()  —  retorna null si falta y no hay default
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-006 get retorna null cuando no existe y no se especifica default
     */
    public function testGetRetornaNullSinDefault(): void
    {
        unset($_ENV['CLAVE_SIN_DEFAULT_ABC']);

        $resultado = \EnvLoader::get('CLAVE_SIN_DEFAULT_ABC');

        $this->assertNull($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-007  Integridad de constantes core
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-007 Base path y constantes esenciales estan definidas tras carga
     */
    public function testConstantesCoreDefinidas(): void
    {
        $this->assertTrue(defined('BASE_PATH'));
        $this->assertTrue(defined('APP_ENV'));
        $this->assertContains(APP_ENV, ['testing', 'development', 'production']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-008  get()  —  manejo de tipo de retorno string o null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-008 get mantiene tipado nullable string
     */
    public function testGetRetornoTipado(): void
    {
        $_ENV['TEST_NUMBER_STR'] = '5432';

        $resultado = \EnvLoader::get('TEST_NUMBER_STR');

        $this->assertIsString($resultado);
        $this->assertSame('5432', $resultado);

        unset($_ENV['TEST_NUMBER_STR']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-ENV-009  Estado de la propiedad privada loaded
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-ENV-009 EnvLoader mantiene estado de carga en propiedad privada estatica
     */
    public function testPropiedadLoadedEsPrivada(): void
    {
        $reflectionProperty = new \ReflectionProperty(\EnvLoader::class, 'loaded');

        $this->assertTrue($reflectionProperty->isStatic());
        $this->assertTrue($reflectionProperty->isPrivate());
    }
}
