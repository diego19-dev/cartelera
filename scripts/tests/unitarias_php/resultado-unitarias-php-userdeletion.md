# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 04: UserDeletion (User) — 9 casos, 9 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Gestión de usuarios — reglas de bloqueo para eliminación de cuenta (`User::deletionBlockReason` en `src/User.php`) |
| **Versión del código** | Código en repositorio local. `src/User.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-06 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64 (instalado vía composer en `scripts/`). Sin acceso a base de datos ni sesión HTTP. Bootstrap aislado `bootstrap_unitarias.php` con inclusión directa de `src/User.php`. |

---

## CASO DE PRUEBA UT-PHP-USR-001

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-001 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | deletionBlockReason retorna error cuando el usuario objetivo es null |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios (solo admin). Previene excepciones fatales de acceso a índices inexistentes cuando se intenta borrar un identificador que no existe en el sistema. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. La función opera puramente sobre parámetros en memoria pasados a la función estática. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia: entrada nula / usuario inexistente. Rama: `if ($target === null) return 'Usuario no encontrado.';`. |
| **Objetivo de la prueba** | Confirmar que si el usuario objetivo es `null`, la función retorna inmediatamente el mensaje `"Usuario no encontrado."` sin intentar evaluar accesos a arrays o variables no inicializadas. |
| **Precondición (preparar)** | Ninguna; la función no depende de estado externo. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(null, 1, 2)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = null`, `$actorId = 1`, `$adminCount = 2` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el string `'Usuario no encontrado.'`. |
| **Resultado obtenido** | Retornó `'Usuario no encontrado.'`. `assertSame('Usuario no encontrado.', ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonTargetNuloRetornaUsuarioNoEncontrado` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-001 deletionBlockReason retorna error cuando el usuario objetivo es null` (ver Figura 1 al final del documento) |
| **Poscondición** | Ninguna; la prueba no escribe datos ni archivos (se ejecutó con `--do-not-cache-result`). |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-002

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-002 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Crítica |
| **Título de la prueba** | deletionBlockReason impide que un usuario elimine su propia cuenta |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios. Regla de negocio de auto-eliminación: un operador activo no puede suprimir su propia cuenta desde el panel administrativo. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Clase de equivalencia: coincidencia de actor con usuario objetivo. Rama: `if ((int) $target['id'] === $actorId) return 'No puede eliminar su propia cuenta.';`. |
| **Objetivo de la prueba** | Confirmar que si el ID del objetivo coincide con el ID del operador en sesión, se bloquea la acción con el mensaje `"No puede eliminar su propia cuenta."`. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 5, 'username' => 'editor1', 'role' => 'editor'], 5, 2)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 5, 'username' => 'editor1', 'role' => 'editor']`, `$actorId = 5`, `$adminCount = 2` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna el string `'No puede eliminar su propia cuenta.'`. |
| **Resultado obtenido** | Retornó `'No puede eliminar su propia cuenta.'`. `assertSame('No puede eliminar su propia cuenta.', ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonImpideAutoEliminacionMismoId` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-002 deletionBlockReason impide que un usuario elimine su propia cuenta` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-003

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-003 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | deletionBlockReason maneja ID como string numérico en auto-eliminación |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios. Integridad de tipos ante respuestas de drivers PDO PostgreSQL donde los identificadores primarios pueden ser hidratados como cadenas de texto numéricas (`string`). |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Prueba de robustez y compatibilidad de tipos: casteo explícito `(int) $target['id']` frente a un `$actorId` entero. |
| **Objetivo de la prueba** | Verificar que un `$target['id']` con valor de cadena `'10'` se castea correctamente y detecta la igualdad estricta con el entero `10`, bloqueando la auto-eliminación. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => '10', 'username' => 'jefe_salud', 'role' => 'program_head'], 10, 2)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => '10', 'username' => 'jefe_salud', 'role' => 'program_head']`, `$actorId = 10`, `$adminCount = 2` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `'No puede eliminar su propia cuenta.'`. |
| **Resultado obtenido** | Retornó `'No puede eliminar su propia cuenta.'`. `assertSame('No puede eliminar su propia cuenta.', ...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonManejaIdStringNumericoEnAutoEliminacion` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-003 deletionBlockReason maneja ID como string numérico en auto-eliminación` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-004

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-004 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Crítica |
| **Título de la prueba** | deletionBlockReason bloquea la eliminación del último administrador si adminCount es 1 |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Protección de último admin. El sistema jamás debe quedar en estado huérfano sin al menos una cuenta con privilegio de administración. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite: `$adminCount = 1`. Rama: `if (($target['role'] ?? '') === 'admin' && $adminCount <= 1)`. |
| **Objetivo de la prueba** | Verificar que al intentar eliminar un usuario con rol `admin` cuando el conteo total de administradores activos es exactamente `1`, la operación sea bloqueada. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 3, 'username' => 'admin_principal', 'role' => 'admin'], 1, 1)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 3, 'username' => 'admin_principal', 'role' => 'admin']`, `$actorId = 1`, `$adminCount = 1` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `'No se puede eliminar al último administrador del sistema.'`. |
| **Resultado obtenido** | Retornó `'No se puede eliminar al último administrador del sistema.'`. `assertSame(...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonBloqueaEliminacionDeUltimoAdmin` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-004 deletionBlockReason bloquea la eliminación del último administrador si adminCount es 1` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-005

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-005 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | deletionBlockReason bloquea la eliminación de admin si adminCount es menor o igual a cero |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Protección de último admin. Robustez defensiva ante conteos inconsistentes o desincronizados en la base de datos (`adminCount <= 0`). |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite inferior: `$adminCount = 0 <= 1`. Comprobación del operador relacional `<=` frente a `<`. |
| **Objetivo de la prueba** | Confirmar que si por error de conteo se suministra `adminCount = 0`, la condición `$adminCount <= 1` se cumple y continúa protegiendo el registro admin. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 3, 'username' => 'admin_test', 'role' => 'admin'], 1, 0)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 3, 'username' => 'admin_test', 'role' => 'admin']`, `$actorId = 1`, `$adminCount = 0` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `'No se puede eliminar al último administrador del sistema.'`. |
| **Resultado obtenido** | Retornó `'No se puede eliminar al último administrador del sistema.'`. `assertSame(...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonBloqueaEliminacionDeAdminConConteoCeroONegativo` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-005 deletionBlockReason bloquea la eliminación de admin si adminCount es menor o igual a cero` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-006

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-006 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | deletionBlockReason permite eliminar un administrador cuando existen 2 o más administradores |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios. Permite la baja de administradores redundantes o que cambian de cargo, siempre que quede al menos un admin en el sistema. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Valor límite superior: `$adminCount = 2 > 1`. La condición `$adminCount <= 1` evalúa a falso. |
| **Objetivo de la prueba** | Confirmar que si existen 2 administradores, la función retorna `null` (sin bloqueo de eliminación) al intentar borrar a uno de ellos por parte de otro operador distinto. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 4, 'username' => 'admin_secundario', 'role' => 'admin'], 1, 2)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 4, 'username' => 'admin_secundario', 'role' => 'admin']`, `$actorId = 1`, `$adminCount = 2` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `null`. |
| **Resultado obtenido** | Retornó `null`. `assertNull(...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonPermiteEliminarAdminSiHayDosOMas` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-006 deletionBlockReason permite eliminar un administrador cuando existen 2 o más administradores` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-007

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-007 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | deletionBlockReason permite eliminar un usuario no admin aunque solo exista un administrador |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios. La restricción de último administrador no debe interferir con la eliminación normal de usuarios con rol `program_head` o `editor`. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Tabla de decisión: `$target['role'] !== 'admin'` y `$adminCount = 1`. La primera condición de la conjunción evalúa a falso. |
| **Objetivo de la prueba** | Comprobar que un jefe de programa puede ser eliminado sin impedimento aun cuando solo exista un admin activo en el sistema. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 8, 'username' => 'jefe_turismo', 'role' => 'program_head'], 1, 1)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 8, 'username' => 'jefe_turismo', 'role' => 'program_head']`, `$actorId = 1`, `$adminCount = 1` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `null`. |
| **Resultado obtenido** | Retornó `null`. `assertNull(...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonPermiteEliminarUsuarioNoAdminConUnSoloAdmin` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-007 deletionBlockReason permite eliminar un usuario no admin aunque solo exista un administrador` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-008

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-008 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Media |
| **Título de la prueba** | deletionBlockReason permite eliminar un usuario con rol ausente o null si no es auto-eliminación |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Robustez de datos. Comportamiento seguro ante registros legados o incompletos donde el campo `role` no esté seteado o sea `null`. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Coalescencia nula: `$target['role'] ?? ''`. Verifica ausencia de Warning en PHP por clave no definida o valor nulo. |
| **Objetivo de la prueba** | Confirmar que el operador de coalescencia nula evita warnings de PHP cuando la clave `role` no está en el array o es `null`, resolviendo a `''` y retornando `null`. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | (1) `User::deletionBlockReason(['id' => 9, 'username' => 'invitado'], 1, 1)` <br> (2) `User::deletionBlockReason(['id' => 12, 'username' => 'sin_rol', 'role' => null], 1, 1)` |
| **Datos de prueba (valores exactos de entrada)** | (1) `$target = ['id' => 9, 'username' => 'invitado']` <br> (2) `$target = ['id' => 12, 'username' => 'sin_rol', 'role' => null]` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Ambas llamadas retornan `null`. |
| **Resultado obtenido** | Ambas retornaron `null`. `assertNull` aprobado en ambos casos. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonPermiteEliminarUsuarioConRolAusenteONull` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-008 deletionBlockReason permite eliminar un usuario con rol ausente o null si no es auto-eliminación` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## CASO DE PRUEBA UT-PHP-USR-009

| | |
|:---|:---|
| **Caso de prueba N.º** | UT-PHP-USR-009 |
| **Tipo de prueba** | Unitaria (PHP) |
| **Prioridad (Alta / Media / Baja)** | Alta |
| **Título de la prueba** | deletionBlockReason prioriza la validación de auto-eliminación sobre la regla de último admin |
| **Requisito / Riesgo relacionado (trazabilidad)** | RF-05 — Gestión de usuarios. Orden de guardas de validación: coherencia de retroalimentación al usuario cuando coinciden múltiples causas de bloqueo. |
| **Unidad bajo prueba — Archivo** | src/User.php |
| **Unidad bajo prueba — Función/Método (firma)** | `User::deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string` |
| **Dependencias externas (debe ser «Ninguna»; si usa BD no es unitaria)** | Ninguna. |
| **Técnica de diseño (equivalencia / valor límite / tabla de decisión / rama)** | Análisis de flujo de control / prioridad de guardas: el actor es el último admin (`actorId === target['id']` y `adminCount === 1`). |
| **Objetivo de la prueba** | Verificar que cuando un admin intenta borrarse a sí mismo y a la vez es el último admin, el mensaje reportado corresponde a `"No puede eliminar su propia cuenta."` debido a que la guarda de identidad precede a la de conteo administrativo. |
| **Precondición (preparar)** | Ninguna. |
| **Pasos (ejecutar — llamada exacta)** | `User::deletionBlockReason(['id' => 1, 'username' => 'admin_super', 'role' => 'admin'], 1, 1)` |
| **Datos de prueba (valores exactos de entrada)** | `$target = ['id' => 1, 'username' => 'admin_super', 'role' => 'admin']`, `$actorId = 1`, `$adminCount = 1` |
| **Resultado esperado (verificar — retorno o excepción y mensaje)** | Retorna `'No puede eliminar su propia cuenta.'`. |
| **Resultado obtenido** | Retornó `'No puede eliminar su propia cuenta.'`. `assertSame(...)` aprobado. |
| **Archivo y método de PHPUnit** | `scripts/tests/unitarias_php/UserDeletionTest.php :: testDeletionBlockReasonPriorizaAutoEliminacionSobreUltimoAdmin` |
| **Comando de ejecución** | `php ../lib/bin/phpunit -c phpunit.xml --testdox --do-not-cache-result --testsuite UnitariasPhp` |
| **Evidencia (salida de PHPUnit)** | `✔ UT-PHP-USR-009 deletionBlockReason prioriza la validación de auto-eliminación sobre la regla de último admin` (ver Figura 1) |
| **Poscondición** | Ninguna. |
| **Estado (Aprobado / Fallido / Bloqueado)** | **Aprobado** |
| **ID de defecto (si falla — numerar internamente en este documento)** | No aplica |
| **Notas / comentarios** | Sin observaciones. |

---

## RESUMEN DE LA EJECUCIÓN

| | |
|:---|:---|
| **Casos diseñados / ejecutados** | 9 / 9 |
| **Aprobados / Fallidos / Bloqueados** | 9 / 0 / 0 |
| **Aserciones** | 10 |
| **Tiempo de ejecución** | 0.014 s (suite UnitariasPhp completa: Security + ContentValidity + VideoDuration + UserDeletion) |
| **Memoria** | 8.00 MB |
| **Defectos abiertos** | Ninguno |
| **Hallazgos para revisar** | El método `User::deletionBlockReason` presenta cobertura total de ramas y sentencias. Se verificó que el casteo a `(int)` en `$target['id']` previene falsos negativos si PDO devuelve identificadores como strings. |
| **Impacto en producción** | Ninguno. Solo se creó `UserDeletionTest.php` en `scripts/tests/unitarias_php/` y se registró `require_once $srcPath . '/User.php'` en `bootstrap_unitarias.php`; no se modificó el código de producción en `src/` ni la base de datos. |

---

## EVIDENCIA: SALIDA COMPLETA DE LA EJECUCIÓN

*Figura 1. Salida de PHPUnit en entorno local el 2026-11-06. PHP 8.5.8, PHPUnit 10.5.64.*

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.8
Configuration: /home/diego/Documentos/cartelera/scripts/phpunit.xml

...................................                               35 / 35 (100%)

Time: 00:00.014, Memory: 8.00 MB

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

User Deletion (Tests\UnitariasPhp\UserDeletion)
 ✔ UT-PHP-USR-001 deletionBlockReason retorna error cuando el usuario objetivo es null
 ✔ UT-PHP-USR-002 deletionBlockReason impide que un usuario elimine su propia cuenta
 ✔ UT-PHP-USR-003 deletionBlockReason maneja ID como string numérico en auto-eliminación
 ✔ UT-PHP-USR-004 deletionBlockReason bloquea la eliminación del último administrador si adminCount es 1
 ✔ UT-PHP-USR-005 deletionBlockReason bloquea la eliminación de admin si adminCount es menor o igual a cero
 ✔ UT-PHP-USR-006 deletionBlockReason permite eliminar un administrador cuando existen 2 o más administradores
 ✔ UT-PHP-USR-007 deletionBlockReason permite eliminar un usuario no admin aunque solo exista un administrador
 ✔ UT-PHP-USR-008 deletionBlockReason permite eliminar un usuario con rol ausente o null si no es auto-eliminación
 ✔ UT-PHP-USR-009 deletionBlockReason prioriza la validación de auto-eliminación sobre la regla de último admin

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

OK (35 tests, 52 assertions)
```

> Nota: los dos avisos `[CSET] ADVERTENCIA: DB_PASS no configurada` y `SECRET_KEY no configurada`
> aparecen en stderr (no en la salida de PHPUnit) porque el bootstrap global intenta leer `.env`,
> que no existe en el entorno de pruebas local. No afectan la ejecución de ningún caso de esta suite.
