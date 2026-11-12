<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase VideoTranscoder
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-videotranscoder.md
 *
 * Convenio de IDs: UT-PHP-VT-NNN
 * Cada método lleva @testdox con el ID y título exacto según ISO/IEC/IEEE 29119-3.
 *
 * Dependencias externas: NINGUNA.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class VideoTranscoderTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VT-001  isAvailable()  —  detección booleana
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VT-001 isAvailable devuelve un valor booleano segun presencia de ffmpeg
     */
    public function testIsAvailableDevuelveBooleano(): void
    {
        $disponible = \VideoTranscoder::isAvailable();

        $this->assertIsBool($disponible);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VT-002  ffmpegBin()  —  retorno de ruta o null
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VT-002 ffmpegBin retorna una cadena no vacia o null
     */
    public function testFfmpegBinRetornaRutaONull(): void
    {
        $bin = \VideoTranscoder::ffmpegBin();

        if ($bin !== null) {
            $this->assertIsString($bin);
            $this->assertNotEmpty($bin);
            $this->assertTrue(is_executable($bin) || file_exists($bin));
        } else {
            $this->assertNull($bin);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VT-003  toSignageMp4()  —  falla si origen no es legible
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VT-003 toSignageMp4 lanza RuntimeException si el archivo fuente no existe o no es legible
     */
    public function testToSignageMp4FallaConOrigenInexistente(): void
    {
        $origenFalso  = '/tmp/video_inexistente_' . uniqid() . '.mp4';
        $destinoFalso = '/tmp/salida_' . uniqid() . '.mp4';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se pudo leer el video para optimizarlo');

        \VideoTranscoder::toSignageMp4($origenFalso, $destinoFalso);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VT-004  Firma estática de métodos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VT-004 VideoTranscoder expone metodos estaticos publicos
     */
    public function testMetodosEstaticosPublicos(): void
    {
        $refTranscode = new \ReflectionMethod(\VideoTranscoder::class, 'toSignageMp4');
        $refBin       = new \ReflectionMethod(\VideoTranscoder::class, 'ffmpegBin');
        $refAvail     = new \ReflectionMethod(\VideoTranscoder::class, 'isAvailable');

        $this->assertTrue($refTranscode->isStatic());
        $this->assertTrue($refTranscode->isPublic());

        $this->assertTrue($refBin->isStatic());
        $this->assertTrue($refBin->isPublic());

        $this->assertTrue($refAvail->isStatic());
        $this->assertTrue($refAvail->isPublic());
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-VT-005  toSignageMp4()  —  parámetros tipados
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-VT-005 toSignageMp4 recibe sourcePath y destPath como cadenas obligatorias
     */
    public function testParametrosTipadosToSignageMp4(): void
    {
        $ref = new \ReflectionMethod(\VideoTranscoder::class, 'toSignageMp4');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('sourcePath', $params[0]->getName());
        $this->assertSame('destPath', $params[1]->getName());
        $this->assertFalse($params[0]->allowsNull());
        $this->assertFalse($params[1]->allowsNull());
    }
}
