# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 10: ContentDisplayOrder — 7 casos, 7 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Secuencia y orden de visualización de contenidos (`src/ContentDisplayOrder.php`) |
| **Versión del código** | Código en repositorio local. `src/ContentDisplayOrder.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-12 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64. Bootstrap aislado `bootstrap_unitarias.php`. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 7 | 7 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-CDO-001..004: assertTable acepta tablas válidas
- **Objetivo**: Confirmar que `academic_content`, `center_content`, `general_content` y `gallery_content` sean permitidas.
- **Resultado obtenido**: Aprobado.

### UT-PHP-CDO-005: assertTable lanza InvalidArgumentException con tablas fuera de la lista blanca
- **Objetivo**: Prevenir que tablas del sistema (como `users`) sean alteradas por este módulo.
- **Resultado obtenido**: Excepción capturada. Aprobado.

### UT-PHP-CDO-006: assertTable previene inyección de sentencias SQL en nombre de tabla
- **Objetivo**: Protección de seguridad contra inyecciones directas en nombres de tablas dinámicas.
- **Resultado obtenido**: Aprobado.

### UT-PHP-CDO-007: ContentDisplayOrder define un tope máximo de 999999 para orden
- **Objetivo**: Verificar constante `MAX = 999999`.
- **Resultado obtenido**: Aprobado.
