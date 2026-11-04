# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 02: ContentValidity — 9 casos, 9 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Vigencia de contenido — ventana show\_from / show\_until de slides (`ContentValidity.php`) |
| **Versión del código** | Código en repositorio local. `src/ContentValidity.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-04 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64 (instalado vía composer en `scripts/`). Sin acceso a base de datos. |

---

## CASO DE PRUEBA UT-PHP-CV-001

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-001 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | Suma días a una fecha base y devuelve la fecha correcta |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-01 — cálculo correcto de la vigencia relativa de slides en el panel. Un error en esta función publicaría contenido con fecha de expiración incorrecta en la cartelera. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::addDuration(int $amount, string $unit, ?DateTimeImmutable $from = null): DateTimeImmutable` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función opera exclusivamente con `DateTimeImmutable`; no usa BD, sesión ni red. La prueba carga `ContentValidity.php` con `require_once` vía `bootstrap_unitarias.php`. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: unidad `'days'` con cantidad positiva. Rama: `match($unit) { 'days' => $from->modify('+' . $n . ' days') }`. |
| **Objetivo de la prueba** | Confirmar que sumar 3 días a `2026-08-01` produce `2026-08-04`, garantizando el cálculo aritmético correcto de la fecha de expiración cuando el usuario selecciona días en el panel. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado externo. Se instancia `DateTimeImmutable('2026-08-01 12:00:00')` como fecha base determinista. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::addDuration(3, 'days', new DateTimeImmutable('2026-08-01 12:00:00'))` |
| **Datos de prueba (valores exactos de entrada)** | `$amount = 3`, `$unit = 'days'`, `$from = new DateTimeImmutable('2026-08-01 12:00:00')` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna un `DateTimeImmutable` cuyo `format('Y-m-d')` es `'2026-08-04'`. |
| **Resultado obtenido** | Retornó `DateTimeImmutable`. `assertSame('2026-08-04', $resultado->format('Y-m-d'))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testSumaDiasAFechaBase` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-001 Suma días a una fecha base y devuelve la fecha correcta` (ver Figura 1 al final del documento) |
| **Poscondición** | Ninguna; la prueba no escribe datos ni archivos (se ejecutó con `--do-not-cache-result`). |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-002

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-002 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | Suma horas a una fecha base y devuelve la hora correcta |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-01 — cálculo correcto de vigencia con granularidad de horas. Cubre el caso mínimo de publicación (la vigencia mínima del sistema es 1 hora). |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::addDuration(int $amount, string $unit, ?DateTimeImmutable $from = null): DateTimeImmutable` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: unidad `'hours'` con cantidad positiva. Rama: `match($unit) { 'hours' => $from->modify('+' . $n . ' hours') }`. |
| **Objetivo de la prueba** | Verificar que sumar 2 horas a `2026-08-01 10:00:00` produce `2026-08-01 12:00:00`, cubriendo la rama de horas independiente de la de días. |
| **Precondición (preparar)** | Ninguna; se instancia `DateTimeImmutable('2026-08-01 10:00:00')` como fecha base determinista. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::addDuration(2, 'hours', new DateTimeImmutable('2026-08-01 10:00:00'))` |
| **Datos de prueba (valores exactos de entrada)** | `$amount = 2`, `$unit = 'hours'`, `$from = new DateTimeImmutable('2026-08-01 10:00:00')` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna un `DateTimeImmutable` cuyo `format('Y-m-d H:i:s')` es `'2026-08-01 12:00:00'`. |
| **Resultado obtenido** | Retornó `DateTimeImmutable`. `assertSame('2026-08-01 12:00:00', $resultado->format('Y-m-d H:i:s'))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testSumaHorasAFechaBase` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-002 Suma horas a una fecha base y devuelve la hora correcta` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-003

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-003 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | Lanza excepción con unidad de vigencia inválida |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-02 — rechazo explícito de unidades no soportadas en el cálculo de vigencia. Previene que una unidad incorrecta llegue silenciosamente a la base de datos con una fecha errónea. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::addDuration(int $amount, string $unit, ?DateTimeImmutable $from = null): DateTimeImmutable` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia inválida: unidad `'weeks'` no contemplada en el `match`. Rama: `default => throw new InvalidArgumentException('Unidad de vigencia inválida')`. |
| **Objetivo de la prueba** | Confirmar que pasar una unidad no soportada (`'weeks'`) lanza `InvalidArgumentException` con el mensaje exacto `'Unidad de vigencia inválida'`, impidiendo cálculos silenciosamente incorrectos. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado externo. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::addDuration(1, 'weeks')` |
| **Datos de prueba (valores exactos de entrada)** | `$amount = 1`, `$unit = 'weeks'` (unidad no soportada) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Lanza `InvalidArgumentException` con el mensaje `'Unidad de vigencia inválida'`. |
| **Resultado obtenido** | Lanzó `InvalidArgumentException` con el mensaje esperado. `expectException` y `expectExceptionMessage` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testLanzaExcepcionConUnidadInvalida` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-003 Lanza excepción con unidad de vigencia inválida` (ver Figura 1) |
| **Poscondición** | Ninguna; la excepción es capturada por PHPUnit y no propaga efectos. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-004

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-004 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | isExpired devuelve true si la fecha ya pasó |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-03 — detección correcta de contenido expirado para ocultarlo en la cartelera TV. Un fallo aquí mantendría slides vencidos visibles en pantalla. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::isExpired(?string $until): bool` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función usa `strtotime()` y `time()` de PHP nativo. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: fecha en el pasado. Rama: `$ts !== false && $ts < time()` es verdadera. |
| **Objetivo de la prueba** | Verificar que una fecha ISO 8601 situada 1 hora en el pasado es correctamente identificada como expirada. |
| **Precondición (preparar)** | Ninguna; la fecha de prueba se genera dinámicamente como `date('c', time() - 3600)` para garantizar que siempre esté en el pasado. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::isExpired(date('c', time() - 3600))` |
| **Datos de prueba (valores exactos de entrada)** | Fecha ISO 8601 correspondiente a `now - 3600 segundos` (1 hora antes de la ejecución) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `true`. |
| **Resultado obtenido** | Retornó `true`. `assertTrue` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testIsExpiredDevuelveTrueConFechaPasada` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-004 isExpired devuelve true si la fecha ya pasó` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-005

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-005 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | isExpired devuelve false si la fecha es null o cadena vacía |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-03 — los slides sin fecha de expiración (`show_until = null`) deben permanecer visibles indefinidamente; un `true` erróneo los ocultaría. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::isExpired(?string $until): bool` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite: entradas vacías. Rama: `if ($until === null \|\| trim($until) === '') { return false; }`. |
| **Objetivo de la prueba** | Confirmar que `null` y la cadena vacía `''` retornan `false`, preservando la visibilidad de slides sin límite de tiempo asignado. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::isExpired(null)` y `ContentValidity::isExpired('')` |
| **Datos de prueba (valores exactos de entrada)** | `null` y `''` (cadena vacía) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Ambas llamadas retornan `false`. |
| **Resultado obtenido** | `assertFalse(ContentValidity::isExpired(null))` aprobado. `assertFalse(ContentValidity::isExpired(''))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testIsExpiredDevuelveFalseConNullOVacio` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-005 isExpired devuelve false si la fecha es null o cadena vacía` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-006

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-006 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | parseDateTime parsea ISO 8601 válido y retorna string |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-04 — normalización de fechas recibidas del formulario o de la API antes de persistirlas en la BD. Un fallo podría guardar fechas no válidas o en formato incorrecto. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::parseDateTime(?string $dt): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función usa `strtotime()` y `date()` de PHP nativo. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: cadena ISO 8601 con zona horaria explícita. Rama: `$ts !== false → return date('c', $ts)`. |
| **Objetivo de la prueba** | Verificar que una fecha ISO 8601 válida (`'2026-12-01T15:00:00+00:00'`) se parsea correctamente y el resultado es un string interpretable por `strtotime()`. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::parseDateTime('2026-12-01T15:00:00+00:00')` |
| **Datos de prueba (valores exactos de entrada)** | `'2026-12-01T15:00:00+00:00'` (ISO 8601 con offset UTC) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna un string no nulo, y `strtotime($resultado)` no es `false`. |
| **Resultado obtenido** | Retornó string no nulo. `assertNotNull` aprobado. `assertNotFalse(strtotime($resultado))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testParseDateTimeRetornaStringConIsoValido` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-006 parseDateTime parsea ISO 8601 válido y retorna string` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-007

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-007 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | parseDateTime retorna null con cadena vacía, espacios o null |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-04 — un campo de fecha vacío debe traducirse a `null` en la BD (sin límite de tiempo), no a una fecha incorrecta. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::parseDateTime(?string $dt): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite: tres variantes de entrada vacía. Rama: `if ($dt === null \|\| trim($dt) === '') { return null; }`. |
| **Objetivo de la prueba** | Confirmar que `null`, `''` y `'   '` (solo espacios) retornan `null`, evitando que un campo de fecha vacío se interprete como una fecha válida. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::parseDateTime(null)`, `ContentValidity::parseDateTime('')`, `ContentValidity::parseDateTime('   ')` |
| **Datos de prueba (valores exactos de entrada)** | `null`, `''` (cadena vacía), `'   '` (tres espacios) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Las tres llamadas retornan `null`. |
| **Resultado obtenido** | `assertNull(parseDateTime(null))` aprobado. `assertNull(parseDateTime(''))` aprobado. `assertNull(parseDateTime('   '))` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testParseDateTimeRetornaNullConEntradaVacia` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-007 parseDateTime retorna null con cadena vacía, espacios o null` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-CV-008

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-008 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | assertShowUntil lanza excepción si la vigencia supera 6 meses |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-05 — la política del sistema limita la vigencia máxima de un slide a 6 meses. Publicar contenido con fecha más lejana podría saturar la cartelera con información desactualizada. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::assertShowUntil(?string $iso, ?string $previousIso = null): void` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite superior: 7 meses (justo por encima del máximo). Rama: `if ($ts > $max) throw new InvalidArgumentException('La vigencia máxima es 6 meses')`. |
| **Objetivo de la prueba** | Confirmar que una fecha de vigencia a 7 meses en el futuro es rechazada con el mensaje `'La vigencia máxima es 6 meses'`, impidiendo publicaciones con fecha exageradamente lejana. |
| **Precondición (preparar)** | Ninguna; la fecha se genera como `(new DateTimeImmutable('now'))->modify('+7 months')->format('c')` para que siempre esté 7 meses en el futuro respecto al momento de ejecución. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::assertShowUntil((new DateTimeImmutable('now'))->modify('+7 months')->format('c'))` |
| **Datos de prueba (valores exactos de entrada)** | Fecha ISO 8601 correspondiente a `now + 7 meses` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Lanza `InvalidArgumentException` con el mensaje `'La vigencia máxima es 6 meses'`. |
| **Resultado obtenido** | Lanzó `InvalidArgumentException` con el mensaje esperado. `expectException` y `expectExceptionMessage` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testAssertShowUntilRechazaMasDeSeisMeses` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-008 assertShowUntil lanza excepción si la vigencia supera 6 meses` (ver Figura 1) |
| **Poscondición** | Ninguna; la excepción es capturada por PHPUnit sin efectos colaterales. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Caso hermano sugerido: exactamente 6 meses (límite válido) — valida la rama `$ts <= $max`. Registrado como candidato para la siguiente jornada. |

---

## CASO DE PRUEBA UT-PHP-CV-009

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-CV-009 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | assertShowUntil lanza excepción si la vigencia es menos de 1 hora |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-CV-05 — la política del sistema exige una vigencia mínima de 1 hora para evitar slides que expiran antes de que el operario termine de configurarlos o que apenas se muestren en pantalla. |
| **Unidad bajo prueba — Archivo** | src/ContentValidity.php |
| **Unidad bajo prueba — Función/Método (firma)** | `ContentValidity::assertShowUntil(?string $iso, ?string $previousIso = null): void` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite inferior: 30 minutos (justo por debajo del mínimo de 1 hora = 3600 s). Rama: `if ($ts < $now + self::MIN_SECONDS) throw new InvalidArgumentException('La vigencia mínima es 1 hora')`. |
| **Objetivo de la prueba** | Confirmar que una fecha de vigencia a solo 30 minutos en el futuro es rechazada con el mensaje `'La vigencia mínima es 1 hora'`, previniendo publicaciones con expiración casi inmediata. |
| **Precondición (preparar)** | Ninguna; la fecha se genera como `date('c', time() + 1800)` (30 minutos en el futuro) para garantizar que esté por debajo del mínimo de 3600 s. |
| **Pasos (ejecutar — llamada exacta)** | `ContentValidity::assertShowUntil(date('c', time() + 1800))` |
| **Datos de prueba (valores exactos de entrada)** | Fecha ISO 8601 correspondiente a `now + 1800 segundos` (30 minutos en el futuro) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Lanza `InvalidArgumentException` con el mensaje `'La vigencia mínima es 1 hora'`. |
| **Resultado obtenido** | Lanzó `InvalidArgumentException` con el mensaje esperado. `expectException` y `expectExceptionMessage` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/ContentValidityTest.php :: testAssertShowUntilRechazaMenosDeUnaHora` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-CV-009 assertShowUntil lanza excepción si la vigencia es menos de 1 hora` (ver Figura 1) |
| **Poscondición** | Ninguna; la excepción es capturada por PHPUnit sin efectos colaterales. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Caso hermano sugerido: exactamente 1 hora (límite válido) con `previousIso = null` — valida la rama del mínimo exacto. Registrado como candidato para la siguiente jornada. |

---

## RESUMEN DE LA EJECUCIÓN

| | |
|:---|:---|
| **Casos diseñados / ejecutados** | 9 / 9 |
| **Aprobados / Fallidos / Bloqueados** | 9 / 0 / 0 |
| **Aserciones** | 16 |
| **Tiempo de ejecución** | 0.013 s (suite UnitariasPhp completa: Security + ContentValidity) |
| **Memoria** | 8.00 MB |
| **Defectos abiertos** | Ninguno |
| **Hallazgos para revisar** | (1) No existe validación del valor mínimo de `$amount` para unidades `'days'` y `'months'`; `addDuration(0, 'days')` retorna la misma fecha base sin error. La función aplica `max(0, $amount)` silenciosamente. Se sugiere evaluar si un amount de 0 debería lanzar excepción. (2) `assertShowUntil` con exactamente 6 meses (límite válido) y con exactamente 1 hora (límite mínimo válido) no están cubiertos aquí; se sugieren como UT-PHP-CV-010 y UT-PHP-CV-011 en la siguiente jornada. No son defectos frente al código actual. |
| **Impacto en producción** | Ninguno. Solo se creó `ContentValidityTest.php` en `scripts/tests/unitarias_php/` y se actualizó `bootstrap_unitarias.php`; no se modificó código de `src/` ni la base de datos. |

---

## EVIDENCIA: SALIDA COMPLETA DE LA EJECUCIÓN

*Figura 1. Salida de PHPUnit en entorno local el 2026-11-04. PHP 8.5.8, PHPUnit 10.5.64.*

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.8
Configuration: /home/diego/Documentos/cartelera/scripts/phpunit.xml

.................                                                 17 / 17 (100%)

Time: 00:00.013, Memory: 8.00 MB

Content Validity (Tests\UnitariasPhp\ContentValidity)
 ✔ UT-PHP-CV-001 Suma días a una fecha base y devuelve la fecha correcta
 ✔ UT-PHP-CV-002 Suma horas a una fecha base y devuelve la hora correcta
 ✔ UT-PHP-CV-003 Lanza excepción con unidad de vigencia inválida
 ✔ UT-PHP-CV-004 isExpired devuelve true si la fecha ya pasó
 ✔ UT-PHP-CV-005 isExpired devuelve false si la fecha es null o cadena vacía
 ✔ UT-PHP-CV-006 parseDateTime parsea ISO 8601 válido y retorna string
 ✔ UT-PHP-CV-007 parseDateTime retorna null con cadena vacía, espacios o null
 ✔ UT-PHP-CV-008 assertShowUntil lanza excepción si la vigencia supera 6 meses
 ✔ UT-PHP-CV-009 assertShowUntil lanza excepción si la vigencia es menos de 1 hora

Security (Tests\UnitariasPhp\Security)
 ✔ UT-PHP-SEC-001 Convierte etiqueta script a entidades HTML
 ✔ UT-PHP-SEC-002 null devuelve cadena vacía
 ✔ UT-PHP-SEC-003 sanitizeArray omite la clave _csrf del array
 ✔ UT-PHP-SEC-004 sanitizeArray conserva caracteres especiales en password
 ✔ UT-PHP-SEC-005 isStrongPassword rechaza contraseña sin mayúscula
 ✔ UT-PHP-SEC-006 isStrongPassword acepta contraseña que cumple todos los requisitos
 ✔ UT-PHP-SEC-007 sanitizeFilename reemplaza caracteres peligrosos con guion bajo
 ✔ UT-PHP-SEC-008 verifyCsrf devuelve false si el token proporcionado está vacío

OK (17 tests, 32 assertions)
```

> Nota: los dos avisos `[CSET] ADVERTENCIA: DB_PASS no configurada` y `SECRET_KEY no configurada`
> aparecen en stderr (no en la salida de PHPUnit) porque el bootstrap global intenta leer `.env`,
> que no existe en el entorno de pruebas local. No afectan la ejecución de ningún caso de esta suite.
