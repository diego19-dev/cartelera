# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 12: Router — 6 casos, 6 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Enrutador HTTP y despacho de rutas (`src/Router.php`) |
| **Versión del código** | Código en repositorio local. `src/Router.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-12 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64. Bootstrap aislado `bootstrap_unitarias.php`. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 6 | 6 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-RTR-001: Router ejecuta el handler correcto para una ruta estática
- **Objetivo**: Despacho exitoso de ruta coincidente en método y URI.
- **Resultado obtenido**: Aprobado.

### UT-PHP-RTR-002: Router extrae parámetros dinámicos delimitados por llaves
- **Objetivo**: Extracción de `{id}` hacia el array asociativo `$params`.
- **Resultado obtenido**: Aprobado (`id = '105'`).

### UT-PHP-RTR-003: Router respeta el método HTTP solicitado evitando ejecuciones cruzadas
- **Objetivo**: Aislar rutas GET y POST con idéntica URI.
- **Resultado obtenido**: Aprobado.

### UT-PHP-RTR-004: Router ejecuta funciones middleware antes del handler principal
- **Objetivo**: Secuencia garantizada de ejecución: middlewares en orden y luego el handler.
- **Resultado obtenido**: Aprobado.

### UT-PHP-RTR-005: Router emite encabezado 404 ante rutas no coincidentes
- **Objetivo**: Generación de respuesta 404 HTML estándar.
- **Resultado obtenido**: Aprobado.

### UT-PHP-RTR-006: Router normaliza base path eliminando barras finales
- **Objetivo**: Tolerancia ante rutas base con barras repetidas (`/tv///`).
- **Resultado obtenido**: Aprobado.
