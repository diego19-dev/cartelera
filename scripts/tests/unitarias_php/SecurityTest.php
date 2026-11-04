<?php
/**
 * CSET-TV — Pruebas Unitarias PHP: clase Security
 * Suite: UnitariasPhp  |  Resultado: resultado-unitarias-php-security.md
 *
 * Convenio de IDs: UT-PHP-SEC-NNN
 * Cada método lleva @testdox con el ID y título exacto que aparece en el
 * documento ISO/IEC/IEEE 29119-3, para que la salida --testdox de PHPUnit
 * muestre la línea "✔ UT-PHP-SEC-NNN Título".
 *
 * Dependencias externas: NINGUNA. El bootstrap carga solo EnvLoader y
 * Security; no se arranca sesión, BD ni headers HTTP.
 */

namespace Tests\UnitariasPhp;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-001  Security::sanitize()  —  XSS con <script>
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-001 Convierte etiqueta script a entidades HTML
     */
    public function testConvierteScriptAEntidadHtml(): void
    {
        $resultado = \Security::sanitize('<script>alert("xss")</script>');

        // La etiqueta < debe convertirse a &lt;
        $this->assertStringNotContainsString('<script>', $resultado);
        $this->assertStringContainsString('&lt;script&gt;', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-002  Security::sanitize()  —  null devuelve ''
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-002 null devuelve cadena vacía
     */
    public function testNullDevuelveCadenaVacia(): void
    {
        $resultado = \Security::sanitize(null);

        $this->assertSame('', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-003  Security::sanitizeArray()  —  omite clave _csrf
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-003 sanitizeArray omite la clave _csrf del array
     */
    public function testSanitizeArrayOmiteClaveCsrf(): void
    {
        $entrada = [
            'titulo'  => 'Hola',
            '_csrf'   => 'token_secreto_abc',
            'cuerpo'  => 'Texto normal',
        ];

        $resultado = \Security::sanitizeArray($entrada);

        $this->assertArrayNotHasKey('_csrf', $resultado);
        $this->assertArrayHasKey('titulo', $resultado);
        $this->assertArrayHasKey('cuerpo', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-004  Security::sanitizeArray()  —  password sin htmlspecialchars
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-004 sanitizeArray conserva caracteres especiales en password
     */
    public function testSanitizeArrayConservaPasswordSinEscapar(): void
    {
        // Contraseña con caracteres que htmlspecialchars convertiría
        $claveOriginal = 'P@ss<w>ord&"2026"';

        $entrada  = ['username' => 'admin', 'password' => $claveOriginal];
        $resultado = \Security::sanitizeArray($entrada);

        // El campo password debe llegar intacto (sin &amp;, &lt;, etc.)
        $this->assertSame($claveOriginal, $resultado['password']);
        // El campo username sí se sanitiza
        $this->assertSame('admin', $resultado['username']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-005  Security::isStrongPassword()  —  rechaza sin mayúscula
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-005 isStrongPassword rechaza contraseña sin mayúscula
     */
    public function testIsStrongPasswordRechazaSinMayuscula(): void
    {
        // Tiene minúscula, dígito y especial, pero ninguna mayúscula
        $resultado = \Security::isStrongPassword('minuscula1@abc');

        $this->assertFalse($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-006  Security::isStrongPassword()  —  acepta contraseña fuerte
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-006 isStrongPassword acepta contraseña que cumple todos los requisitos
     */
    public function testIsStrongPasswordAceptaContrasenaFuerte(): void
    {
        // ≥10 chars, minúscula + mayúscula + dígito + especial
        $resultado = \Security::isStrongPassword('Segura#2026!');

        $this->assertTrue($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-007  Security::sanitizeFilename()  —  caracteres peligrosos
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-007 sanitizeFilename reemplaza caracteres peligrosos con guion bajo
     */
    public function testSanitizeFilenameReemplazaCaracteresPeligrosos(): void
    {
        $resultado = \Security::sanitizeFilename('../../etc/passwd;rm -rf');

        // No deben quedar barras, puntos al inicio/fin, espacios ni ;
        $this->assertStringNotContainsString('/', $resultado);
        $this->assertStringNotContainsString(';', $resultado);
        $this->assertStringNotContainsString(' ', $resultado);
        // El resultado solo debe contener caracteres seguros
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-\.]+$/', $resultado);
    }

    // ─────────────────────────────────────────────────────────────────────
    // UT-PHP-SEC-008  Security::verifyCsrf()  —  token vacío devuelve false
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @testdox UT-PHP-SEC-008 verifyCsrf devuelve false si el token proporcionado está vacío
     */
    public function testVerifyCsrfDevuelveFalseConTokenVacio(): void
    {
        // verifyCsrf() llama Session::has() — simulamos $_SESSION directamente;
        // PHP permite asignar $_SESSION sin sesión activa en CLI/tests.
        $_SESSION['_csrf_token'] = 'token_valido_de_sesion';

        $this->assertFalse(\Security::verifyCsrf(''));
        $this->assertFalse(\Security::verifyCsrf(null));

        // Limpieza: dejar $_SESSION sin rastros del test
        unset($_SESSION['_csrf_token']);
    }
}
