# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 01: Security — 8 casos, 8 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Seguridad — sanitización, validación de contraseña y CSRF (`Security.php`) |
| **Versión del código** | Código en repositorio local. `src/Security.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-03 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64 (instalado vía composer en `scripts/`). Sin acceso a base de datos. |

---

## CASO DE PRUEBA UT-PHP-SEC-001

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-001 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | Convierte etiqueta script a entidades HTML |
| **Requisito / Riesgo relacionado (trazabilidad)** | Mitigación de XSS reflejado en el panel de administración. MSPI eje 7 (control de acceso e integridad de datos). |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::sanitize(?string $text): string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función aplica `htmlspecialchars` puro; no usa BD, sesión ni red. La prueba carga `Security.php` directamente con `require_once` via `bootstrap_unitarias.php`. |
| **Técnica de diseño** | Clase de equivalencia válida: cadena con etiqueta HTML activa. Rama: `htmlspecialchars(trim($text), ENT_QUOTES\|ENT_HTML5, 'UTF-8')`. |
| **Objetivo de la prueba** | Confirmar que una cadena con `<script>` sale con `&lt;script&gt;`, imposibilitando la ejecución del script en el navegador. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado. Se carga `Security.php` con `require_once` vía bootstrap aislado. |
| **Pasos (ejecutar — llamada exacta)** | `Security::sanitize('<script>alert("xss")</script>')` |
| **Datos de prueba (valores exactos de entrada)** | `'<script>alert("xss")</script>'` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | La cadena retornada no contiene `<script>` y sí contiene `&lt;script&gt;`. |
| **Resultado obtenido** | `assertStringNotContainsString('<script>', ...)` aprobado. `assertStringContainsString('&lt;script&gt;', ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testConvierteScriptAEntidadHtml` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-001 Convierte etiqueta script a entidades HTML` (ver Figura 1 al final del documento) |
| **Poscondición** | Ninguna; la prueba no escribe datos ni archivos (se ejecutó con `--do-not-cache-result`). |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-SEC-002

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-002 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | null devuelve cadena vacía |
| **Requisito / Riesgo relacionado (trazabilidad)** | Robustez ante campos opcionales nulos en formularios del panel. Previene errores de tipo en operaciones de concatenación posteriores. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::sanitize(?string $text): string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Valor límite / clase de equivalencia inválida: entrada `null`. Rama: `if ($text === null) return '';`. |
| **Objetivo de la prueba** | Verificar que `null` no produce advertencia de tipo ni excepción, y que el retorno es la cadena vacía `''`. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado. |
| **Pasos (ejecutar — llamada exacta)** | `Security::sanitize(null)` |
| **Datos de prueba (valores exactos de entrada)** | `null` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `''` (string vacío). `assertSame('', ...)` aprobado. |
| **Resultado obtenido** | Retornó `''`. `assertSame` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testNullDevuelveCadenaVacia` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-002 null devuelve cadena vacía` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-SEC-003

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-003 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | sanitizeArray omite la clave `_csrf` del array |
| **Requisito / Riesgo relacionado (trazabilidad)** | Protección CSRF: el token no debe persistirse en la capa de modelo ni en la BD. MSPI eje 7. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::sanitizeArray(array $data): array` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Tabla de decisión: clave `_csrf` → omitir (`continue`); otras claves → incluir y sanitizar. |
| **Objetivo de la prueba** | Confirmar que `sanitizeArray` filtra el token CSRF y no lo incluye en el array de salida, mientras conserva el resto de claves. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `Security::sanitizeArray(['titulo' => 'Hola', '_csrf' => 'token_secreto_abc', 'cuerpo' => 'Texto normal'])` |
| **Datos de prueba (valores exactos de entrada)** | `['titulo' => 'Hola', '_csrf' => 'token_secreto_abc', 'cuerpo' => 'Texto normal']` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | El array retornado no contiene la clave `_csrf`, y sí contiene `titulo` y `cuerpo`. |
| **Resultado obtenido** | `assertArrayNotHasKey('_csrf', ...)` aprobado. `assertArrayHasKey('titulo', ...)` y `assertArrayHasKey('cuerpo', ...)` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testSanitizeArrayOmiteClaveCsrf` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-003 sanitizeArray omite la clave _csrf del array` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-SEC-004

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-004 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | sanitizeArray conserva caracteres especiales en password |
| **Requisito / Riesgo relacionado (trazabilidad)** | Corrección del hallazgo A4 (auditoría de seguridad CSET): aplicar `htmlspecialchars` a contraseñas rompía el login de cuentas con `&`, `<`, `>`, `"`, `'` en la clave. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::sanitizeArray(array $data): array` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Rama: clave especial `password` → pasar el valor como `(string) $value` sin `htmlspecialchars`. |
| **Objetivo de la prueba** | Verificar que contraseñas con `&`, `<`, `>`, `"` llegan intactas al hash bcrypt, preservando la autenticación correcta. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `Security::sanitizeArray(['username' => 'admin', 'password' => 'P@ss<w>ord&"2026"'])` |
| **Datos de prueba (valores exactos de entrada)** | `['username' => 'admin', 'password' => 'P@ss<w>ord&"2026"']` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | `$resultado['password']` es idéntico a `'P@ss<w>ord&"2026"'` (sin entidades HTML). `$resultado['username']` es `'admin'`. |
| **Resultado obtenido** | `assertSame('P@ss<w>ord&"2026"', $resultado['password'])` aprobado. `assertSame('admin', $resultado['username'])` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testSanitizeArrayConservaPasswordSinEscapar` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-004 sanitizeArray conserva caracteres especiales en password` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Este caso regresiona el hallazgo A4 de la auditoría. Si en el futuro `sanitizeArray` vuelve a aplicar `htmlspecialchars` a `password`, este test fallará y alertará del regreso del defecto. |

---

## CASO DE PRUEBA UT-PHP-SEC-005

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-005 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | isStrongPassword rechaza contraseña sin mayúscula |
| **Requisito / Riesgo relacionado (trazabilidad)** | Política de contraseñas fuertes en creación/cambio de clave de usuarios del panel. MSPI eje 7. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::isStrongPassword(string $password): bool` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Clase de equivalencia inválida: cadena que cumple longitud, minúscula, dígito y especial, pero le falta mayúscula. Rama: `$hasUpper` es falso. |
| **Objetivo de la prueba** | Confirmar que una contraseña sin ninguna letra mayúscula es rechazada, forzando al usuario a incluirla. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `Security::isStrongPassword('minuscula1@abc')` |
| **Datos de prueba (valores exactos de entrada)** | `'minuscula1@abc'` (14 chars, tiene minúscula, dígito y especial, **sin mayúscula**) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `false`. |
| **Resultado obtenido** | Retornó `false`. `assertFalse` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testIsStrongPasswordRechazaSinMayuscula` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-005 isStrongPassword rechaza contraseña sin mayúscula` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Casos hermanos sugeridos para ampliación: sin dígito, sin especial, longitud < 10 (cada uno en su propio caso). |

---

## CASO DE PRUEBA UT-PHP-SEC-006

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-006 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | isStrongPassword acepta contraseña que cumple todos los requisitos |
| **Requisito / Riesgo relacionado (trazabilidad)** | Política de contraseñas fuertes en creación/cambio de clave de usuarios del panel. MSPI eje 7. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::isStrongPassword(string $password): bool` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Clase de equivalencia válida: cadena con ≥10 caracteres, minúscula, mayúscula, dígito y especial. Todas las ramas booleanas `$hasLower && $hasUpper && $hasDigit && $hasSpecial` son verdaderas. |
| **Objetivo de la prueba** | Verificar que una contraseña que cumple la política completa es aceptada por el sistema. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `Security::isStrongPassword('Segura#2026!')` |
| **Datos de prueba (valores exactos de entrada)** | `'Segura#2026!'` (12 chars, minúscula + mayúscula + dígito + especial) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `true`. |
| **Resultado obtenido** | Retornó `true`. `assertTrue` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testIsStrongPasswordAceptaContrasenaFuerte` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-006 isStrongPassword acepta contraseña que cumple todos los requisitos` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-SEC-007

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-007 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | sanitizeFilename reemplaza caracteres peligrosos con guion bajo |
| **Requisito / Riesgo relacionado (trazabilidad)** | Prevención de path traversal y ejecución de comandos en nombres de archivos subidos al panel (`img/uploads/`). MSPI eje 7. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::sanitizeFilename(string $filename): string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño** | Clase de equivalencia válida (entrada peligrosa → salida segura): nombre con barras, punto-punto, punto-coma y espacios. Rama: `preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename)`. |
| **Objetivo de la prueba** | Confirmar que un nombre de archivo con secuencias de path traversal (`../../`) y caracteres de shell (`;`, ` `) queda reducido a caracteres seguros `[a-zA-Z0-9_\-\.]`. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `Security::sanitizeFilename('../../etc/passwd;rm -rf')` |
| **Datos de prueba (valores exactos de entrada)** | `'../../etc/passwd;rm -rf'` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | La cadena retornada no contiene `/`, `;` ni espacios, y coincide con `/^[a-zA-Z0-9_\-\.]+$/`. |
| **Resultado obtenido** | `assertStringNotContainsString('/', ...)`, `assertStringNotContainsString(';', ...)`, `assertStringNotContainsString(' ', ...)` y `assertMatchesRegularExpression(...)` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testSanitizeFilenameReemplazaCaracteresPeligrosos` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-007 sanitizeFilename reemplaza caracteres peligrosos con guion bajo` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Hallazgo informativo: `sanitizeFilename` no elimina la extensión del archivo; si se subiera `shell.php`, el nombre quedaría `shell.php` (válido según la regex). El control de extensiones lo hace `MediaFile` en otra capa. Se sugiere agregar un caso que valide esa interacción en pruebas de integración. |

---

## CASO DE PRUEBA UT-PHP-SEC-008

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-SEC-008 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | verifyCsrf devuelve false si el token proporcionado está vacío |
| **Requisito / Riesgo relacionado (trazabilidad)** | Protección CSRF en todas las operaciones de escritura del panel (POST a la API). MSPI eje 7. |
| **Unidad bajo prueba — Archivo** | src/Security.php |
| **Unidad bajo prueba — Función/Método (firma)** | `Security::verifyCsrf(?string $token): bool` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. `Session::has()` y `Session::get()` se satisfacen con el stub de `Session` en `bootstrap_unitarias.php` que opera sobre `$_SESSION` directamente, sin iniciar sesión real. |
| **Técnica de diseño** | Valor límite: token vacío `''` y token `null`. Rama: `if (empty($token) ...) return false`. |
| **Objetivo de la prueba** | Confirmar que una petición sin token CSRF (campo vacío o ausente) es rechazada inmediatamente, antes de comparar con el token de sesión. |
| **Precondición (preparar)** | `$_SESSION['_csrf_token'] = 'token_valido_de_sesion'` asignado directamente en el test para simular una sesión activa con token. |
| **Pasos (ejecutar — llamada exacta)** | `Security::verifyCsrf('')` y `Security::verifyCsrf(null)` |
| **Datos de prueba (valores exactos de entrada)** | `''` (cadena vacía) y `null` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Ambas llamadas retornan `false`. |
| **Resultado obtenido** | `assertFalse(Security::verifyCsrf(''))` aprobado. `assertFalse(Security::verifyCsrf(null))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/SecurityTest.php :: testVerifyCsrfDevuelveFalseConTokenVacio` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-SEC-008 verifyCsrf devuelve false si el token proporcionado está vacío` (ver Figura 1) |
| **Poscondición** | `$_SESSION['_csrf_token']` eliminado con `unset` al final del test. Sin efectos colaterales. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla)** | No aplica |
| **Notas / comentarios** | Caso hermano sugerido: token con valor incorrecto (distinto al de sesión) — valida la rama `hash_equals`. Registrado como candidato para la próxima jornada de pruebas. |

---

## RESUMEN DE LA EJECUCIÓN

| | |
|:---|:---|
| **Casos diseñados / ejecutados** | 8 / 8 |
| **Aprobados / Fallidos / Bloqueados** | 8 / 0 / 0 |
| **Aserciones** | 16 |
| **Tiempo de ejecución** | 0.007 s |
| **Memoria** | 8.00 MB |
| **Defectos abiertos** | Ninguno |
| **Hallazgos para revisar** | (1) `sanitizeFilename` no valida la extensión del archivo — el control de extensiones recae en `MediaFile`; conviene cubrirlo en integración (UT-PHP-SEC-007). (2) `verifyCsrf` con token incorrecto (distinto al de sesión) no está cubierto aquí; se sugiere como UT-PHP-SEC-009 en la próxima jornada (UT-PHP-SEC-008). No son defectos frente al código actual. |
| **Impacto en producción** | Ninguno. Solo se creó la carpeta `scripts/tests/unitarias_php/` con la prueba y el bootstrap aislado; no se modificó código de `src/` ni la base de datos. |

---

## EVIDENCIA: SALIDA COMPLETA DE LA EJECUCIÓN

*Figura 1. Salida de PHPUnit en entorno local el 2026-11-03. PHP 8.5.8, PHPUnit 10.5.64.*

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.8
Configuration: /home/diego/Documentos/cartelera/scripts/phpunit.xml

........                                                            8 / 8 (100%)

Time: 00:00.007, Memory: 8.00 MB

Security (Tests\UnitariasPhp\Security)
 ✔ UT-PHP-SEC-001 Convierte etiqueta script a entidades HTML
 ✔ UT-PHP-SEC-002 null devuelve cadena vacía
 ✔ UT-PHP-SEC-003 sanitizeArray omite la clave _csrf del array
 ✔ UT-PHP-SEC-004 sanitizeArray conserva caracteres especiales en password
 ✔ UT-PHP-SEC-005 isStrongPassword rechaza contraseña sin mayúscula
 ✔ UT-PHP-SEC-006 isStrongPassword acepta contraseña que cumple todos los requisitos
 ✔ UT-PHP-SEC-007 sanitizeFilename reemplaza caracteres peligrosos con guion bajo
 ✔ UT-PHP-SEC-008 verifyCsrf devuelve false si el token proporcionado está vacío

OK (8 tests, 16 assertions)
```

> Nota: los dos avisos `[CSET] ADVERTENCIA: DB_PASS no configurada` y `SECRET_KEY no configurada`
> aparecen en stderr (no en la salida de PHPUnit) porque el bootstrap global intenta leer `.env`,
> que no existe en el entorno de pruebas local. No afectan la ejecución de ningún caso de esta suite.
