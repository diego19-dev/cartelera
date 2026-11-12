# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 09: Icons — 6 casos, 6 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Generador de SVG Lucide inline para panel y cartelera (`cset_icon` en `src/icons.php`) |
| **Versión del código** | Código en repositorio local. `src/icons.php` — rama principal. |
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

### UT-PHP-ICO-001: cset_icon genera un elemento svg válido con atributos correctos
- **Objetivo**: Garantizar que el string generado cumpla el estándar XML/SVG con viewBox y stroke.
- **Resultado obtenido**: Aprobado.

### UT-PHP-ICO-002: cset_icon aplica correctamente el tamaño especificado en width y height
- **Objetivo**: Verificar que `$size` personalice las dimensiones del SVG.
- **Resultado obtenido**: Aprobado.

### UT-PHP-ICO-003: cset_icon concatena clases CSS preservando shrink-0 y sanitizando
- **Objetivo**: Asegurar clases como `shrink-0 text-green-500 mr-2`.
- **Resultado obtenido**: Aprobado.

### UT-PHP-ICO-004: cset_icon utiliza el path de info como fallback ante nombres inexistentes
- **Objetivo**: Evitar fallos de interfaz cuando se solicita un icono no registrado en el diccionario.
- **Resultado obtenido**: Aprobado.

### UT-PHP-ICO-005: cset_icon resuelve correctamente iconos institucionales del CSET
- **Objetivo**: Probar los iconos más usados (`tv`, `building-2`, `newspaper`, `images`, `dollar-sign`).
- **Resultado obtenido**: Aprobado.

### UT-PHP-ICO-006: cset_icon utiliza 18 píxeles como dimensión por defecto
- **Objetivo**: Validar el valor predeterminado del parámetro opcional `$size = 18`.
- **Resultado obtenido**: Aprobado.
