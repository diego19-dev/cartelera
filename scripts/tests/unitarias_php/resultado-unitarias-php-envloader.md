# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 07: EnvLoader — 9 casos, 9 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Carga y gestión de variables de entorno del sistema (`src/EnvLoader.php`) |
| **Versión del código** | Código en repositorio local. `src/EnvLoader.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-11 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64. Bootstrap aislado `bootstrap_unitarias.php`. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 9 | 9 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-ENV-001: get recupera valor existente definido en entorno
- **Objetivo**: Validar lectura transparente de variables seteadas en `$_ENV`.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-002: get retorna valor por defecto si la variable no existe
- **Objetivo**: Confirmar fallback automático ante claves inexistentes.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-003: get retorna valor por defecto si la variable tiene cadena vacía
- **Objetivo**: Tratar cadenas vacías como valores no definidos retornando el default especificado.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-004: load maneja de forma segura ruta de archivo inexistente
- **Objetivo**: Comprobar que rutas no existentes no generen excepciones fatales ni advertencias.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-005: EnvLoader expone métodos estáticos load y get
- **Objetivo**: Verificar accesibilidad y naturaleza estática de los métodos core.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-006: get retorna null cuando no existe y no se especifica default
- **Objetivo**: Validar tipado de retorno nullable cuando no hay valor de respaldo.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-007: Base path y constantes esenciales están definidas tras carga
- **Objetivo**: Verificar coherencia de constantes del sistema (`BASE_PATH`, `APP_ENV`).
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-008: get mantiene tipado nullable string
- **Objetivo**: Comprobar que números almacenados como cadenas no se muten a enteros.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-ENV-009: EnvLoader mantiene estado de carga en propiedad privada estática
- **Objetivo**: Validar encapsulamiento de la bandera `$loaded`.
- **Resultado obtenido**: Conforme (Aprobado).
