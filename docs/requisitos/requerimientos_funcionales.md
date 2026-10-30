# Requerimientos Funcionales — CSET-TV Cartelera Digital

**Sistema:** CSET-TV Cartelera Digital  
**Versión:** 2.0.0  
**Fecha de relevamiento:** Octubre 2026  
**Relevado desde:** Código fuente del repositorio (`src/`, `scripts/`, `index.php`)

---

## Descripción del sistema

CSET-TV es un sistema de cartelería digital para una Institución de Educación Superior (IES/SENA). Sirve contenido multimedia en monitores Smart TV y pantallas Signage 24/7, gestionado desde un panel de administración web construido en PHP clásico sobre una API REST PHP + PostgreSQL. El player de la cartelera corre en el navegador del monitor (incluido LG Signage con Chromium 53) y rota slides agrupados en escenarios temáticos: bienvenida institucional, contenido académico, información del centro, noticias/indicadores generales y galería de imágenes. El panel permite a distintos roles gestionar el contenido de su área sin interferir con el de otros.

---

## Tabla de contenidos

- [RF-01 — Módulo de Autenticación](#rf-01--módulo-de-autenticación)
- [RF-02 — Rate Limiting de Login](#rf-02--rate-limiting-de-login)
- [RF-03 — Gestión de Sesiones](#rf-03--gestión-de-sesiones)
- [RF-04 — Control de Acceso por Roles](#rf-04--control-de-acceso-por-roles)
- [RF-05 — Gestión de Usuarios](#rf-05--gestión-de-usuarios-solo-admin)
- [RF-06 — Contenido Académico](#rf-06--contenido-académico)
- [RF-07 — Contenido Centro](#rf-07--contenido-centro-nuestro-centro)
- [RF-08 — Contenido General](#rf-08--contenido-general)
- [RF-09 — Agrupación de Indicadores en el Feed TV](#rf-09--agrupación-de-indicadores-en-el-feed-tv)
- [RF-10 — Galería de Imágenes](#rf-10--galería-de-imágenes)
- [RF-11 — Slide de Bienvenida Institucional](#rf-11--slide-de-bienvenida-institucional)
- [RF-12 — Gestión de Medios (Subida de Archivos)](#rf-12--gestión-de-medios-subida-de-archivos)
- [RF-13 — Transcodificación de Video para Signage](#rf-13--transcodificación-de-video-para-signage)
- [RF-14 — Detección de Duración de Video](#rf-14--detección-de-duración-de-video)
- [RF-15 — Biblioteca de Medios](#rf-15--biblioteca-de-medios)
- [RF-16 — Feed de la Cartelera (API pública)](#rf-16--feed-de-la-cartelera-api-pública)
- [RF-17 — Player de Cartelera (Comportamiento del Cliente)](#rf-17--player-de-cartelera-comportamiento-del-cliente)
- [RF-18 — Configuración de la Cartelera](#rf-18--configuración-de-la-cartelera)
- [RF-19 — Branding y Logo](#rf-19--branding-y-logo)
- [RF-20 — Indicadores Externos (Cron)](#rf-20--indicadores-externos-cron)
- [RF-21 — Auditoría y Logs](#rf-21--auditoría-y-logs)
- [RF-22 — API REST — Rutas Completas](#rf-22--api-rest--rutas-completas)
- [RF-23 — Redirecciones y Enrutamiento](#rf-23--redirecciones-y-enrutamiento)
- [RF-24 — Dashboard de Administración](#rf-24--dashboard-de-administración)
- [RF-25 — Herramientas de Mantenimiento CLI](#rf-25--herramientas-de-mantenimiento-cli)

---

## RF-01 — Módulo de Autenticación

El acceso al panel de administración requiere autenticación mediante usuario y contraseña. El flujo completo es el siguiente:

- El formulario de login envía las credenciales a `POST /tv/api/auth/login`.
- **Antes** de validar la contraseña, se consulta la tabla `login_attempts` para verificar si la IP del cliente está bloqueada por exceder el límite de intentos fallidos. Si la IP está bloqueada, se retorna error inmediatamente sin procesar la contraseña.
- Se busca el `username` en la tabla `users`. Si no existe, se registra el intento fallido con razón `usuario_no_existe` y se retorna `null`.
- Si el usuario existe pero su campo `is_active` es `false`, se registra el intento con razón `usuario_inactivo` y se retorna `null`. La cuenta inactiva no puede iniciar sesión bajo ninguna circunstancia.
- Si la contraseña no coincide con el `password_hash` almacenado (verificación bcrypt via `password_verify()`), se registra razón `contrasena_incorrecta`, se escribe `login_failed` en `activity_log` y se retorna `null`.
- Si la contraseña es correcta, se comprueba si el hash necesita actualizarse (`password_needs_rehash()` con coste 12). De ser así, se genera un nuevo hash y se actualiza en BD de forma transparente antes de crear la sesión.
- Con login exitoso: se crea la sesión PHP, se actualiza el campo `last_login` del usuario en `users`, se registra el intento exitoso en `login_attempts` y se escribe `login_success` en `activity_log`.
- Los datos que se guardan en la sesión PHP son: `user_id`, `user_name` (nombre completo), `user_username`, `user_role`, `user_program_area` y `user_email`.
- **Logout:** cierra la sesión PHP, caduca la cookie de sesión, elimina el registro correspondiente en la tabla `user_sessions` por token y registra `logout` en `activity_log`.
- En cada request del panel, el sistema sincroniza `role`, `program_area` y `full_name` desde la BD hacia la sesión (`Auth::syncSessionFromDb`). Esto permite que los cambios de rol aplicados por el admin surtan efecto en la próxima petición del afectado sin necesidad de que cierre sesión.
- En entorno `development`, las IPs de loopback (`127.0.0.1`, `::1`) nunca son bloqueadas por rate limiting, para evitar que el desarrollador quede sin acceso durante pruebas.

---

## RF-02 — Rate Limiting de Login

El sistema protege el endpoint de login contra ataques de fuerza bruta mediante control de intentos por IP:

- Se permiten como máximo **5 intentos fallidos** (`LOGIN_MAX_ATTEMPTS = 5`) dentro de una ventana de **15 minutos** (`LOGIN_WINDOW_MINUTES = 15`) por dirección IP.
- El bloqueo es **por IP**, no por nombre de usuario ni por cookie. Cambiar el username no evita el bloqueo si la IP ya lo activó.
- **Todo** intento de login, exitoso o fallido, queda registrado en la tabla `login_attempts` con los siguientes campos: dirección IP, username intentado, resultado (éxito/fallo), razón del fallo y fragmento del user-agent (truncado a 255 caracteres).
- Las razones de fallo posibles son: `usuario_no_existe`, `usuario_inactivo`, `contrasena_incorrecta`.
- Los registros de `login_attempts` se **purgan automáticamente** después de 90 días en cada inserción (sin necesidad de cron aparte).
- Se puede deshabilitar temporalmente el rate limiting en producción sin reiniciar el servidor configurando la variable de entorno `LOGIN_RATE_LIMIT_DISABLED_UNTIL` con una fecha/hora en formato ISO-8601. Mientras esa fecha esté en el futuro, no se aplica el bloqueo. Útil para ventanas de mantenimiento o pruebas coordinadas en producción.
- El desbloqueo manual de una IP se puede hacer borrando sus intentos fallidos recientes desde la herramienta CLI o directamente en la tabla `login_attempts`.

---

## RF-03 — Gestión de Sesiones

El panel usa sesiones PHP estándar con los siguientes parámetros fijos:

- **Nombre de cookie:** `cset_session` (constante `SESSION_NAME`).
- **Duración:** 7200 segundos (2 horas), configurada en `SESSION_LIFETIME` y aplicada tanto al parámetro `lifetime` de la cookie como a `session.gc_maxlifetime`.
- **Regeneración del ID en login:** se llama a `session_regenerate_id(true)` al crear la sesión, invalidando el ID previo y previniendo session fixation.
- **Regeneración periódica:** el ID se regenera automáticamente cada 30 minutos durante la sesión activa (comparando `$_SESSION['_created_at']` con `time()`).
- **Flags de cookie:** `HttpOnly = true` (protege de acceso via JavaScript), `SameSite = Strict` (previene CSRF cross-site), `Secure` configurable — por defecto `false`, se activa solo cuando el servidor tiene TLS configurado.
- **PHP ini:** `session.use_strict_mode = 1`, `session.use_cookies = 1`, `session.use_only_cookies = 1`, `session.cookie_httponly = 1`.
- **Mensajes flash:** el sistema provee `Session::flash()` para mensajes de un solo uso entre redirecciones del panel (confirmación de guardado, errores de formulario, etc.). El mensaje se guarda con la clave indicada y al leerlo se borra automáticamente.
- `Session::isAuthenticated()` retorna `true` solo si existen simultáneamente `user_id` y `user_role` en `$_SESSION`.
- `Session::destroy()` vacía `$_SESSION`, caduca la cookie con `time() - 42000` y llama a `session_destroy()`.

---

## RF-04 — Control de Acceso por Roles

El sistema implementa un modelo de roles con tres niveles:

### Roles disponibles

| Rol | Descripción |
|-----|-------------|
| `admin` | Acceso total al panel. Único rol que puede gestionar usuarios, configuración global, branding e identidad visual, y el slide de bienvenida. |
| `program_head` | Jefe de programa. Puede crear, editar, activar/desactivar y eliminar contenido académico, pero **únicamente** del sector (`program_area`) que tiene asignado. No puede acceder ni ver el contenido de otros sectores. |
| `editor` | Editor de contenido general. Puede gestionar slides de información general e indicadores externos. |

### Evaluación de permisos por área

La función `Auth::canEditArea($area)` determina si el usuario en sesión puede editar contenido de un área dada:

- `admin` o `editor`: siempre retorna `true` (acceso a todo el contenido).
- `program_head` con área asignada: retorna `true` solo si `$area === session.user_program_area`.
- `program_head` sin área asignada (campo vacío): retorna `false` — no puede editar nada hasta que el admin le asigne un sector.
- Cualquier otro caso: retorna `false`.

### Respuestas de acceso denegado

- **Panel (páginas HTML):** si no hay sesión activa, redirige a `/tv/login.php`. Si hay sesión pero el rol no es suficiente, devuelve una página HTML con código HTTP 403, diseñada con el estilo institucional del sistema.
- **API (endpoints JSON):** sin sesión → HTTP 401 con `{"ok": false, "error": "No autenticado"}`. Con sesión pero sin rol → HTTP 403 con `{"ok": false, "error": "Acceso denegado"}`.

---

## RF-05 — Gestión de Usuarios (solo admin)

Solo los usuarios con rol `admin` pueden acceder al CRUD completo de usuarios.

### Campos del usuario

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `username` | string | Único; solo `[a-zA-Z0-9_]`; obligatorio |
| `full_name` | string | Obligatorio |
| `email` | string | Opcional |
| `role` | enum | Solo valores de `PANEL_ASSIGNABLE_ROLES`: `admin`, `program_head` |
| `program_area` | string | Obligatorio para `program_head`; debe ser clave de `PROGRAM_AREAS` |
| `is_active` | bool | Activo por defecto al crear |
| `password` | string | Obligatorio al crear; opcional al editar (si se envía, reemplaza la actual) |

### Reglas de negocio

- El `username` debe ser único en el sistema. Si ya existe otro usuario con ese username al crear o editar, se retorna HTTP 409.
- La contraseña debe tener al menos 10 caracteres e incluir como mínimo una letra minúscula, una mayúscula, un dígito y un carácter especial (cualquier carácter que no sea alfanumérico). Si no cumple, la operación falla con HTTP 422 y mensaje `"Contraseña débil"`.
- Los roles asignables desde el panel están definidos en `PANEL_ASSIGNABLE_ROLES`. Actualmente: `admin` y `program_head`. El rol `editor` no aparece en ese array (puede estar en la BD pero no se puede asignar desde la interfaz).
- **Eliminar usuario:** requiere que el cliente envíe el campo `confirm_username` con el `username` exacto del usuario a borrar. La comparación usa `hash_equals()` (timing-safe). Si no coincide exactamente, la operación falla con HTTP 422.
- **Protección de último admin:** si el usuario a eliminar es el único con rol `admin` activo, la operación falla con error descriptivo. No se puede dejar el sistema sin admin.
- **Auto-desactivación:** un usuario no puede desactivar su propia cuenta desde el panel. Si el `id` del usuario a desactivar coincide con el `user_id` de la sesión activa y el usuario está activo, la operación falla.
- **Edición propia:** si el admin edita su propia cuenta, la sesión se actualiza inmediatamente con el nuevo `username`, `role` y `program_area` para que el cambio tenga efecto sin necesidad de cerrar sesión.
- **Búsqueda:** el endpoint `GET /api/users?q=texto` filtra los resultados en memoria (no en SQL) comparando el texto contra `username` y `full_name` con `stripos`.
- **Auditoría:** todas las operaciones quedan registradas en `activity_log` con las acciones: `create_user`, `update_user`, `toggle_user`, `delete_user`.
- El campo `password_hash` nunca se incluye en las respuestas JSON de la API (`unset($user['password_hash'])` antes de responder).

---

## RF-06 — Contenido Académico

Gestión de slides para el escenario académico de la cartelera. Tabla: `academic_content`.

### Campos editables

| Campo | Descripción |
|-------|-------------|
| `title` | Título del slide (puede ser cadena vacía, NOT NULL) |
| `description` | Descripción o cuerpo del texto (nullable) |
| `program_type` | Tipo de programa (técnico, tecnólogo, etc.) |
| `program_area` | Sector educativo (clave de `PROGRAM_AREAS`) |
| `media_type` | Tipo de medio adjunto (`image`, `video`, etc.) |
| `media_path` | Nombre del archivo multimedia en `img/uploads/academic/` (nullable) |
| `thumbnail_path` | Miniatura opcional para previsualización (nullable) |
| `duration_seconds` | Duración de este slide en la TV; si es null, usa el default de configuración |
| `is_active` | Si `false`, el slide no aparece en la cartelera (sin borrar) |
| `show_from` | Fecha/hora de inicio de visibilidad (null = sin límite de inicio) |
| `show_until` | Fecha/hora de fin de visibilidad (null = sin caducidad) |
| `start_date` | Fecha de inicio del programa académico informativo (formato `Y-m-d`) |
| `end_date` | Fecha de fin del programa académico informativo (formato `Y-m-d`) |
| `display_order` | Posición en el orden de rotación; se asigna automáticamente |

### Reglas de acceso y orden

- Un usuario con rol `program_head` solo puede ver y editar registros cuyo `program_area` coincida con su propio sector. Al listar, el sistema filtra en la query SQL con `AND (program_area IS NULL OR program_area = ?)`.
- Un usuario con rol `admin` puede operar sobre contenido de todos los sectores sin restricción.
- Los slides en el feed TV se ordenan por `display_order ASC, created_at ASC`.

### Display order

- Al crear un nuevo slide, `display_order` se asigna como `MAX(display_order) + 1` del contenido existente en la tabla, con tope en 999999.
- Al eliminar un slide, el sistema renumera todos los restantes de 1…N usando `ROW_NUMBER() OVER (ORDER BY display_order ASC, created_at ASC NULLS LAST, id ASC)` en una sola query SQL, para mantener la secuencia compacta sin huecos.

### Vigencia temporal (ventana show_from / show_until)

- `show_from` y `show_until` son opcionales. Si ambos son `null`, el slide es visible siempre (mientras `is_active = true`).
- La vigencia mínima al configurar `show_until` es 1 hora desde el momento actual.
- La vigencia máxima es 6 meses desde el momento actual.
- Si se intenta editar un slide cuyo `show_until` ya venció usando el modo "mantener vigencia", el sistema lanza un error explicativo: _"La vigencia ya venció. Elija «Sin límite de tiempo» o defina un nuevo tiempo de publicación"_. No guarda silenciosamente una fecha expirada.
- Si la diferencia entre el `show_until` existente y el nuevo es menor a 2 segundos, se considera "sin cambio" y se omite la validación de mínimo 1 hora (para evitar rechazar guardados que no tocan la fecha).

### Filtro para TV

Para aparecer en el feed `GET /api/tv`, un registro debe cumplir:
1. `is_active = TRUE`
2. `show_from IS NULL OR show_from <= now()`
3. `show_until IS NULL OR show_until >= now()`

---

## RF-07 — Contenido Centro ("Nuestro Centro")

Gestión de slides del centro educativo agrupados por sector. Tabla: `center_content`.

### Sectores disponibles (`PROGRAM_AREAS`)

| Clave | Nombre visible |
|-------|----------------|
| `empresarial` | Empresarial |
| `salud` | Salud |
| `tecnologia` | Tecnología |
| `hoteleria` | Hotelería |
| `turismo` | Turismo |
| `barismo` | Barismo |
| `eventos` | Gestión de Eventos |

### Campos editables

Mismos campos estructurales que Académico, con la diferencia de que el campo de clasificación es `sector` (en lugar de `program_area` + `program_type`): `title`, `description`, `sector`, `media_type`, `media_path`, `thumbnail_path`, `duration_seconds`, `is_active`, `show_from`, `show_until`, `display_order`.

### Estado del módulo en el panel

- El módulo `center` está listado en `PANEL_DISABLED_MODULES = ['center', 'gallery']`. Esto significa que **no aparece en el menú de navegación** del panel y que los endpoints de su API (`/api/center/*`) devuelven HTTP 403 si se intenta acceder directamente.
- A pesar de estar deshabilitado en el panel, el feed público `GET /api/tv` **sí incluye** el contenido activo y vigente de `center_content` en el escenario `center`. Esta separación es intencional: el módulo puede estar en modo "solo lectura desde TV" mientras el panel de edición está desactivado.
- Las mismas reglas de vigencia, display_order y filtro para TV aplican igual que en Académico.
- Los slides en el feed TV se ordenan por `sector, display_order ASC, created_at ASC`.

---

## RF-08 — Contenido General

Gestión de slides de información general, noticias e indicadores. Tabla: `general_content`.

### Categorías disponibles (`CONTENT_CATEGORIES`)

| Clave | Nombre visible |
|-------|----------------|
| `news` | Noticias |
| `event` | Eventos |
| `announcement` | Anuncios |
| `currency` | Divisas |
| `commodity` | Materias Primas |
| `sports` | Deportes |
| `weather` | Clima |
| `institutional` | Institucional |

### Campos editables

| Campo | Descripción |
|-------|-------------|
| `category` | Clave de `CONTENT_CATEGORIES` |
| `title` | Título del slide (puede ser cadena vacía) |
| `description` | Cuerpo o subtítulo (nullable) |
| `source_url` | URL fuente de la noticia o dato (nullable) |
| `image_path` | Archivo de imagen adjunto (nullable) |
| `icon` | Icono decorativo; debe ser clave de `GENERAL_ICONS` (ver más abajo) |
| `duration_seconds` | Duración del slide; null = usa default de configuración |
| `is_active` | Visibilidad del slide |
| `is_external` | `true` si el contenido viene del cron de datos externos |
| `publish_date` | Fecha de publicación (default: fecha de creación) |
| `expire_date` | Fecha de caducidad; `null` = sin caducidad |
| `external_data` | JSON con datos estructurados para slides de indicadores (JSONB en PostgreSQL) |
| `display_order` | Posición en el ciclo de rotación |

### Campo `icon` — Lista de valores válidos (`GENERAL_ICONS`)

Solo se aceptan las siguientes 14 claves. Cualquier otro valor se normaliza a `null`:

`dollar-sign`, `trending-up`, `bitcoin`, `cloud`, `thermometer`, `newspaper`, `calendar`, `megaphone`, `building-2`, `landmark`, `trophy`, `droplets`, `bell`, `info`

### Caducidad (`expire_date`)

El módulo general usa `expire_date` para controlar la vigencia, a diferencia de académico/centro que usan `show_from/show_until`. Un slide con `expire_date` en el pasado no aparece en el feed TV aunque `is_active = true`.

### Contenido externo (`is_external`)

- Los slides con `is_external = true` son creados/actualizados exclusivamente por el cron (`cron_actualizar_externos.php`). Sus datos estructurados se almacenan en `external_data` (JSONB).
- El campo `external_data` se serializa a JSON al guardar. La sanitización XSS estándar convierte `"` en `&quot;`, lo que rompería el JSONB. Para evitar esto, el modelo aplica `html_entity_decode()` antes de parsear el JSON, y si el resultado no es un array válido, descarta el campo.
- `GET /api/general/indicators` devuelve solo los slides externos activos.

### Orden en el feed TV

`display_order ASC, publish_date ASC NULLS LAST, created_at ASC`

---

## RF-09 — Agrupación de Indicadores en el Feed TV

Los slides externos de información general no se sirven individualmente al player. En su lugar, `TvController` los agrupa automáticamente en slides compuestos antes de responder:

### Grupos definidos

| ID (negativo) | Título en TV | Slides que agrupa (por coincidencia en título) |
|---|---|---|
| `-101` | Divisas y criptomonedas | `"Cotización del Dólar"`, `"TRM Colombia"`, `"Criptomonedas"` |
| `-102` | Commodities y clima | `"Café"`, `"Petróleo"`, `"Clima actual"` |

### Comportamiento

- La coincidencia se hace con `stripos($titulo, $needle)`, por lo que busca la cadena sin importar mayúsculas ni texto adicional en el título.
- La duración del slide compuesto es la **suma** de las duraciones de sus slides miembro, con un mínimo de 3 segundos.
- El `updated_at` del slide compuesto es el más reciente entre sus miembros.
- Los slides externos que **no encajan** en ningún grupo se incluyen individualmente al final del escenario general.
- Los slides **no externos** (content manual con `is_external = false`) nunca se agrupan; siempre se muestran individualmente.
- Los slides compuestos usan `layout: 'indicadores'` y el player los renderiza como una tabla de datos con icono, título y valores.
- Los IDs negativos (`-101`, `-102`) son artificiales (no existen en la BD). Son solo identificadores para el player del ciclo de rotación.

---

## RF-10 — Galería de Imágenes

Módulo de slides puramente visuales, tabla `gallery_content`.

- Solo acepta imágenes: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`. No se permiten videos, audio ni documentos en este módulo.
- Campos: `title` (nullable), `image_path`, `duration_seconds`, `is_active`, `show_from`, `show_until`, `display_order`.
- El campo `title` es opcional y puede ser `null`.
- Al igual que `center`, el módulo galería está en `PANEL_DISABLED_MODULES`: no tiene CRUD accesible en el panel ni menú de navegación, y la API retorna 403. El feed TV sí lo incluye.
- Para aparecer en el feed TV un registro debe cumplir: `is_active = TRUE`, `show_from IS NULL OR show_from <= now()`, `show_until IS NULL OR show_until >= now()`, y `image_path IS NOT NULL AND image_path <> ''`.
- Mismas reglas de vigencia, display_order y renumeración al borrar que los demás módulos de contenido.

---

## RF-11 — Slide de Bienvenida Institucional

Slide fijo que siempre encabeza el ciclo de rotación, independientemente del orden configurado de escenarios.

### Campos editables

| Campo | Descripción | Restricción de HTML |
|-------|-------------|---------------------|
| `saludo` | Frase de saludo superior | Solo texto plano (`strip_tags`) |
| `titulo` | Título principal del slide | `strip_tags`; acepta `\n` para saltos de línea |
| `centro` | Nombre del centro educativo | Solo texto plano |
| `regional` | Regional SENA | Solo texto plano |
| `lema` | Frase de lema o slogan | Acepta `<b></b>` para negrilla |
| `hashtag` | Hashtag institucional | Solo texto plano |
| `pie` | Texto del pie del slide | Acepta `<b></b>` para negrilla |
| `duration` | Duración del slide en segundos | Entero; rango [3, 120] |

### Duración

- Mínimo: 3 segundos. Máximo: 120 segundos.
- Si el valor enviado está fuera de rango, el sistema lo ajusta automáticamente al límite más cercano sin retornar error.

### Colores individuales por elemento

Se pueden configurar 11 claves de color independientes:

`bg_color`, `accent_color`, `text_color`, `saludo_color`, `titulo_color`, `centro_color`, `regional_color`, `lema_color`, `hashtag_color`, `pie_color`, `deco_color`

- Todos deben ser hexadecimales en formato `#RRGGBB` exacto (6 dígitos). El sistema valida con regex `/^#[0-9A-Fa-f]{6}$/` y rechaza cualquier valor que no cumpla (HTTP 422).
- Si no se configura un color individual, se usa `accent_color` como fallback para colores de énfasis y `text_color` para colores de texto.

### Manejo de HTML limitado en `lema` y `pie`

Los campos `lema` y `pie` permiten negrilla con `<b>`. El sistema usa un parser propio (no regex) que:
1. Divide el string por tokens `<b>` y `</b>` (case-insensitive).
2. Escapa todo texto entre tokens con `htmlspecialchars`.
3. Solo inserta `<b>` si no hay uno ya abierto (previene anidamiento).
4. Cierra automáticamente cualquier `<b>` que quede abierto al final.
5. Descarta cualquier otra etiqueta HTML.

### Imagen de fondo

- Se puede subir una imagen de fondo a `img/uploads/welcome/`. Formatos aceptados: `jpg`, `jpeg`, `png`, `svg`. Tamaño máximo: 5 MB.
- Al eliminar el fondo, se borra el archivo físico del disco y se vacían las claves `welcome_bg_path` y `welcome_bg_url` en `display_config`.
- Si hay una imagen de fondo subida previamente y se sube una nueva, el archivo antiguo se elimina del disco automáticamente.

### Defaults institucionales

Los valores por defecto están definidos en `DisplayConfig::welcomeDefaults()`:
- Colores: esquema verde SENA (`#39a900` como acento, `#eaf4e1` como fondo).
- Textos: saludo, título, nombre del centro, regional, lema y hashtag institucionales del CSET.

### Acceso

Solo el rol `admin` puede modificar el slide de bienvenida. Todas las operaciones requieren token CSRF válido.

---

## RF-12 — Gestión de Medios (Subida de Archivos)

Los archivos multimedia se suben desde los formularios del panel (para cada módulo de contenido) o desde la biblioteca de medios via `POST /api/media`.

### Validación en dos pasos

1. **Extensión:** se extrae la extensión del nombre original del archivo y se comprueba que esté en la lista blanca correspondiente.
2. **MIME real:** se detecta el tipo MIME del contenido del archivo con `finfo_open(FILEINFO_MIME_TYPE)`. Se compara contra la tabla de MIMEs esperados por extensión. Si no coinciden, el archivo se rechaza.
   - Excepción: videos con MIME `application/octet-stream` se aceptan igualmente (algunos sistemas operativos reportan así archivos `.mp4` legítimos).

### Tipos y límites por categoría general

| Tipo | Extensiones | Tamaño máximo |
|------|-------------|----------------|
| Imagen | `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg` | 10 MB |
| Video | `mp4`, `webm`, `ogg`, `mov` | 500 MB |
| Audio | `mp3`, `wav`, `ogg`, `m4a` | 50 MB |
| Documento | `pdf` | 20 MB |

### Restricciones adicionales por categoría de destino

| Categoría destino | Tipos permitidos | Tamaño máximo |
|-------------------|-----------------|----------------|
| `branding` | Solo imágenes: `jpg`, `jpeg`, `png`, `svg` | 5 MB |
| `welcome` | Solo imágenes: `jpg`, `jpeg`, `png`, `svg` | 5 MB |
| `gallery` | Solo imágenes (todos los tipos de imagen) | 10 MB |
| `academic`, `center`, `general` | Todos los tipos | Según tipo |
| `media` (biblioteca) | Todos los tipos | Según tipo |

### Nombre del archivo en disco

El nombre se genera como: `YYYYMMDD_HHIISS_<8 caracteres hexadecimales aleatorios>.<ext>`

El nombre original del usuario **nunca** se usa en el path físico. Esto evita path traversal y conflictos de nombres.

### Directorio de destino

Los archivos se guardan en `img/uploads/{category}/`. Si el directorio no existe, se crea con permisos `0775`. Si el directorio no tiene permisos de escritura, la operación falla con error descriptivo.

### Permisos del archivo guardado

`chmod($destPath, 0644)` tras mover el archivo temporal de PHP.

### Sanitización de SVG

Los archivos SVG pasan por un proceso de sanitización adicional **después** de moverse al disco:
1. Se carga el contenido con `DOMDocument::loadXML(..., LIBXML_NONET)`. `LIBXML_NONET` previene fetch de recursos externos durante el parseo.
2. Si el archivo no es XML bien formado con elemento raíz `<svg>`, se rechaza y se borra del disco.
3. Se eliminan todos los nodos de los tags: `<script>`, `<foreignObject>`, `<iframe>`, `<embed>`, `<object>`.
4. Se recorren todos los elementos con `DOMXPath` y se eliminan atributos `on*` (event handlers) y `href`/`xlink:href` cuyo valor comience con `javascript:` (case-insensitive).
5. Se serializa el DOM saneado y se sobreescribe el archivo.
6. Si algún paso falla, se borra el archivo del disco y se retorna error.

### Proceso de video

1. El archivo temporal de PHP se mueve primero a un path temporal oculto (`.{nombre}.src`) en el directorio de destino.
2. Se pasa por `VideoTranscoder::toSignageMp4()` que genera el MP4 final.
3. El archivo `.src` se elimina del disco siempre (independientemente de si la transcodificación fue exitosa o falló).
4. Si la transcodificación falla, también se limpia el archivo de destino y se lanza error al cliente.

### Registro en BD

Cada subida exitosa crea un registro en `media_files` con: `filename`, `original_name`, `mime_type`, `file_size`, `file_path`, `category`, `uploaded_by`, `uploaded_at`.

### Eliminación de medios

- Solo el rol `admin` puede eliminar archivos desde la biblioteca.
- La eliminación borra el archivo físico del disco (`unlink`) y elimina la fila en `media_files`.

---

## RF-13 — Transcodificación de Video para Signage

Todo video subido al sistema pasa por transcodificación antes de guardarse definitivamente, para garantizar compatibilidad con LG Signage y Chromium 53.

### Parámetros de transcodificación (ffmpeg)

| Parámetro | Valor |
|-----------|-------|
| Codec de video | `libx264` |
| Perfil H.264 | `baseline` |
| Nivel H.264 | `3.1` |
| Preset de velocidad | `veryfast` |
| CRF (calidad) | `23` |
| Filtro de escala | `min(1920, ancho_original) × min(1080, alto_original)`, preservando relación de aspecto |
| Pixel format | `yuv420p` |
| Codec de audio | `aac` |
| Bitrate de audio | `128k` |
| Canales de audio | `2` (estéreo) |
| Sample rate | `44100 Hz` |
| Movflags | `+faststart` |
| Max muxing queue | `1024` |
| Extensión de salida | Siempre `.mp4` |

### Localización del binario ffmpeg

El sistema busca el ejecutable en este orden:
1. `scripts/bin/ffmpeg` (local al repositorio, para entornos sin ffmpeg en PATH).
2. `command -v ffmpeg` en el PATH del sistema.

Si no lo encuentra en ninguno de los dos, la subida de video falla con mensaje: _"No se pudo optimizar el video para TV: instale ffmpeg en el servidor"_.

### Proceso de transcodificación

1. Se genera un nombre de archivo temporal de salida: `{destino}.tmp.{4hex}.mp4`.
2. Se ejecuta ffmpeg con `exec()`, capturando stdout+stderr.
3. Si el código de salida es distinto de 0, el archivo temporal tiene menos de 64 bytes o no existe, se limpia y se lanza error con las últimas 12 líneas de salida de ffmpeg.
4. El temporal se renombra al destino final con `rename()`.
5. El archivo original (`.src`) se borra siempre.

---

## RF-14 — Detección de Duración de Video

Antes de transcodificar, el sistema determina la duración del video para asignarla como `duration_seconds` del slide.

### Métodos (en orden de preferencia)

1. **ffprobe:** si está disponible (buscado igual que ffmpeg: `scripts/bin/ffprobe` o PATH), se ejecuta con `-show_entries format=duration -of default=noprint_wrappers=1:nokey=1`. Retorna la duración en segundos como float.
2. **Parser manual de átomo `mvhd`:** si no hay ffprobe, se escanea el archivo MP4 recursivamente buscando el box `mvhd` en la jerarquía `moov → trak → mdia → minf → stbl → mvhd`. Soporta:
   - Boxes con tamaño extendido a 64 bits (`boxSize = 1` + 8 bytes de tamaño real).
   - `mvhd` versión 0: timescale y duration en campos de 32 bits.
   - `mvhd` versión 1: timescale en 32 bits, duration en 64 bits.

Si ningún método retorna una duración válida (> 0), la subida falla con error: _"No se pudo leer la duración del video. Use MP4 o instale ffprobe."_

### Conversión a segundos de slide

- Se aplica `ceil()` sobre la duración en segundos flotantes.
- Se ajusta al rango `[MIN_SLIDE_DURATION_SECONDS, MAX_SLIDE_DURATION_SECONDS]` = `[5, 600]`.
- Si la duración supera 600.5 segundos (10 minutos + 0.5 s de margen), se rechaza el video con error antes de transcodificar: _"El video supera el máximo de 10 minutos"_.

---

## RF-15 — Biblioteca de Medios

La biblioteca de medios centraliza la consulta de archivos subidos al sistema.

- El panel lista hasta los **últimos 100 archivos** subidos, ordenados por `uploaded_at DESC LIMIT 100`.
- Cada entrada muestra: `filename`, `original_name`, `mime_type`, `file_size`, `category`, `uploaded_by` (ID) y `uploader_name` (nombre completo via JOIN con `users`).
- **Acceso de lectura:** cualquier usuario con sesión activa puede consultar la biblioteca.
- **Eliminación:** solo el rol `admin` puede eliminar archivos. La eliminación es permanente (archivo en disco + fila en BD).
- Los editores y `program_head` no acceden a la biblioteca directa; suben archivos exclusivamente desde los formularios de cada módulo de contenido.

---

## RF-16 — Feed de la Cartelera (API pública)

El feed es el endpoint central que alimenta al player de la cartelera. Es **público**: no requiere sesión ni CSRF.

### `GET /api/tv` — Feed completo

Devuelve el payload completo de la cartelera en JSON:

```json
{
  "server_ts": 1234567890,
  "server_iso": "2026-10-30T10:00:00-05:00",
  "version": "2.0.0",
  "config": { ... },
  "scenarios": {
    "welcome": [ ... ],
    "academic": [ ... ],
    "center": [ ... ],
    "general": [ ... ],
    "gallery": [ ... ]
  },
  "last_update": 1234567800
}
```

- `config` contiene todos los parámetros de configuración de pantalla (rotación, duraciones, branding, colores, etc.).
- `scenarios` es un objeto con los arrays de slides de cada escenario. El escenario `welcome` siempre aparece como primer elemento del orden.
- `last_update` es la marca de tiempo (Unix epoch) del `MAX(updated_at)` calculado sobre todas las tablas de contenido (`academic_content`, `center_content`, `general_content`, `gallery_content`) y `display_config`. Se calcula con una única query SQL usando `GREATEST(COALESCE(MAX(...), 0), ...)`.

### Boot data (`window.__CSET_BOOT__`)

`index.php` inyecta el mismo payload del feed en la variable global `window.__CSET_BOOT__` del HTML antes de enviar la página. El player lee este objeto al inicializar, eliminando la necesidad de un request HTTP adicional al cargar la cartelera.

### `GET /api/tv/ping` — Heartbeat ligero

Endpoint mínimo para detección de cambios sin cargar el feed completo. Retorna solo:

```json
{
  "server_ts": 1234567890,
  "last_update": 1234567800,
  "tv_enabled": "1"
}
```

### Caché

Ambos endpoints incluyen `Cache-Control: no-store, no-cache, must-revalidate` y `Pragma: no-cache` para prevenir que proxies o el buffer del monitor Signage sirvan contenido desactualizado.

---

## RF-17 — Player de Cartelera (Comportamiento del Cliente)

El player es la aplicación JavaScript que corre en el navegador del monitor Signage y gestiona la rotación de slides.

### Inicialización

Lee el payload desde `window.__CSET_BOOT__` al cargar la página. No hace ningún request HTTP adicional en la carga inicial.

### Ciclo de polling

1. El player hace ping a `GET /api/tv/ping` cada `config.api_poll_seconds` segundos (default: **30 segundos**).
2. Compara el `last_update` recibido con el que tiene en memoria.
3. Si son iguales: no hace nada y espera el siguiente intervalo.
4. Si son distintos: solicita el feed completo `GET /api/tv` inmediatamente ("modo turbo") y actualiza todo el payload en memoria.

### Rotación de slides

- La duración de cada slide se determina así: si el slide tiene `duration_seconds` propio (no null), se usa ese valor; si no, se usa el default del tipo (`tv_academic_duration`, `tv_center_duration`, etc.) de `config`.
- La rotación sigue el orden de `config.scenario_order`. El escenario `welcome` siempre es el primero.
- Al terminar el último slide del último escenario, el ciclo vuelve a empezar por `welcome`.

### Layouts de slide

| Layout | Descripción |
|--------|-------------|
| `bienvenida` | Slide institucional con saludo, título, centro, regional, lema, hashtag, pie y fondo configurable |
| `indicadores` | Tabla de datos externos agrupados (divisas, commodities, clima) con icono y valores clave/valor |
| genérico | Contenido con imagen o video, título y descripción |

### Características opcionales

- Reloj en pantalla: activo si `config.show_clock = true`.
- Widget de clima: activo si `config.show_weather = true`.
- Modo oscuro/claro: según los colores de branding configurados.
- Pantalla completa automática: si `config.tv_fullscreen = '1'`.

### Modo apagado

Si `config.tv_enabled = '0'`, el player deja de rotar slides y muestra únicamente el texto de `config.tv_off_message`. Sigue haciendo polling; cuando `tv_enabled` vuelva a `'1'`, retoma la rotación.

### Compatibilidad ES5

El código JavaScript del player está escrito en ES5 estricto. Restricciones que aplica todo el equipo de desarrollo al modificar el player:

- No arrow functions (`() =>`). Usar `function()` siempre.
- No `const` ni `let`. Usar `var`.
- No template literals (\`...\`). Usar concatenación con `+`.
- No módulos ES6 (`import`/`export`).
- No `async`/`await`.
- No Promises nativas.
- No spread operator (`...`).
- No destructuring (`const {a, b} = obj`).

---

## RF-18 — Configuración de la Cartelera

Panel de configuración accesible exclusivamente para el rol `admin`. Todas las claves se guardan en la tabla `display_config` como pares `config_key / config_value` (ambos strings). Las operaciones son upsert: si la clave existe se actualiza, si no existe se inserta.

### Parámetros configurables

| Clave en BD | Default | Descripción |
|-------------|---------|-------------|
| `tv_rotation_seconds` | `20` | Duración base en segundos de cada slide (puede ser sobreescrita por slide) |
| `api_poll_seconds` | `30` | Frecuencia de polling del player en segundos |
| `tv_scenario_order` | `academic,center,general` | Orden de escenarios separados por coma |
| `tv_academic_duration` | `15` | Duración default de slides académicos (segundos) |
| `tv_center_duration` | `15` | Duración default de slides de centro (segundos) |
| `tv_general_duration` | `10` | Duración default de slides generales (segundos) |
| `tv_gallery_duration` | `15` | Duración default de slides de galería (segundos) |
| `tv_show_clock` | `1` | Mostrar reloj en cartelera (`1`/`0`) |
| `tv_show_weather` | `1` | Mostrar widget de clima (`1`/`0`) |
| `tv_enabled` | `1` | Cartelera encendida (`1`) o apagada (`0`) |
| `tv_fullscreen` | `1` | Activar pantalla completa automática (`1`/`0`) |
| `tv_off_message` | `Cartelera apagada` | Texto mostrado cuando la cartelera está apagada |
| `tv_color_header` | `` (vacío) | Color de cabecera; vacío = usa `brand_primary_color` |
| `tv_color_badge` | `` (vacío) | Color de badges/etiquetas; vacío = usa primario |
| `tv_color_icon` | `` (vacío) | Color de iconos; vacío = usa primario |
| `tv_color_live` | `` (vacío) | Color del indicador "en vivo"; vacío = usa primario |
| `tv_color_progress` | `` (vacío) | Color de la barra de progreso; vacío = usa primario |

La caché de request (`DisplayConfig::$cache`) se invalida automáticamente en cada `set()`, de modo que lecturas posteriores en el mismo request reflejan el valor actualizado.

---

## RF-19 — Branding y Logo

Configuración de identidad visual de la cartelera. Solo accesible para `admin`.

### Parámetros de colores

| Clave en BD | Default | Descripción |
|-------------|---------|-------------|
| `brand_primary_color` | `#0b3d91` | Color primario modo claro |
| `brand_secondary_color` | `#1e5ba8` | Color secundario modo claro |
| `brand_accent_color` | `#e6b800` | Color de acento modo claro |
| `brand_bg_color` | `#f1f5f9` | Color de fondo modo claro |
| `brand_text_color` | `#1e293b` | Color de texto modo claro |
| `brand_dark_primary_color` | `#0b3d91` | Color primario modo oscuro |
| `brand_dark_secondary_color` | `#1e5ba8` | Color secundario modo oscuro |
| `brand_dark_accent_color` | `#e6b800` | Color de acento modo oscuro |
| `brand_dark_bg_color` | `#0a0e27` | Color de fondo modo oscuro |
| `brand_dark_text_color` | `#ffffff` | Color de texto modo oscuro |
| `brand_font_family` | `'Segoe UI', Roboto, sans-serif` | Familia tipográfica |

### Logo

- Formatos aceptados: `jpg`, `jpeg`, `png`, `svg`. Tamaño máximo: 5 MB.
- Se guarda en `img/uploads/branding/`.
- La clave `brand_logo_path` guarda solo el nombre del archivo (no la ruta completa).
- La clave `brand_logo_url` guarda la URL relativa construida como `APP_BASE + '/img/uploads/branding/' + filename`, sin quedar pegada a un esquema (HTTP/HTTPS) ni a un host concreto.
- Al subir un nuevo logo, el archivo anterior se elimina automáticamente del disco.
- El tamaño de logo en la UI es configurable: `brand_logo_size` acepta `small`, `medium`, `large`.

### Nombre y subtítulo del sitio

| Clave | Default | Descripción |
|-------|---------|-------------|
| `site_name` | `IES` | Nombre corto del centro |
| `branding_title` | `IES - Cartelera Digital` | Título principal |
| `branding_subtitle` | `Formación para el trabajo` | Subtítulo |

### Exposición al player

El branding completo se incluye en el objeto `config` del feed `GET /api/tv`. También se expone por separado en `GET /api/branding` (público, sin autenticación), para que el player pueda actualizar colores y logo sin cargar el feed completo.

---

## RF-20 — Indicadores Externos (Cron)

Script de ejecución CLI que actualiza los slides externos de información general con datos de APIs públicas.

**Archivo:** `src/cron_actualizar_externos.php`  
**Tiempo máximo de ejecución:** 300 segundos (`set_time_limit(300)`)  
**Ejecución típica:** `0 6 * * *` (06:00 hora Bogotá, una vez al día)

### Fuentes de datos

#### Fuente 1 — Divisas USD
- **URL:** `https://open.er-api.com/v6/latest/USD`
- **Timeout:** 15 segundos
- **Datos:** tasas de cambio USD → `COP`, `EUR`, `GBP`, `BRL`, `MXN`, `PEN`, `ARS`, `CLP`, `CAD`, `JPY`
- **Slides generados:**
  - `"Cotización del Dólar (USD)"`: objeto con las 10 monedas y sus tasas.
  - `"TRM Colombia (Peso Colombiano)"`: objeto con `COP por USD` formateado, fecha y fuente.

#### Fuente 2 — Criptomonedas
- **URL:** `https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum&vs_currencies=usd&include_24hr_change=true`
- **Timeout:** 15 segundos
- **Datos:** precio USD y variación 24h de Bitcoin y Ethereum
- **Slide generado:** `"Criptomonedas"`

#### Fuente 3 — Café (Arábica)
- **URL:** `https://query1.finance.yahoo.com/v8/finance/chart/KC=F?interval=1d&range=1d` (Yahoo Finance, contrato ICE KC=F)
- **Timeout:** 20 segundos
- **Datos:** precio en centavos de dólar por libra (se divide entre 100 para obtener USD/lb); variación porcentual vs cierre previo
- **Slide generado:** `"Café (Arábica) - Precio Internacional"`

#### Fuente 4 — Petróleo (WTI y Brent)
- **URL:** Yahoo Finance `CL=F` (WTI) y `BZ=F` (Brent)
- **Timeout:** 20 segundos por símbolo
- **Datos:** precio USD/barril WTI, precio USD/barril Brent, OPEP (promedio), variación promedio
- **Slide generado:** `"Petróleo - Precios WTI y Brent"`

#### Fuente 5 — Clima
- **URL:** `https://api.open-meteo.com/v1/forecast?latitude=7.1193&longitude=-73.1227&current_weather=true`
- **Ciudad:** Bucaramanga, Santander (lat 7.1193, lon -73.1227)
- **Timeout:** 15 segundos
- **Datos:** temperatura (°C), velocidad del viento (km/h), código de condición meteorológica WMO traducido al español
- **Condiciones climáticas:** mapeadas desde código WMO a texto: 0 = Despejado, 1 = Mayormente despejado, 2 = Parcialmente nublado, 3 = Nublado, 45/48 = Niebla, 51-55 = Llovizna, 61-65 = Lluvia, 71-75 = Nieve, 80-82 = Chubascos, 95 = Tormenta eléctrica.
- **Slide generado:** `"Clima actual - Bucaramanga"`

### Tarea de limpieza adicional

Al final de cada ejecución, el cron limpia `user_sessions` expiradas con `DELETE FROM user_sessions WHERE expires_at < now()`.

### Mecanismo de upsert y aliases legacy

- `GeneralContent::upsertExternal()` hace `SELECT` por `category + title`. Si el registro existe, lo actualiza (`UPDATE`); si no, lo inserta (`INSERT`).
- Existen aliases de títulos legacy (versiones sin tilde, sin paréntesis o con nombres antiguos) que se desactivan automáticamente (`is_active = FALSE`) cuando se actualiza el título canónico, para evitar duplicados en la TV.
- Títulos canónicos y sus aliases:
  - `"Café (Arábica) - Precio Internacional"` → aliases: `"Cafe (Arabica) - Precio Internacional"`, `"Café - Precio Internacional"`, `"Cafe - Precio Internacional"`
  - `"Petróleo - Precios WTI y Brent"` → alias: `"Petroleo - Precios WTI y Brent"`
  - `"Cotización del Dólar (USD)"` → aliases: `"Cotizacion del Dolar (USD)"`, etc.

### Resultado y logging

- Cada fuente imprime en consola el resultado: `OK`, `Sin cambios` o `Error: <mensaje>`.
- Al finalizar, el resumen completo (timestamp, OK count, FAIL count, detalle por fuente) se **escribe en** `logs/external-refresh.log` con `FILE_APPEND | LOCK_EX`.
- El cron es seguro de ejecutar manualmente en consola para diagnóstico: `php src/cron_actualizar_externos.php`.

---

## RF-21 — Auditoría y Logs

### Registro de actividad en BD

Todas las acciones relevantes se registran en la tabla `activity_log` mediante la función PostgreSQL `log_activity(user_id, action, table_name, record_id, details_json, ip_address)`.

Las acciones registradas incluyen:

| Acción | Descripción |
|--------|-------------|
| `login_success` | Login exitoso |
| `login_failed` | Intento de login con credenciales incorrectas |
| `logout` | Cierre de sesión |
| `create_user` | Creación de usuario |
| `update_user` | Edición de usuario |
| `toggle_user` | Activación/desactivación de usuario |
| `delete_user` | Eliminación de usuario |
| `upload_logo` | Subida de logo de branding |
| `upload_welcome_bg` | Subida de imagen de fondo del slide de bienvenida |
| `delete_welcome_bg` | Eliminación de imagen de fondo |
| `update_config` | Cambio en configuración de pantalla |
| `update_welcome` | Cambio en el slide de bienvenida |
| `create_*` / `update_*` / `delete_*` | CRUD de contenido (cada módulo registra su acción) |

### Fallback a archivo

Si la BD no está disponible durante la escritura de un log, el sistema escribe en `logs/activity-YYYY-MM-DD.log` en formato JSONL (un objeto JSON por línea). El request continúa sin interrumpirse.

### Archivos de log

| Archivo | Contenido | Formato |
|---------|-----------|---------|
| `logs/activity-YYYY-MM-DD.log` | Acciones de auditoría (fallback de BD) | JSONL |
| `logs/errors-YYYY-MM-DD.log` | Errores críticos de la aplicación | JSONL |
| `logs/external-refresh.log` | Resultados de cada ejecución del cron | Texto plano (append) |
| `logs/php-errors.log` | Errores PHP en producción | Formato nativo de PHP |

### Dashboard

El endpoint `GET /api/dashboard` retorna las últimas **15 filas** de `activity_log` con JOIN a `users` para incluir `username` y `full_name` del actor.

### Retención de intentos de login

Los registros de `login_attempts` se retienen 90 días. La purga se ejecuta automáticamente en cada inserción, sin necesidad de cron externo.

---

## RF-22 — API REST — Rutas Completas

| Método | Ruta | Descripción | Auth | Rol mínimo |
|--------|------|-------------|------|------------|
| `GET` | `/api/auth/csrf` | Obtiene token CSRF de sesión | No | — |
| `POST` | `/api/auth/login` | Autenticación (rate-limited por IP) | No | — |
| `POST` | `/api/auth/logout` | Cierre de sesión | Sesión | Cualquiera |
| `GET` | `/api/auth/me` | Datos del usuario en sesión | Sesión | Cualquiera |
| `GET` | `/api/tv` | Feed completo de cartelera | No | — |
| `GET` | `/api/tv/ping` | Heartbeat (last_update + tv_enabled) | No | — |
| `GET` | `/api/branding` | Branding e identidad visual | No | — |
| `GET` | `/api/meta` | Metadatos del sistema | No | — |
| `GET` | `/api/dashboard` | Estadísticas y actividad reciente | Sesión | Cualquiera |
| `GET` | `/api/academic` | Listar slides académicos | Sesión | Según área |
| `GET` | `/api/academic/{id}` | Obtener slide académico | Sesión | Según área |
| `POST` | `/api/academic` | Crear slide académico | Sesión + CSRF | `admin` / `program_head` |
| `PUT`/`PATCH` | `/api/academic/{id}` | Editar slide académico | Sesión + CSRF | `admin` / `program_head` |
| `DELETE` | `/api/academic/{id}` | Eliminar slide académico | Sesión + CSRF | `admin` / `program_head` |
| `PATCH` | `/api/academic/{id}/toggle` | Activar/desactivar slide | Sesión + CSRF | `admin` / `program_head` |
| `GET` | `/api/general` | Listar slides generales | Sesión | Cualquiera |
| `GET` | `/api/general/{id}` | Obtener slide general | Sesión | Cualquiera |
| `POST` | `/api/general` | Crear slide general | Sesión + CSRF | `admin` / `editor` |
| `PUT`/`PATCH` | `/api/general/{id}` | Editar slide general | Sesión + CSRF | `admin` / `editor` |
| `DELETE` | `/api/general/{id}` | Eliminar slide general | Sesión + CSRF | `admin` / `editor` |
| `PATCH` | `/api/general/{id}/toggle` | Activar/desactivar | Sesión + CSRF | `admin` / `editor` |
| `GET` | `/api/general/indicators` | Solo indicadores externos activos | Sesión | Cualquiera |
| `GET`/`POST`/`PUT`/`DELETE` | `/api/center[/{id}[/toggle]]` | CRUD centro | Sesión + CSRF | `admin` (403 si módulo deshabilitado) |
| `GET`/`POST`/`PUT`/`DELETE` | `/api/gallery[/{id}[/toggle]]` | CRUD galería | Sesión + CSRF | `admin` (403 si módulo deshabilitado) |
| `GET` | `/api/media` | Listar biblioteca de medios (últimos 100) | Sesión | Cualquiera |
| `POST` | `/api/media` | Subir archivo a biblioteca | Sesión + CSRF | Cualquiera |
| `DELETE` | `/api/media/{id}` | Eliminar archivo de biblioteca | Sesión + CSRF | `admin` |
| `GET` | `/api/users` | Listar usuarios | Sesión | `admin` |
| `GET` | `/api/users/{id}` | Obtener usuario | Sesión | `admin` |
| `POST` | `/api/users` | Crear usuario | Sesión + CSRF | `admin` |
| `PUT`/`PATCH` | `/api/users/{id}` | Editar usuario | Sesión + CSRF | `admin` |
| `PATCH` | `/api/users/{id}/toggle` | Activar/desactivar usuario | Sesión + CSRF | `admin` |
| `DELETE` | `/api/users/{id}` | Eliminar usuario | Sesión + CSRF | `admin` |
| `GET` | `/api/config` | Obtener configuración | Sesión | `admin` |
| `PUT`/`PATCH` | `/api/config` | Actualizar configuración | Sesión + CSRF | `admin` |
| `POST` | `/api/config/logo` | Subir logo | Sesión + CSRF | `admin` |
| `DELETE` | `/api/config/logo` | Eliminar logo | Sesión + CSRF | `admin` |
| `GET` | `/api/welcome` | Obtener slide de bienvenida | Sesión | `admin` |
| `PUT`/`PATCH` | `/api/welcome` | Actualizar slide de bienvenida | Sesión + CSRF | `admin` |
| `POST` | `/api/welcome/background` | Subir imagen de fondo | Sesión + CSRF | `admin` |
| `DELETE` | `/api/welcome/background` | Eliminar imagen de fondo | Sesión + CSRF | `admin` |

### Convenciones de respuesta

- Todas las respuestas son JSON con `Content-Type: application/json; charset=utf-8`.
- Respuestas exitosas: `{"ok": true, "item": {...}}` o `{"ok": true, "items": [...]}`.
- Respuestas de error: `{"ok": false, "error": "mensaje descriptivo"}`.
- Código HTTP 404 en rutas de API no encontradas (JSON); código 404 HTML en rutas de panel/públicas no encontradas.
- Las mutaciones (POST, PUT, PATCH, DELETE) requieren token CSRF en header `X-CSRF-Token` o campo POST `_csrf`.

---

## RF-23 — Redirecciones y Enrutamiento

- Las rutas legacy `/cset/*` reciben redirect HTTP **301** a `/tv/*`. Ejemplo: `/cset/tv/` → `/tv/`.
- La base path de la aplicación es `/tv` por defecto, configurable con `APP_BASE` en `.env`.
- El único `.htaccess` está en la raíz del repositorio; no existen `.htaccess` en subcarpetas.
- `index.php` en la raíz del repositorio es el punto de entrada de la cartelera de producción.
- Las URLs del panel siguen el patrón `/tv/admin/...`.
- La API está bajo `/tv/api/...`.
- El `ApiDispatcher` extrae el recurso del `PATH_INFO` o del `REQUEST_URI` usando regex `#/api(?:/index\.php)?(.*)$#`.
- Las rutas no encontradas en la API responden con JSON 404; las rutas no encontradas fuera de la API responden con HTML 404.

---

## RF-24 — Dashboard de Administración

- Accesible para cualquier usuario con sesión activa (no exclusivo de `admin`).
- Endpoint: `GET /api/dashboard`.
- **Estadísticas:** se consultan de la vista PostgreSQL `v_dashboard_stats`. El contenido de esta vista está definido en el schema de BD.
- **Actividad reciente:** últimas **15 entradas** de `activity_log` con JOIN a `users` para incluir `username` y `full_name` del usuario que realizó la acción.
- No hay operaciones de escritura en este módulo; es solo lectura.

---

## RF-25 — Herramientas de Mantenimiento CLI

Herramientas de línea de comandos para administración del sistema. Se ejecutan con `php <herramienta>.php` desde consola del servidor y **no son accesibles vía HTTP** (están dentro de `src/`, bloqueado por Apache).

### `herramienta_reset_admin.php`

Restablece la contraseña del usuario `admin` sin necesidad de acceso al panel. Útil cuando el administrador olvidó sus credenciales o la cuenta quedó bloqueada. Genera o recibe una nueva contraseña y actualiza el `password_hash` en la tabla `users`.

### `herramienta_reencode_videos.php`

Re-transcodifica videos existentes en `img/uploads/` al formato Signage actualizado (H.264 Baseline MP4). Útil tras modificaciones en los parámetros de transcodificación (CRF, escala, etc.) cuando se quiere unificar el formato de todos los videos existentes sin necesidad de subirlos nuevamente.

### `herramienta_renumerar_orden.php`

Compacta y renumera el campo `display_order` de 1…N en las cuatro tablas de contenido (`academic_content`, `center_content`, `general_content`, `gallery_content`). Disponible con opción `--chronological` para ordenar por `created_at` en lugar de `display_order` actual (útil como migración one-shot tras importar datos legacy). Usa `ContentDisplayOrder::renumberAllTables()` internamente.

---

## RF-26 — Filtros de Búsqueda en el Panel de Contenido Académico

El listado de contenido académico soporta cuatro filtros combinables que se aplican en memoria sobre el resultado de la consulta a BD:

| Parámetro GET | Tipo | Comportamiento |
|---------------|------|----------------|
| `search` | string | Filtra por `title` usando `stripos` (insensible a mayúsculas/minúsculas y acentos). Si el término no aparece en el título, el item se excluye. |
| `area` | string | Filtra por `program_area` exacto (comparación estricta `===`). Vacío = sin filtro. |
| `type` | string | Filtra por `program_type` exacto. Vacío = sin filtro. |
| `active` | `'1'`/`'0'` | `'1'` = solo activos (`is_active = true`); `'0'` = solo inactivos; cualquier otro valor = sin filtro. |

Los filtros se aplican secuencialmente con `array_filter`. El endpoint retorna el array resultante con índices reindexados (`array_values`).

Adicionalmente, el listado visible para un `program_head` en el panel se limita a los slides donde `created_by === session.user_id`. Un `program_head` **no ve** el contenido de su mismo sector creado por otro usuario. Solo el `admin` ve todos los items independientemente de quién los creó (`cset_academic_list_for_user()`).

---

## RF-27 — Filtros de Búsqueda en el Panel de Contenido General

El listado de contenido general soporta dos filtros combinables aplicados en memoria:

| Parámetro GET | Tipo | Comportamiento |
|---------------|------|----------------|
| `category` / `cat` | string | Filtra por `category` exacto (acepta cualquiera de los dos nombres de parámetro). |
| `search` | string | Filtra por `title` con `stripos`. |

El módulo general es **exclusivo del rol `admin`** para todas las operaciones (listar, crear, editar, eliminar, activar/desactivar). El rol `editor` no tiene acceso a este módulo, a pesar de que en otras partes del sistema se menciona como gestor de contenido general. Esta restricción fue reforzada como hallazgo de seguridad (AUDITORIA_SEGURIDAD_CSET.md C6) y está implementada en `GeneralController::requireContentRole()`:

```php
private function requireContentRole(): void {
    if (!Session::hasRole('admin')) {
        $this->fail('Sin permisos', 403);
    }
}
```

---

## RF-28 — Contenido General: Gestión Manual de Indicadores Externos

Además del CRUD estándar, el endpoint `GET|PUT /api/general/indicators` permite gestionar los indicadores externos directamente:

**GET `/api/general/indicators`** — lista todos los slides con `is_external = TRUE`, sin filtro de caducidad ni visibilidad. Retorna: `id`, `category`, `title`, `description`, `external_data` (decodificado como objeto JSON, no como string), `source_url`. Requiere sesión y rol `admin`.

**PUT `/api/general/indicators`** — actualiza indicadores en lote. El body debe contener un array `indicators` donde cada elemento tiene:
- `category`: categoría del indicador
- `title`: título del indicador
- `data` o `external_data`: objeto con los valores a mostrar
- `source_url` o `source`: URL de la fuente (opcional)

El sistema itera el array y llama `GeneralContent::upsertExternal()` por cada elemento que tenga `category`, `title` y datos no vacíos. Retorna el conteo de registros actualizados. Permite actualizar indicadores externos desde el panel sin esperar la ejecución del cron.

---

## RF-29 — Eliminación de Usuario: Detach de Referencias en BD

Cuando se elimina un usuario del sistema, todas sus referencias en otras tablas se anulan con `NULL` antes de borrar la fila en `users`. Esto se hace dentro de una transacción para garantizar atomicidad:

**Tablas y campos afectados:**

| Tabla | Campo anulado |
|-------|---------------|
| `academic_content` | `created_by = NULL` |
| `center_content` | `created_by = NULL` |
| `general_content` | `created_by = NULL` |
| `gallery_content` | `created_by = NULL` |
| `media_files` | `uploaded_by = NULL` |
| `display_config` | `updated_by = NULL` |
| `activity_log` | `user_id = NULL` |

El contenido creado por el usuario eliminado **no se borra**: permanece en la cartelera con `created_by = NULL`. Solo se pierde la trazabilidad del autor. Las entradas en `activity_log` del usuario también se conservan pero quedan con `user_id = NULL`.

Si la transacción falla en cualquier punto (error en alguna actualización o en el DELETE final), se hace rollback y se lanza `RuntimeException`. El usuario no queda en un estado parcialmente eliminado.

---

## RF-30 — Validaciones de Integridad al Crear/Editar Usuarios

Además de las validaciones de contraseña y username ya documentadas, la función `cset_validate_user_fields()` impone las siguientes reglas antes de persistir cualquier usuario:

- El `role` debe ser una clave de `PANEL_ASSIGNABLE_ROLES`. Si se envía un rol que no está en esa lista (por ejemplo, un rol deprecado o inventado), la operación falla con: _"Rol no permitido."_
- Un usuario con rol `program_head` **debe** tener `program_area` asignada (no vacía). Si se intenta crear o editar un `program_head` sin área, la operación falla con: _"Los jefes de programa deben tener un área asignada."_
- Un usuario con rol `admin` **no puede** tener `program_area`. Si se envía un área para un admin, se ignora y se guarda como `null`.

Al editar un usuario, si el editor es `program_head` intentando cambiar su propio `program_area` en el payload, el sistema fuerza el área de la sesión activa (no la del payload). Los `program_head` no pueden auto-asignarse otro sector.

---

## RF-31 — Página de Inicio Post-Login Según Rol

Tras un login exitoso, el sistema redirige al usuario al destino correspondiente según su rol, definido en `cset_panel_home_url()`:

| Rol | Destino |
|-----|---------|
| `admin` | `/tv/admin/dashboard.php` |
| `program_head` | `/tv/admin/academic/index.php` |
| `editor` | `/tv/admin/academic/index.php` |
| Cualquier otro | `/tv/admin/academic/index.php` |

El admin es el único que va al dashboard; todos los demás roles aterrizan directamente en el listado de contenido académico, que es la sección que más usan.

---

## RF-32 — Tema Visual del Panel (Claro/Oscuro)

El panel de administración soporta tema claro y oscuro. La preferencia se guarda en la **cookie** `cset_theme` en el navegador del usuario (no en la BD ni en la sesión del servidor).

- `panel_theme()` lee `$_COOKIE['cset_theme']`, lo convierte a minúsculas y retorna `'dark'` si el valor es exactamente `'dark'`; cualquier otro valor (incluido ausente) retorna `'light'`.
- El cambio de tema se aplica en el cliente via JavaScript sin recarga de página; solo actualiza la cookie.
- El tema no afecta la cartelera de producción (player): ese usa los colores de `brand_*` / `brand_dark_*` de `display_config`.

---

## RF-33 — Detección de `post_max_size` Excedido en Subidas Multipart

Cuando un formulario multipart (subida de archivo) supera el límite `post_max_size` de PHP, el servidor descarta silenciosamente todo el body: `$_POST` y `$_FILES` quedan vacíos aunque el request llegó. Sin detección explícita, el controlador retornaría un error genérico o procesaría un form vacío.

El método `BaseController::assertMultipartIntact()` detecta este caso y retorna HTTP **413** con mensaje descriptivo:

1. Verifica si el `Content-Type` es `multipart/form-data`.
2. Verifica que `Content-Length > 0`.
3. Si `$_POST` y `$_FILES` están ambos vacíos a pesar de tener un body declarado, concluye que el límite fue superado.
4. Retorna error: _"La subida supera el límite del servidor (post_max_size={valor}). Usa un video más liviano o sube el límite PHP."_

Este check se ejecuta en todos los endpoints de upload antes de procesar cualquier campo.

---

## RF-34 — Endpoint de Metadatos del Sistema (`/api/meta`)

`GET /api/meta` es un endpoint público (sin autenticación) que expone los catálogos y constantes configuradas del sistema:

| Campo | Contenido |
|-------|-----------|
| `program_areas` | Objeto con todas las claves y etiquetas de `PROGRAM_AREAS` |
| `content_categories` | Objeto con todas las claves y etiquetas de `CONTENT_CATEGORIES` |
| `roles` | Roles asignables (resultado de `cset_assignable_roles()`) |
| `app_name` | Nombre de la aplicación (`APP_NAME = 'CSET-TV Cartelera Digital'`) |
| `app_base` | Base path de la aplicación (`APP_BASE`, por defecto `/tv`) |

Lo consume el frontend del panel para poblar selectores de área, categoría y rol sin hardcodear los valores en el JavaScript del cliente.

---

## RF-35 — Resolución del Área de Programa en Contenido Académico

Al crear o editar contenido académico, el área de programa (`program_area`) se resuelve con la siguiente lógica de precedencia:

1. **Si el usuario es `program_head`** (y no `admin`): se usa **siempre** el área de la sesión activa (`Session::userProgramArea()`), ignorando completamente cualquier valor enviado en el payload. Un `program_head` no puede asignar su contenido a un sector diferente al suyo.
2. **Si el usuario es `admin`**: se usa el `program_area` del payload si viene con valor no vacío. Si el payload no lo incluye, se mantiene el `program_area` del registro existente (en edición) o se deja `null` (en creación).
3. **Si el área resultante no está vacía**: se valida con `Auth::canEditArea($area)` antes de persistir. Si el usuario no tiene permisos sobre esa área, la operación falla con HTTP 403.

Esta lógica está implementada en `AcademicController::resolveProgramArea()` y aplica tanto al crear como al editar.

---

## RF-36 — Manejo de Duración en Slides Académicos con Video

Cuando se sube un nuevo archivo de video al crear/editar un slide académico, la duración del slide se establece automáticamente a la duración real del video:

- La duración del video se detecta con `MediaController::upload()` que internamente usa `VideoDuration::probe()`.
- El resultado se clampea al rango `[MIN_SLIDE_DURATION_SECONDS, MAX_SLIDE_DURATION_SECONDS]` = `[5, 600]`.
- Si el upload incluye un `duration_seconds` en el payload, se ignora y se usa la del video.

Cuando se edita un slide que **ya tiene** video pero **no se sube uno nuevo**:
- El campo `duration_seconds` del payload **no se modifica** ni se toca. Se mantiene la duración ya almacenada en BD.
- Esto previene que una edición del título o descripción reinicie accidentalmente la duración del video.

Para slides de tipo imagen o texto (no video), `duration_seconds` del payload se acepta y se clampea al rango `[5, 600]`.

---

## RF-37 — Funciones Helper del Panel (Navegación y UI)

El sistema define un conjunto de funciones helper globales en `src/helpers.php` que dan soporte a las vistas PHP del panel:

### Navegación activa

- `cset_nav_key()`: determina la sección activa del panel analizando `REQUEST_URI` y comparando con rutas conocidas (`/admin/dashboard`, `/admin/academic`, `/admin/general`, etc.). Retorna una clave como `'academic'`, `'users'`, `'config'`, etc.
- `cset_nav_active(string $key)`: retorna la clase CSS `'bg-white/15 font-semibold'` si la clave coincide con la sección activa, o cadena vacía si no.
- `nav_active(string $needle)`: variante simple que busca una cadena en la URI completa; retorna `'is-active'` o `''`.

### URLs del panel

- `cset_url(string $path)`: construye URLs relativas al panel anteponiéndoles `APP_BASE`. Ejemplo: `cset_url('admin/users')` → `/tv/admin/users`. Si `$path` está vacío, retorna la URL base con barra final.
- `redirect(string $path)`: redirige con `Location:` header y `exit`. Si el path no empieza por `http` ni `/`, se antepone `APP_BASE`.

### Escape y sanitización en vistas

- `e(?string $value)`: atajo de `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` para usar en templates PHP. `ENT_SUBSTITUTE` reemplaza caracteres inválidos en lugar de fallar.

### Mensajes flash

- `flash_set(string $type, string $message)`: guarda un mensaje de un solo uso en sesión con tipo (`'success'`, `'error'`, `'info'`).
- `flash_get()`: lee y borra el mensaje flash. Retorna `['type' => ..., 'message' => ...]` o `null` si no hay mensaje. El tipo tiene default `'info'` si no se definió.

### Roles y etiquetas

- `role_label(?string $role)`: convierte claves de rol a nombres legibles: `admin` → `'Administrador del Sistema'`, `program_head` → `'Jefe de programa'`, `editor` → `'Editor'`, `viewer` → `'Solo lectura'`.
- `cset_user_role_options(?string $currentRole)`: retorna el mapa de roles para formularios. Si el usuario tiene un rol que no está en `PANEL_ASSIGNABLE_ROLES` (deprecado), lo incluye con sufijo `' (deprecado)'` para que el formulario pueda mostrarlo pero no asignarlo a otros.

### Branding en el panel

- `cset_branding()`: llama `DisplayConfig::getAllForTv()` dentro de un try/catch. Si la BD no está disponible, retorna `[]` sin lanzar excepción. El panel usa este resultado para los colores y logo del chrome de administración (barra de navegación, cabecera).

### Formato de fechas en actividad

- `format_activity_when(?string $iso)`: convierte un string ISO-8601 al formato `d/m/Y H:i` en zona horaria `America/Bogota`. Si el string es nulo o no parseable, retorna el string original o vacío.
- `cset_format_activity_at($value)`: wrapper que normaliza `null` a `null` y llama `format_activity_when()`.

---

## RF-38 — Iconos SVG del Panel

El sistema genera todos los iconos del panel como SVG inline via la función `icon(string $name)` definida en `src/helpers.php`. Esto evita dependencias de fuentes de iconos externas (Font Awesome, etc.) y garantiza que los iconos funcionen sin conexión a internet.

Los iconos disponibles para el panel son:

| Nombre | Uso en el panel |
|--------|-----------------|
| `home` | Dashboard |
| `spark` | Inicio / resumen |
| `academic` | Sección académica |
| `building` | Centro / sectores |
| `news` | Contenido general |
| `images` | Galería |
| `folder` | Medios |
| `users` | Gestión de usuarios |
| `settings` | Configuración |
| `tv` | Preview de cartelera |
| `moon` | Tema oscuro |
| `sun` | Tema claro |
| `logout` | Cerrar sesión |
| `plus` | Agregar / crear nuevo |

El SVG generado incluye: `class="icon"`, `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`, `stroke-width="2"`, `stroke-linecap="round"`, `stroke-linejoin="round"`, `aria-hidden="true"`. El atributo `aria-hidden="true"` declara los iconos como decorativos para lectores de pantalla.

Los iconos del panel son distintos de los iconos de slides de información general (`GENERAL_ICONS`, RF-08), que son referencias de texto usadas en el frontend de la cartelera.
