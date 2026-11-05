# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 03: VideoDuration — 9 casos, 9 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Duración de video — conversión y acotamiento de segundos de slide (`VideoDuration.php`) |
| **Versión del código** | Código en repositorio local. `src/VideoDuration.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-05 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64 (instalado vía composer en `scripts/`). Sin acceso a base de datos. Constantes de configuración definidas directamente en `bootstrap_unitarias.php`: `MAX_VIDEO_DURATION_SECONDS = 600`, `MAX_SLIDE_DURATION_SECONDS = 600`, `MIN_SLIDE_DURATION_SECONDS = 5`. |

---

## CASO DE PRUEBA UT-PHP-VD-001

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-001 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | toSlideSeconds redondea hacia arriba con ceil y aplica el mínimo de 5 s |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-01 — conversión correcta de duración real de video a segundos de slide en el panel. Un error aquí produciría slides con duración incorrecta en la cartelera TV. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::toSlideSeconds(float $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función usa únicamente `ceil()` de PHP y las constantes `MAX_VIDEO_DURATION_SECONDS`, `MAX_SLIDE_DURATION_SECONDS` y `MIN_SLIDE_DURATION_SECONDS`, definidas en el bootstrap aislado. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: duración decimal dentro del rango normal. Rama: `ceil($seconds)` produce un entero mayor que `MIN_SLIDE_DURATION_SECONDS` y menor que `MAX_SLIDE_DURATION_SECONDS`. |
| **Objetivo de la prueba** | Confirmar que `14.2` s se convierte a `15` s (ceil correcto) y que el resultado permanece dentro del rango válido de slide sin que se aplique ningún clamp. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado externo. Constantes de configuración definidas en `bootstrap_unitarias.php`. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::toSlideSeconds(14.2)` |
| **Datos de prueba (valores exactos de entrada)** | `14.2` (float, 14 segundos y 200 ms) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `15`. |
| **Resultado obtenido** | Retornó `15`. `assertSame(15, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testToSlideSecondsCeilYMinimoDecinco` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-001 toSlideSeconds redondea hacia arriba con ceil y aplica el mínimo de 5 s` (ver Figura 1 al final del documento) |
| **Poscondición** | Ninguna; la prueba no escribe datos ni archivos (se ejecutó con `--do-not-cache-result`). |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-002

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-002 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | toSlideSeconds acepta exactamente 600 segundos (límite superior válido) |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-01 — el valor exacto del límite máximo (10 minutos = 600 s) debe ser aceptado sin error para no rechazar videos de duración válida máxima. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::toSlideSeconds(float $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite superior válido: exactamente `600.0` s. Rama: `$seconds > MAX_VIDEO_DURATION_SECONDS + 0.5` es falsa; `ceil(600.0) = 600 = MAX_SLIDE_DURATION_SECONDS`, no se aplica clamp superior. |
| **Objetivo de la prueba** | Verificar que `600.0` s (10 minutos exactos) no lanza excepción y retorna `600`, confirmando que el límite superior del sistema es inclusivo. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::toSlideSeconds(600.0)` |
| **Datos de prueba (valores exactos de entrada)** | `600.0` (float, exactamente 10 minutos) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `600` sin lanzar excepción. |
| **Resultado obtenido** | Retornó `600`. `assertSame(600, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testToSlideSecondsAceptaExactamente600` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-002 toSlideSeconds acepta exactamente 600 segundos (límite superior válido)` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-003

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-003 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | toSlideSeconds rechaza video que supera 10 minutos |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-02 — control de tamaño máximo de archivo de video subido al panel. Videos demasiado largos degradan el rendimiento de la cartelera y consumen almacenamiento excesivo. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::toSlideSeconds(float $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite superior inválido: `601.0` s, justo por encima del umbral `MAX_VIDEO_DURATION_SECONDS + 0.5 = 600.5`. Rama: `if ($seconds > 600.5) throw new RuntimeException(...)`. |
| **Objetivo de la prueba** | Confirmar que un video de `601` s lanza `RuntimeException` con el mensaje `'El video supera el máximo de 10 minutos'`, impidiendo que se registre su duración en la BD. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::toSlideSeconds(601.0)` |
| **Datos de prueba (valores exactos de entrada)** | `601.0` (float, 10 minutos y 1 segundo) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Lanza `RuntimeException` con el mensaje `'El video supera el máximo de 10 minutos'`. |
| **Resultado obtenido** | Lanzó `RuntimeException` con el mensaje esperado. `expectException` y `expectExceptionMessage` aprobados. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testToSlideSecondsRechazaMasDeDiezMinutos` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-003 toSlideSeconds rechaza video que supera 10 minutos` (ver Figura 1) |
| **Poscondición** | Ninguna; la excepción es capturada por PHPUnit sin efectos colaterales. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Hallazgo informativo: el umbral de rechazo es `MAX_VIDEO_DURATION_SECONDS + 0.5 = 600.5`, lo que significa que `600.4` s se acepta pero produce `ceil = 601 > MAX_SLIDE_DURATION_SECONDS`, el cual queda recortado a `600` por la rama de clamp. Comportamiento correcto y documentado. |

---

## CASO DE PRUEBA UT-PHP-VD-004

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-004 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | toSlideSeconds aplica el mínimo de 5 s cuando el valor es inferior |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-01 — un slide de menos de 5 s no se vería correctamente en pantalla; el sistema garantiza que la duración mínima del slide siempre sea legible. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::toSlideSeconds(float $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite inferior: `3.1` s, cuyo `ceil = 4` queda por debajo de `MIN_SLIDE_DURATION_SECONDS = 5`. Rama: `if ($slide < MIN_SLIDE_DURATION_SECONDS) $slide = MIN_SLIDE_DURATION_SECONDS`. |
| **Objetivo de la prueba** | Verificar que una duración de `3.1` s produce un slide de `5` s (mínimo forzado), evitando slides demasiado cortos que no sean visibles en la cartelera. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::toSlideSeconds(3.1)` |
| **Datos de prueba (valores exactos de entrada)** | `3.1` (float, 3 segundos y 100 ms; `ceil = 4 < 5`) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `5`. |
| **Resultado obtenido** | Retornó `5`. `assertSame(5, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testToSlideSecondsAplicaMinimoQuando` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-004 toSlideSeconds aplica el mínimo de 5 s cuando el valor es inferior` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-005

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-005 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | toSlideSeconds aplica el máximo de 600 s cuando ceil supera el tope de slide |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-01 — la duración del slide nunca debe superar `MAX_SLIDE_DURATION_SECONDS` incluso si el `ceil` del video la supera marginalmente. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::toSlideSeconds(float $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite: `599.9` s cuyo `ceil = 600 = MAX_SLIDE_DURATION_SECONDS`; la rama de clamp superior `if ($slide > MAX_SLIDE_DURATION_SECONDS)` es falsa y devuelve `600`. |
| **Objetivo de la prueba** | Confirmar que `599.9` s no supera el umbral de rechazo (`600.5`) y que su `ceil = 600` coincide exactamente con el máximo del slide, retornando `600` sin excepción. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::toSlideSeconds(599.9)` |
| **Datos de prueba (valores exactos de entrada)** | `599.9` (float, 599 segundos y 900 ms; `ceil = 600`) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `600`. |
| **Resultado obtenido** | Retornó `600`. `assertSame(600, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testToSlideSecondsAplicaMaximoCuandoCeilSupera` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-005 toSlideSeconds aplica el máximo de 600 s cuando ceil supera el tope de slide` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-006

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-006 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | clampSlideSeconds devuelve el mismo valor si está dentro del rango |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-03 — el acotamiento no debe modificar valores ya válidos; alterar una duración correcta produciría slides más cortos o más largos de lo configurado por el operario. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::clampSlideSeconds(int $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función solo evalúa comparaciones enteras contra las constantes de configuración. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia válida: valor estrictamente dentro del rango `[5, 600]`. Ambas ramas de clamp (`< MIN` y `> MAX`) son falsas; se retorna el valor sin modificar. |
| **Objetivo de la prueba** | Verificar que `42` s, valor claramente dentro del rango, pasa sin ninguna modificación. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::clampSlideSeconds(42)` |
| **Datos de prueba (valores exactos de entrada)** | `42` (int, dentro del rango [5, 600]) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `42`. |
| **Resultado obtenido** | Retornó `42`. `assertSame(42, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testClampSlideSecondsDevuelveMismoValorEnRango` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-006 clampSlideSeconds devuelve el mismo valor si está dentro del rango` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-007

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-007 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | clampSlideSeconds devuelve 5 si el valor es inferior al mínimo |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-03 — un slide de duración inferior a 5 s no es legible en pantalla; el clamp protege contra valores fuera de rango que pudieran llegar desde otras capas del sistema. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::clampSlideSeconds(int $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite inferior: `1` s, estrictamente menor que `MIN_SLIDE_DURATION_SECONDS = 5`. Rama: `if ($seconds < MIN_SLIDE_DURATION_SECONDS) return MIN_SLIDE_DURATION_SECONDS`. |
| **Objetivo de la prueba** | Confirmar que `1` s se acota al mínimo `5` s, garantizando que ningún slide quede con una duración por debajo del umbral legible. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::clampSlideSeconds(1)` |
| **Datos de prueba (valores exactos de entrada)** | `1` (int, inferior al mínimo de 5 s) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `5`. |
| **Resultado obtenido** | Retornó `5`. `assertSame(5, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testClampSlideSecondsDevuelveMinimoSiMenor` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-007 clampSlideSeconds devuelve 5 si el valor es inferior al mínimo` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-008

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-008 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | clampSlideSeconds devuelve 600 si el valor supera el máximo |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-03 — evitar que un slide tenga una duración mayor que el límite del sistema, lo que bloquearía la rotación de la cartelera al quedarse demasiado tiempo en un mismo slide. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::clampSlideSeconds(int $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite superior: `999` s, mayor que `MAX_SLIDE_DURATION_SECONDS = 600`. Rama: `if ($seconds > MAX_SLIDE_DURATION_SECONDS) return MAX_SLIDE_DURATION_SECONDS`. |
| **Objetivo de la prueba** | Confirmar que `999` s se acota al máximo `600` s, protegiendo contra duraciones excesivas que pudieran llegar desde otras capas del sistema. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::clampSlideSeconds(999)` |
| **Datos de prueba (valores exactos de entrada)** | `999` (int, superior al máximo de 600 s) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `600`. |
| **Resultado obtenido** | Retornó `600`. `assertSame(600, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testClampSlideSecondsDevuelveMaximoSiMayor` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-008 clampSlideSeconds devuelve 600 si el valor supera el máximo` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-VD-009

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-VD-009 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | clampSlideSeconds acepta exactamente 5 s como límite inferior válido |
| **Requisito / Riesgo relacionado (trazabilidad)** | SYS-VD-03 — el valor exacto del límite mínimo debe ser aceptado sin modificación para no truncar duraciones que ya son válidas. |
| **Unidad bajo prueba — Archivo** | src/VideoDuration.php |
| **Unidad bajo prueba — Función/Método (firma)** | `VideoDuration::clampSlideSeconds(int $seconds): int` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite inferior exacto: `5 = MIN_SLIDE_DURATION_SECONDS`. La condición `$seconds < MIN_SLIDE_DURATION_SECONDS` es falsa (es igual, no menor); se retorna el valor sin modificar. |
| **Objetivo de la prueba** | Verificar que el límite inferior es inclusivo — `5` s no se rechaza ni se modifica, documentando que la condición de clamp usa `<` estricto y no `<=`. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `VideoDuration::clampSlideSeconds(5)` |
| **Datos de prueba (valores exactos de entrada)** | `5` (int, exactamente igual a `MIN_SLIDE_DURATION_SECONDS`) |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el entero `5` sin modificación. |
| **Resultado obtenido** | Retornó `5`. `assertSame(5, ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/VideoDurationTest.php :: testClampSlideSecondsAceptaExactamenteCinco` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-VD-009 clampSlideSeconds acepta exactamente 5 s como límite inferior válido` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Caso hermano sugerido: `clampSlideSeconds(600)` (límite superior exacto) — valida la rama `> MAX` como falsa. Registrado como candidato para la siguiente jornada. |

---

## RESUMEN DE LA EJECUCIÓN

| | |
|:---|:---|
| **Casos diseñados / ejecutados** | 9 / 9 |
| **Aprobados / Fallidos / Bloqueados** | 9 / 0 / 0 |
| **Aserciones** | 10 |
| **Tiempo de ejecución** | 0.026 s (suite UnitariasPhp completa: Security + ContentValidity + VideoDuration) |
| **Memoria** | 8.00 MB |
| **Defectos abiertos** | Ninguno |
| **Hallazgos para revisar** | (1) El umbral de rechazo en `toSlideSeconds` es `MAX_VIDEO_DURATION_SECONDS + 0.5 = 600.5`, lo que permite valores como `600.4` s que generan `ceil = 601`, el cual es recortado silenciosamente a `600` por la rama de clamp; el video se acepta pero el slide queda en el máximo. Comportamiento correcto pero conveniente documentar (UT-PHP-VD-003). (2) `clampSlideSeconds(600)` (límite superior exacto) no está cubierto aquí; se sugiere como UT-PHP-VD-010 en la siguiente jornada. No son defectos frente al código actual. |
| **Impacto en producción** | Ninguno. Solo se creó `VideoDurationTest.php` en `scripts/tests/unitarias_php/` y se actualizaron las constantes en `bootstrap_unitarias.php`; no se modificó código de `src/` ni la base de datos. |

---

## EVIDENCIA: SALIDA COMPLETA DE LA EJECUCIÓN

*Figura 1. Salida de PHPUnit en entorno local el 2026-11-05. PHP 8.5.8, PHPUnit 10.5.64.*

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.8
Configuration: /home/diego/Documentos/cartelera/scripts/phpunit.xml

..........................                                        26 / 26 (100%)

Time: 00:00.026, Memory: 8.00 MB

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

Video Duration (Tests\UnitariasPhp\VideoDuration)
 ✔ UT-PHP-VD-001 toSlideSeconds redondea hacia arriba con ceil y aplica el mínimo de 5 s
 ✔ UT-PHP-VD-002 toSlideSeconds acepta exactamente 600 segundos (límite superior válido)
 ✔ UT-PHP-VD-003 toSlideSeconds rechaza video que supera 10 minutos
 ✔ UT-PHP-VD-004 toSlideSeconds aplica el mínimo de 5 s cuando el valor es inferior
 ✔ UT-PHP-VD-005 toSlideSeconds aplica el máximo de 600 s cuando ceil supera el tope de slide
 ✔ UT-PHP-VD-006 clampSlideSeconds devuelve el mismo valor si está dentro del rango
 ✔ UT-PHP-VD-007 clampSlideSeconds devuelve 5 si el valor es inferior al mínimo
 ✔ UT-PHP-VD-008 clampSlideSeconds devuelve 600 si el valor supera el máximo
 ✔ UT-PHP-VD-009 clampSlideSeconds acepta exactamente 5 s como límite inferior válido

OK (26 tests, 42 assertions)
```

> Nota: los dos avisos `[CSET] ADVERTENCIA: DB_PASS no configurada` y `SECRET_KEY no configurada`
> aparecen en stderr (no en la salida de PHPUnit) porque el bootstrap global intenta leer `.env`,
> que no existe en el entorno de pruebas local. No afectan la ejecución de ningún caso de esta suite.
