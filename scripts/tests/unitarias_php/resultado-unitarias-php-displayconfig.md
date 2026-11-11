# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 06: DisplayConfig — 9 casos, 9 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Configuración de pantalla y sanitización de bienvenida (`src/DisplayConfig.php`) |
| **Versión del código** | Código en repositorio local. `src/DisplayConfig.php` — rama principal. |
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

### UT-PHP-DC-001: sanitizeWelcomeHtml conserva etiquetas permitidas b y cierre b
- **Objetivo**: Asegurar que las etiquetas `<b>` y `</b>` requeridas para énfasis en el lema institucional se mantengan intactas.
- **Entrada**: `'Texto con <b>negrita</b> permitida'`
- **Resultado esperado**: `'Texto con <b>negrita</b> permitida'`
- **Resultado obtenido**: Idéntico (Aprobado).

### UT-PHP-DC-002: sanitizeWelcomeHtml escapa scripts y atributos maliciosos
- **Objetivo**: Prevenir ataques XSS a través de inyecciones `<script>` en los campos de bienvenida.
- **Entrada**: `'Bienvenido <script>alert("xss")</script>'`
- **Resultado esperado**: Convierte a `&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;`
- **Resultado obtenido**: Escapado seguro (Aprobado).

### UT-PHP-DC-003: sanitizeWelcomeHtml convierte otras etiquetas como cursiva o encabezados
- **Objetivo**: Verificar que etiquetas HTML no autorizadas (`<i>`, `<u>`) sean neutralizadas.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-DC-004: sanitizeWelcomeHtml cierra automáticamente etiqueta b al final
- **Objetivo**: Evitar desmaquetado del slide si el usuario deja una etiqueta `<b>` sin cerrar.
- **Entrada**: `'Texto que abre <b>negrita sin cerrar'`
- **Resultado esperado**: Termina con `</b>`
- **Resultado obtenido**: Auto-cierre exitoso (Aprobado).

### UT-PHP-DC-005: sanitizeWelcomeHtml ignora etiquetas de cierre b sin apertura previa
- **Objetivo**: Eliminar cierres huérfanos `</b>` que no tienen etiqueta de apertura correspondiente.
- **Resultado obtenido**: Cierre huérfano omitido (Aprobado).

### UT-PHP-DC-006: sanitizeWelcomeHtml escapa caracteres especiales como comillas y ampersand
- **Objetivo**: Garantizar codificación de entidades HTML5 para comillas dobles y ampersands.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-DC-007: welcomeDefaults retorna las 21 claves predeterminadas del SENA CSET
- **Objetivo**: Validar la existencia de las 21 claves institucionales (saludo, titulo, centro, regional, colores).
- **Resultado obtenido**: 21 claves presentes (Aprobado).

### UT-PHP-DC-008: welcomeDefaults tiene duración entera positiva y colores válidos
- **Objetivo**: Verificar formato `#RRGGBB` en colores y duración mínima de 3 segundos.
- **Resultado obtenido**: Conforme (Aprobado).

### UT-PHP-DC-009: welcomeEditableKeys contiene exactamente las 19 claves permitidas
- **Objetivo**: Confirmar que los paths de fondos no puedan ser sobreescritos mediante la API REST directa.
- **Resultado obtenido**: Exactamente 19 claves (Aprobado).
