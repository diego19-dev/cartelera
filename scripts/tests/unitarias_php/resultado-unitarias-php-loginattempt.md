# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 05: LoginAttempt — 8 casos, 8 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Control de acceso y prevención de ataques de fuerza bruta (`LoginAttempt::shouldSkipRateLimit` y firmas en `src/LoginAttempt.php`) |
| **Versión del código** | Código en repositorio local. `src/LoginAttempt.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-11 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64. Bootstrap aislado `bootstrap_unitarias.php`. Sin conexión a base de datos. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 8 | 8 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-LA-001: shouldSkipRateLimit retorna false para IP externa estándar
- **Objetivo**: Garantizar que direcciones IPv4 convencionales de clientes no puedan evadir el rate limiting.
- **Entrada**: `'192.168.1.50'`
- **Resultado esperado**: `false`
- **Resultado obtenido**: `false` (Aprobado).

### UT-PHP-LA-002: shouldSkipRateLimit no omite loopback IPv4 en entorno testing
- **Objetivo**: Confirmar que en entornos de pruebas (no development), loopback IPv4 no omite el conteo.
- **Entrada**: `'127.0.0.1'`
- **Resultado esperado**: `false`
- **Resultado obtenido**: `false` (Aprobado).

### UT-PHP-LA-003: shouldSkipRateLimit no omite loopback IPv6 en entorno testing
- **Objetivo**: Validar el comportamiento para loopback IPv6 compacto (`::1`).
- **Entrada**: `'::1'`
- **Resultado esperado**: `false`
- **Resultado obtenido**: `false` (Aprobado).

### UT-PHP-LA-004: shouldSkipRateLimit retorna false para direcciones IP públicas
- **Objetivo**: Prevenir bypass para cualquier dirección IP en Internet.
- **Entrada**: `'200.89.45.12'`
- **Resultado esperado**: `false`
- **Resultado obtenido**: `false` (Aprobado).

### UT-PHP-LA-005: shouldSkipRateLimit no omite loopback expandido IPv6 en testing
- **Objetivo**: Asegurar que representaciones completas de IPv6 no evadan el control.
- **Entrada**: `'0:0:0:0:0:0:0:1'`
- **Resultado esperado**: `false`
- **Resultado obtenido**: `false` (Aprobado).

### UT-PHP-LA-006: isBlocked tiene firma tipada estática callable con tres parámetros
- **Objetivo**: Validar contrato estático de `isBlocked(string $ip, int $maxAttempts, int $windowMinutes): bool`.
- **Resultado esperado**: 3 parámetros tipados, estático y público.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-LA-007: record acepta parámetros opcionales nulos para usuario y agente
- **Objetivo**: Validar que `record` permita registrar intentos con username o user agent nulos.
- **Resultado esperado**: Permite null en parámetros 1, 3 y 4.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-LA-008: clearFailuresForIp tiene valor por defecto de 15 minutos de ventana
- **Objetivo**: Verificar que el valor predeterminado de ventana para desbloqueo manual sea de 15 minutos.
- **Resultado esperado**: `$windowMinutes = 15`.
- **Resultado obtenido**: Conforme (Aprobado).
