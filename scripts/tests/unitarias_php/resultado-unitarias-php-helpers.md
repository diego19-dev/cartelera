# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 08: Helpers (Vistas y Permisos) — 10 casos, 10 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Helpers de vistas, escapado seguro, URLs institucionales y validación de roles (`src/helpers.php`) |
| **Versión del código** | Código en repositorio local. `src/helpers.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-12 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64 (instalado vía composer en `scripts/`). Sin acceso a base de datos externa ni sesión HTTP activa. Bootstrap aislado `bootstrap_unitarias.php`. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 10 | 10 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-HLP-001: e escapa caracteres especiales HTML previniendo XSS
- **Objetivo**: Garantizar que etiquetas y caracteres HTML sensibles sean convertidos a entidades seguras.
- **Entrada**: `'<script>alert("xss")</script> & \'comillas\''`
- **Resultado esperado**: Contiene `&lt;script&gt;`, `&amp;`, `&quot;`, `&#039;` y no contiene `<script>`.
- **Resultado obtenido**: Cadena saneada correctamente. **Aprobado**.

### UT-PHP-HLP-002: e devuelve cadena vacía cuando el valor es null
- **Objetivo**: Asegurar que valores nulos pasados al helper no generen excepciones de tipo.
- **Entrada**: `null`
- **Resultado esperado**: `""` (cadena vacía).
- **Resultado obtenido**: `""`. **Aprobado**.

### UT-PHP-HLP-003: role_label mapea roles del sistema a nombres legibles en español
- **Objetivo**: Validar la traducción de roles internos a etiquetas legibles para la interfaz administrativa.
- **Entrada**: `'admin'`, `'program_head'`, `'editor'`, `'viewer'`, `null`.
- **Resultado esperado**: `Administrador del Sistema`, `Jefe de programa`, `Editor`, `Solo lectura`, `Usuario`.
- **Resultado obtenido**: Mapeo exacto conforme a la especificación. **Aprobado**.

### UT-PHP-HLP-004: cset_url antepone el prefijo institucional APP_BASE de forma consistente
- **Objetivo**: Comprobar que todas las URLs generadas incluyan el prefijo base (`/tv/`).
- **Entrada**: `''`, `'admin/users'`, `'/admin/users'`, `'login.php'`.
- **Resultado esperado**: `'/tv/'`, `'/tv/admin/users'`, `'/tv/login.php'`.
- **Resultado obtenido**: Rutas bien formadas sin dobles barras. **Aprobado**.

### UT-PHP-HLP-005: cset_validate_user_fields asigna program_area null a usuarios con rol admin
- **Objetivo**: Evitar asignaciones espurias de área de formación a los administradores generales.
- **Entrada**: `role = 'admin'`, `programArea = 'tecnologia'`.
- **Resultado esperado**: `['role' => 'admin', 'program_area' => null]`.
- **Resultado obtenido**: Área limpiada a null. **Aprobado**.

### UT-PHP-HLP-006: cset_validate_user_fields acepta rol program_head cuando tiene área válida
- **Objetivo**: Permitir la creación de jefes de programa asignándoles su área saneada.
- **Entrada**: `role = 'program_head'`, `programArea = '  salud  '`.
- **Resultado esperado**: `['role' => 'program_head', 'program_area' => 'salud']`.
- **Resultado obtenido**: Registro validado con área limpia. **Aprobado**.

### UT-PHP-HLP-007: cset_validate_user_fields lanza RuntimeException si program_head no tiene área
- **Objetivo**: Forzar la regla de negocio que exige área obligatoria para jefes de programa.
- **Entrada**: `role = 'program_head'`, `programArea = null`.
- **Resultado esperado**: Lanza `RuntimeException` con mensaje `"Los jefes de programa deben tener un área asignada."`.
- **Resultado obtenido**: Excepción capturada con el mensaje exacto. **Aprobado**.

### UT-PHP-HLP-008: cset_validate_user_fields rechaza roles fuera de PANEL_ASSIGNABLE_ROLES
- **Objetivo**: Prevenir escalada de privilegios o inyección de roles inválidos.
- **Entrada**: `role = 'superadmin_invalido'`, `programArea = null`.
- **Resultado esperado**: Lanza `RuntimeException` con mensaje `"Rol no permitido."`.
- **Resultado obtenido**: Excepción capturada con el mensaje exacto. **Aprobado**.

### UT-PHP-HLP-009: cset_panel_module_enabled detecta módulos activos y deshabilitados
- **Objetivo**: Comprobar que los módulos deshabilitados (`center`, `gallery`) sean identificados y ocultados.
- **Entrada**: `'center'`, `'gallery'`, `'academic'`, `'general'`, `'users'`.
- **Resultado esperado**: `false` para center y gallery; `true` para los demás.
- **Resultado obtenido**: Estados coherentes con la configuración del CSET. **Aprobado**.

### UT-PHP-HLP-010: format_activity_when formatea timestamp ISO a zona horaria America/Bogota
- **Objetivo**: Validar la conversión precisa de marcas de tiempo UTC a hora local de Colombia.
- **Entrada**: `'2026-11-12T15:00:00Z'` (15:00 UTC).
- **Resultado esperado**: `'12/11/2026 10:00'` (UTC-5).
- **Resultado obtenido**: `'12/11/2026 10:00'`. **Aprobado**.
