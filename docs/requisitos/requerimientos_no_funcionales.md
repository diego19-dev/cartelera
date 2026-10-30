# Requerimientos No Funcionales — CSET-TV Cartelera Digital

**Sistema:** CSET-TV Cartelera Digital  
**Versión:** 2.0.0  
**Fecha de relevamiento:** Octubre 2026  
**Relevado desde:** Código fuente del repositorio (`src/`, `scripts/`, `.htaccess`, `index.php`)

---

## Descripción del documento

Este documento recoge los requerimientos no funcionales del sistema CSET-TV: restricciones de seguridad, compatibilidad de hardware y software, comportamiento bajo carga y fallo, stack tecnológico requerido, configuración de despliegue, mantenibilidad e integridad de datos. Todos los valores numéricos y configuraciones provienen directamente del código fuente relevado.

---

## Tabla de contenidos

- [RNF-01 — Seguridad: Gestión de Contraseñas](#rnf-01--seguridad-gestión-de-contraseñas)
- [RNF-02 — Seguridad: Protección CSRF](#rnf-02--seguridad-protección-csrf)
- [RNF-03 — Seguridad: Rate Limiting y Bloqueo de Login](#rnf-03--seguridad-rate-limiting-y-bloqueo-de-login)
- [RNF-04 — Seguridad: Sanitización y Prevención de XSS](#rnf-04--seguridad-sanitización-y-prevención-de-xss)
- [RNF-05 — Seguridad: Validación de Uploads y Archivos](#rnf-05--seguridad-validación-de-uploads-y-archivos)
- [RNF-06 — Seguridad: Headers HTTP](#rnf-06--seguridad-headers-http)
- [RNF-07 — Compatibilidad con LG Signage / Chromium 53](#rnf-07--compatibilidad-con-lg-signage--chromium-53)
- [RNF-08 — Rendimiento: Polling y Actualización en Tiempo Real](#rnf-08--rendimiento-polling-y-actualización-en-tiempo-real)
- [RNF-09 — Disponibilidad: Operación 24/7](#rnf-09--disponibilidad-operación-247)
- [RNF-10 — Disponibilidad: Modo Degradado](#rnf-10--disponibilidad-modo-degradado)
- [RNF-11 — Stack Tecnológico Requerido](#rnf-11--stack-tecnológico-requerido)
- [RNF-12 — Despliegue y Configuración de Servidor](#rnf-12--despliegue-y-configuración-de-servidor)
- [RNF-13 — Mantenibilidad: Logs y Diagnóstico](#rnf-13--mantenibilidad-logs-y-diagnóstico)
- [RNF-14 — Mantenibilidad: Herramientas CLI](#rnf-14--mantenibilidad-herramientas-cli)
- [RNF-15 — Integridad de Datos](#rnf-15--integridad-de-datos)

---

## RNF-01 — Seguridad: Gestión de Contraseñas

### Algoritmo y coste

Las contraseñas de usuarios se almacenan exclusivamente como hashes bcrypt generados con:

```php
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
```

El coste es 12 (`BCRYPT_COST = 12`), lo que implica 2^12 = 4096 iteraciones por hash. Este valor balancea resistencia a fuerza bruta con tiempo de respuesta aceptable en el servidor de la institución.

La verificación usa `password_verify($password, $hash)`, que es timing-safe por diseño de PHP (compara en tiempo constante independientemente de cuántos caracteres coincidan).

### Rehash automático

En cada login exitoso, el sistema comprueba `password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])`. Si retorna `true` (porque el coste configurado cambió respecto al hash almacenado), se genera un nuevo hash con el coste actual y se actualiza en la tabla `users` **antes** de crear la sesión. Este proceso es transparente para el usuario.

### Contraseñas nunca se sanitizan como HTML

Las contraseñas nunca pasan por `htmlspecialchars()`, `trim()` ni ninguna función de sanitización de texto. Se tratan exclusivamente como strings planos y se pasan directamente a `password_hash()`. Esto es intencional: aplicar `htmlspecialchars` a una contraseña que contiene `&`, `<`, `>`, `"` o `'` la transformaría antes de hashear, rompiendo el login de cualquier usuario cuya contraseña contenga esos caracteres.

En `Security::sanitizeArray()`, los campos `password` y `password_hash` están explícitamente excluidos del sanitizado, con un comentario en el código que documenta esta decisión.

### Política de contraseñas

`Security::isStrongPassword()` aplica las siguientes reglas para aceptar una contraseña como válida:

| Regla | Requisito |
|-------|-----------|
| Longitud mínima | 10 caracteres |
| Letras minúsculas | Al menos 1 (`[a-z]`) |
| Letras mayúsculas | Al menos 1 (`[A-Z]`) |
| Dígitos | Al menos 1 (`\d`) |
| Caracteres especiales | Al menos 1 (cualquier carácter que no sea `[a-zA-Z0-9]`) |

Si no se cumplen todos los criterios, el sistema rechaza la contraseña con HTTP 422 y mensaje `"Contraseña débil"`.

### Gestión de secretos del sistema

Los valores secretos del sistema (`DB_PASS` y `SECRET_KEY`) se configuran **únicamente** a través del archivo `.env` en la raíz del repositorio, que está excluido del control de versiones (`.gitignore`). El repositorio incluye un archivo `.env.example` con claves y valores placeholder como referencia.

Si en el arranque del sistema `DB_PASS` o `SECRET_KEY` están vacíos, se registra una advertencia en `error_log` pero la aplicación **no falla duro**: continúa con el valor vacío. Esto permite que el sistema arranque en entornos de desarrollo sin `.env` configurado, aunque en producción es un error de configuración crítico.

### Generación de contraseñas

`Security::generatePassword(int $length = 16)` genera contraseñas aleatorias usando el charset `[a-zA-Z0-9!@#$%^&*]`, con `str_shuffle()` y `substr()`. Útil para el reset de admin y para las contraseñas semilla del schema.

---

## RNF-02 — Seguridad: Protección CSRF

Todas las operaciones mutativas del sistema (crear, editar, eliminar, subir archivos) están protegidas contra ataques Cross-Site Request Forgery mediante tokens de sesión.

### Generación del token

```php
bin2hex(random_bytes(32))  // → 64 caracteres hexadecimales
```

El token se genera la primera vez que se llama a `Security::csrfToken()` en la sesión y se almacena en `$_SESSION['_csrf_token']`. Las llamadas posteriores en la misma sesión devuelven el mismo token.

### Verificación

La comparación usa `hash_equals($sessionToken, $submittedToken)`, que es timing-safe (previene timing attacks donde un atacante podría deducir cuántos caracteres del token son correctos midiendo el tiempo de respuesta).

Si la verificación falla, la API responde:
```json
HTTP 403
{"ok": false, "error": "Token CSRF inválido"}
```

### Canales de entrega del token

El token se puede enviar por dos vías (el servidor acepta cualquiera de las dos):

1. **Header HTTP:** `X-CSRF-Token: <token>` (usado por el JavaScript del panel via fetch/XHR).
2. **Campo POST:** `_csrf=<token>` (usado por formularios HTML del panel).

### Endpoints que requieren CSRF

Todas las mutaciones de la API: `POST`, `PUT`, `PATCH`, `DELETE`. Incluyendo subida de archivos (multipart/form-data).

### Endpoints exentos de CSRF

Los endpoints públicos de solo lectura no requieren CSRF (ni sesión):
- `GET /api/tv`
- `GET /api/tv/ping`
- `GET /api/branding`
- `GET /api/meta`
- `GET /api/auth/csrf` (este endpoint es precisamente para obtener el token)

### Campo hidden para formularios PHP

`Security::csrfField()` genera el campo oculto HTML:
```html
<input type="hidden" name="_csrf" value="<token>">
```

El campo `_csrf` se descarta automáticamente en `Security::sanitizeArray()` (nunca llega al modelo ni a la BD).

---

## RNF-03 — Seguridad: Rate Limiting y Bloqueo de Login

### Parámetros de bloqueo

| Parámetro | Constante | Valor default |
|-----------|-----------|---------------|
| Intentos máximos fallidos | `LOGIN_MAX_ATTEMPTS` | `5` |
| Ventana de tiempo | `LOGIN_WINDOW_MINUTES` | `15` minutos |

El bloqueo se activa por **dirección IP**, no por cuenta de usuario. Esto significa que cambiar el username no evita el bloqueo si la IP ya superó el límite.

### Consulta de verificación en PostgreSQL

```sql
SELECT COUNT(*) FROM login_attempts
WHERE ip_address = ?
  AND success = FALSE
  AND created_at > now() - make_interval(mins => ?::int)
```

Si el conteo es mayor o igual a `LOGIN_MAX_ATTEMPTS`, la IP está bloqueada.

### Registro de intentos

Cada intento (exitoso o fallido) inserta una fila en `login_attempts` con:
- `ip_address`: dirección IP del cliente
- `username`: nombre de usuario intentado (puede ser `null` si estaba vacío)
- `success`: boolean
- `failure_reason`: razón textual del fallo (`usuario_no_existe`, `usuario_inactivo`, `contrasena_incorrecta`) o `null` si fue exitoso
- `user_agent`: primeros 255 caracteres del User-Agent del cliente

### Purga automática

En cada inserción en `login_attempts`, el sistema ejecuta:
```sql
DELETE FROM login_attempts WHERE created_at < now() - INTERVAL '90 days'
```

Los registros se retienen **90 días** y se purgan sin necesidad de cron.

### Excepciones al bloqueo

**Entorno development:** las IPs de loopback nunca se bloquean:
- `127.0.0.1`
- `::1`
- `0:0:0:0:0:0:0:1`

Esto previene que el desarrollador quede sin acceso al panel durante pruebas intensivas de login.

**Ventana de deshabilitación temporal:** la variable de entorno `LOGIN_RATE_LIMIT_DISABLED_UNTIL` acepta una fecha/hora ISO-8601. Mientras `time() < strtotime(LOGIN_RATE_LIMIT_DISABLED_UNTIL)`, el rate limiting no se aplica a ninguna IP. Ejemplo: `LOGIN_RATE_LIMIT_DISABLED_UNTIL=2026-11-01T08:00:00-05:00`. Útil para mantenimientos programados sin quedar bloqueados.

### IP del cliente

Por defecto el sistema usa `$_SERVER['REMOTE_ADDR']` como IP del cliente. Los headers de proxy (`HTTP_CF_CONNECTING_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`) **solo se consideran** si la variable de entorno `TRUST_PROXY=1` está configurada en `.env`. Sin `TRUST_PROXY=1`, estos headers son ignorados para prevenir que un cliente malicioso falsifique su IP enviando headers de proxy arbitrarios.

El orden de prioridad con `TRUST_PROXY=1` es:
1. `HTTP_CF_CONNECTING_IP` (Cloudflare)
2. `HTTP_X_FORWARDED_FOR` (primer IP de la lista)
3. `HTTP_X_REAL_IP`
4. `REMOTE_ADDR` (fallback)

---

## RNF-04 — Seguridad: Sanitización y Prevención de XSS

### Sanitización estándar

La función `Security::sanitize(?string $text): string` aplica:
```php
htmlspecialchars(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')
```

- `ENT_QUOTES`: convierte tanto `"` como `'`
- `ENT_HTML5`: usa las entidades HTML5 más recientes
- `UTF-8`: encoding explícito para prevenir ataques de charset

Valores `null` se convierten a string vacío `''`.

### Sanitización de arrays (inputs de API y formularios)

`Security::sanitizeArray(array $data)` recorre recursivamente todos los pares clave/valor del input:

1. Las **claves** se limpian con `preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $key)`. Claves vacías tras la limpieza se descartan.
2. El campo `_csrf` se descarta siempre (nunca debe llegar a los modelos).
3. Los campos `password` y `password_hash` se copian como `(string) $value` **sin sanitizar**.
4. Arrays anidados se procesan recursivamente.
5. Todos los demás valores se sanitizan con `Security::sanitize()`.

### Campo `external_data` (JSONB)

El sanitizado estándar convierte `"` en `&quot;`, lo que corrompe el JSON almacenado en `external_data`. Para evitar esto, el modelo `GeneralContent` aplica `html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')` antes de parsear el JSON. Si el resultado no es un array válido (`json_last_error() !== JSON_ERROR_NONE`), el campo se descarta completamente de la operación de escritura.

### HTML limitado en campos de bienvenida

Los campos `welcome_lema` y `welcome_pie` permiten usar `<b>` para negrilla. El sistema usa `DisplayConfig::sanitizeWelcomeHtml()`, que implementa un parser por tokens (no regex) con las siguientes garantías:

- Solo `<b>` y `</b>` son reconocidos como etiquetas (case-insensitive).
- Cualquier otro HTML se escapa con `htmlspecialchars`.
- No se permite anidamiento de `<b>` (si ya hay uno abierto, el siguiente `<b>` se ignora).
- Un `<b>` sin su `</b>` correspondiente se cierra automáticamente al final del string.
- El resultado siempre es HTML seguro, sin posibilidad de XSS.

### Sanitización de SVG

Los SVG reciben sanitización adicional mediante parsing XML real con `DOMDocument`:

```php
$dom->loadXML($content, LIBXML_NONET)
```

`LIBXML_NONET` previene fetch de recursos externos durante el parseo (mitigación de SSRF en el parser XML). En PHP 8, la resolución de entidades externas está deshabilitada por defecto (mitigación XXE).

**Nodos eliminados:** `<script>`, `<foreignObject>`, `<iframe>`, `<embed>`, `<object>`.

**Atributos eliminados (via DOMXPath sobre todos los elementos):**
- Cualquier atributo cuyo nombre empiece por `on` (event handlers: `onclick`, `onload`, `onerror`, etc.)
- Atributos `href` o `xlink:href` cuyo valor empiece por `javascript:` (case-insensitive, con espacios al inicio)

Si el archivo no es XML bien formado o el elemento raíz no es `<svg>`, se rechaza y se elimina del disco.

### Campo `titulo` de bienvenida

Aplica `strip_tags()` para eliminar cualquier HTML, pero preserva saltos de línea `\n` (reemplazando `\r\n` y `\r` por `\n`).

### Resto de campos de texto en bienvenida

Aplican `strip_tags()` directamente, sin excepciones.

---

## RNF-05 — Seguridad: Validación de Uploads y Archivos

### Principio: lista blanca, no lista negra

El sistema solo acepta tipos de archivo explícitamente permitidos. No se mantiene una lista de tipos prohibidos (que podría tener huecos).

### Doble validación: extensión + MIME real

**Paso 1 — Extensión:** se extrae con `pathinfo($originalName, PATHINFO_EXTENSION)` y se convierte a minúsculas. Debe estar en alguna de las listas: `ALLOWED_IMAGE_TYPES`, `ALLOWED_VIDEO_TYPES`, `ALLOWED_AUDIO_TYPES`, `ALLOWED_DOC_TYPES`.

**Paso 2 — MIME real:** se detecta el MIME del contenido del archivo con `finfo_open(FILEINFO_MIME_TYPE)` sobre el archivo temporal de PHP. Si `finfo` no está disponible, cae a `mime_content_type()`. Si tampoco está, usa `application/octet-stream`.

El MIME detectado se compara contra la tabla de MIMEs esperados:

| Extensión | MIMEs aceptados |
|-----------|-----------------|
| `jpg`, `jpeg` | `image/jpeg` |
| `png` | `image/png` |
| `gif` | `image/gif` |
| `webp` | `image/webp` |
| `svg` | `image/svg+xml`, `text/xml`, `text/plain` |
| `mp4` | `video/mp4` |
| `webm` | `video/webm` |
| `ogg` | `video/ogg`, `audio/ogg`, `application/ogg` |
| `mov` | `video/quicktime` |
| `mp3` | `audio/mpeg` |
| `wav` | `audio/wav`, `audio/x-wav`, `audio/vnd.wave` |
| `m4a` | `audio/mp4`, `audio/x-m4a`, `audio/mpeg` |
| `pdf` | `application/pdf` |

**Excepción:** videos con MIME `application/octet-stream` se aceptan si la extensión es de video válida. Algunos sistemas operativos reportan así archivos `.mp4` legítimos.

Si el MIME detectado no está en los aceptados para esa extensión, la subida falla con: _"El contenido del archivo no coincide con la extensión .{ext}"_.

### Nombres de archivo en disco

El nombre en disco es generado por el sistema: `YYYYMMDD_HHIISS_<8hex>.ext`. El nombre original del usuario **nunca** forma parte del path físico en disco. Esto elimina:
- Path traversal (nombres como `../../php.ini`).
- Conflictos por nombres duplicados.
- Inyección de extensiones dobles (`archivo.php.jpg`).

`Security::sanitizeFilename()` (usada si se necesita exponer algún nombre derivado del original) permite solo `[a-zA-Z0-9_\-\.]`, colapsa múltiples `_` y elimina `.` y `_` al inicio y al final.

### Bloqueo de PHP en uploads

Los archivos PHP y sus variantes (`.php`, `.phtml`, `.php5`, etc.) están bloqueados a nivel de Apache en la configuración del `.htaccess` de la raíz, que deniega acceso HTTP directo al directorio `img/uploads/` para archivos ejecutables.

### Directorios inaccesibles vía URL

Los siguientes directorios están bloqueados contra acceso web directo en `.htaccess`:
- `src/` (código fuente PHP)
- `scripts/` (herramientas, SQL, tests)
- `logs/` (logs del sistema)

---

## RNF-06 — Seguridad: Headers HTTP

### Headers enviados en cada respuesta

El bootstrap `src/bootstrap.php` envía los siguientes headers en cada request PHP, antes de cualquier contenido:

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
```

Y el siguiente header de Content Security Policy (CSP):

```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'unsafe-inline';
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com;
  img-src 'self' data: blob:;
  connect-src 'self';
  object-src 'none';
  base-uri 'self';
  form-action 'self';
  frame-ancestors 'self'
```

### Header adicional en producción

Cuando `APP_ENV = production`:

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

Esto instruye a los navegadores a usar siempre HTTPS durante 1 año. **Nota:** este header solo debe estar activo cuando el servidor realmente sirva por HTTPS; en caso contrario puede romper el acceso al panel desde HTTP.

### Headers en respuestas JSON de la API

Adicionalmente a los del bootstrap, todas las respuestas JSON incluyen:

```
Content-Type: application/json; charset=utf-8
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
Pragma: no-cache
```

Esto previene que proxies intermedios o el buffer del monitor Signage cacheen respuestas de la API.

### Propósito de cada header

| Header | Protección |
|--------|------------|
| `X-Content-Type-Options: nosniff` | Previene MIME sniffing (el navegador no intenta "adivinar" el tipo de un recurso) |
| `X-Frame-Options: SAMEORIGIN` | Previene clickjacking (el panel no puede ser embebido en iframes de otros sitios) |
| `X-XSS-Protection: 1; mode=block` | Activa el filtro XSS del navegador (legacy, complementa CSP) |
| `Referrer-Policy: strict-origin-when-cross-origin` | Limita qué información de la URL se envía como Referer en requests cross-origin |
| `Cache-Control: no-store` | El contenido del panel y la API no debe ser cacheado en proxies ni historial |
| `CSP` | Restringe las fuentes de recursos del panel (scripts, estilos, imágenes, fuentes, conexiones) |
| `HSTS` | Fuerza HTTPS en producción durante 1 año, incluyendo subdominios |

---

## RNF-07 — Compatibilidad con LG Signage / Chromium 53

El player de la cartelera debe funcionar en monitores LG Signage que ejecutan webOS con Chromium 53, lanzado en 2016. Esto impone restricciones técnicas estrictas en el JavaScript del player y en el formato de los videos.

### Restricciones de JavaScript ES5

Todo el código JavaScript del player (`src/cartelera.html` + `index.php`) debe estar escrito en ES5 estricto. Las siguientes características de ES6+ **no son compatibles** con Chromium 53 y están prohibidas en el player:

| Característica ES6+ | Alternativa ES5 |
|---------------------|-----------------|
| Arrow functions `() =>` | `function() {}` |
| `const` y `let` | `var` |
| Template literals `` `${x}` `` | Concatenación `"" + x` |
| Módulos ES6 `import`/`export` | No usar módulos |
| `async`/`await` | Callbacks o XMLHttpRequest |
| Promises nativas | Callbacks |
| Spread operator `...` | `Array.prototype.concat()` o `apply()` |
| Destructuring `const {a} = obj` | `var a = obj.a` |
| Classes ES6 | Prototype-based inheritance o funciones constructoras |
| `for...of` | `for` con índice o `forEach` |
| `Object.assign` | Copias manuales propiedad por propiedad |

Esta restricción solo aplica al código del player (lo que corre en el monitor). El código del panel y la API en PHP no tienen esta restricción.

### Formato de video requerido para Signage

Para garantizar reproducción en Chromium 53 y decoders de hardware de TV, todos los videos deben cumplir:

| Parámetro | Valor requerido | Justificación |
|-----------|-----------------|---------------|
| Contenedor | MP4 | Único formato universalmente soportado en Chromium antiguo |
| Codec de video | H.264 | Soporte garantizado en hardware de TV |
| Perfil H.264 | `baseline` | Máxima compatibilidad con hardware; otros perfiles pueden fallar en TVs baratas |
| Nivel H.264 | `3.1` | Compatible con la mayoría de hardware 1080p de la época |
| Pixel format | `yuv420p` | Algunos decoders de hardware no soportan `yuv444p` ni `yuv422p` |
| Codec de audio | AAC | Único codec de audio ampliamente soportado en Chromium 53 |
| Bitrate de audio | 128k | Balance calidad/compatibilidad |
| Canales de audio | 2 (estéreo) | Previene problemas con decoders que no soporten mono o surround |
| Sample rate | 44100 Hz | Standard de compatibilidad |
| Resolución máxima | 1920×1080 | El sistema escala hacia abajo preservando relación de aspecto si el video es más grande |
| Movflags | `+faststart` | El átomo `moov` va al inicio del archivo; el video puede empezar antes de descargar todo el archivo |

El sistema garantiza este formato transcodificando automáticamente todos los uploads de video.

### HTTP sin TLS (operación en red local)

Los monitores Signage operan en la red interna de la institución y frecuentemente no aceptan certificados TLS autofirmados. Por eso:

- `COOKIE_SECURE` está en `false` por defecto. Con `COOKIE_SECURE = true`, el navegador solo envía la cookie de sesión por HTTPS; si el servidor sirve por HTTP, el login se rompe (la cookie nunca se envía y el sistema queda inutilizable).
- Para activar `COOKIE_SECURE = true` se debe configurar `COOKIE_SECURE=1` en `.env`, pero **solo** cuando el servidor realmente sirve por HTTPS con un certificado válido o de la CA interna instalado en los monitores.
- El feed TV y el ping usan `Cache-Control: no-store` para prevenir que el buffer del monitor Signage sirva contenido desactualizado.

### URL relativa del logo y medios

Las URLs de medios (logo, fondos, imágenes de contenido) se construyen como `APP_BASE + '/img/uploads/' + category + '/' + filename`, sin incluir esquema (`http://`) ni host. Esto permite que el mismo sistema funcione tanto en HTTP (cartelera en red local) como en HTTPS (panel accedido desde fuera) sin URL rota por mixed-content.

---

## RNF-08 — Rendimiento: Polling y Actualización en Tiempo Real

### Estrategia de polling en dos niveles

El player usa una estrategia de dos niveles para minimizar el tráfico de red sin sacrificar velocidad de actualización:

**Nivel 1 — Ping ligero (siempre activo):**
- Se ejecuta cada `api_poll_seconds` segundos (default: **30 segundos**).
- Endpoint: `GET /api/tv/ping`.
- Respuesta: solo 3 campos (`server_ts`, `last_update`, `tv_enabled`). Payload mínimo, sin slides.
- Propósito: detectar si el contenido cambió sin traer todo el feed.

**Nivel 2 — Feed completo (solo cuando hay cambios):**
- Se activa cuando el `last_update` del ping difiere del que tiene el player en memoria.
- Endpoint: `GET /api/tv`.
- Trae el payload completo con todos los slides de todos los escenarios.
- Este "modo turbo" garantiza que el contenido actualizado aparezca en la TV en menos de `api_poll_seconds` segundos desde el cambio.

### Cálculo de `last_update`

```sql
SELECT GREATEST(
    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM academic_content), 0),
    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM center_content), 0),
    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM general_content), 0),
    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM gallery_content), 0),
    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM display_config), 0)
) as last_update
```

Esta query se ejecuta en cada ping y en cada feed completo. Retorna un solo valor entero (Unix epoch en segundos). Si todas las tablas están vacías (o no han sido actualizadas nunca), retorna `time()` como fallback.

### Caché de configuración en memoria

`DisplayConfig::$cache` es un array estático (caché de request) que almacena los valores de `display_config` leídos durante el mismo ciclo de request PHP. Esto evita que múltiples lecturas de la misma clave (`tv_enabled`, `api_poll_seconds`, etc.) en el mismo request generen queries duplicadas a la BD. Se invalida en cada `DisplayConfig::set()`.

### Transcodificación con preset `veryfast`

El preset `veryfast` de ffmpeg es el más rápido disponible que aún produce archivos H.264 válidos. El trade-off es que el archivo resultante puede ser hasta un 20-30% más grande que con preset `slow` o `medium`. Para el caso de uso de cartelería en red local, el tiempo de espera del usuario al subir un video es más crítico que el tamaño del archivo.

### `+faststart` en videos

El flag `-movflags +faststart` de ffmpeg reubica el átomo `moov` al inicio del archivo MP4 (normalmente queda al final, requiriendo descargar todo el archivo antes de empezar a reproducir). Con faststart, el navegador/TV puede iniciar la reproducción mientras el resto del archivo todavía se está descargando.

---

## RNF-09 — Disponibilidad: Operación 24/7

### Operación continua en monitores

El sistema está diseñado para correr ininterrumpidamente en monitores de señalización digital durante toda la jornada institucional (y potencialmente las 24 horas). El player en el monitor no requiere ninguna interacción del usuario para continuar rotando contenido.

### Manejadores globales de error en PHP

`src/bootstrap.php` registra handlers globales con `set_error_handler()` y `set_exception_handler()` que:

1. Capturan cualquier excepción no manejada y error PHP.
2. Registran el error en `logs/php-errors.log` (y en `Logger::error()` si la clase ya está cargada).
3. En **producción** (`APP_ENV = production`): muestran una pantalla de error institucional HTML estilizada con código 500, sin exponer stack traces ni detalles internos.
4. En **desarrollo** (`APP_DEBUG = true`): muestran el mensaje de excepción y el stack trace completo en HTML.
5. Siempre terminan con `exit(1)`.

Esto elimina las pantallas en blanco del servidor que confundirían a los monitores Signage.

### Logs de errores PHP en producción

```
error_reporting(E_ALL)
ini_set('display_errors', '0')
ini_set('log_errors', '1')
ini_set('error_log', LOG_PATH . '/php-errors.log')
```

Todos los errores PHP se capturan en el log pero **no** se muestran en pantalla. En desarrollo, `display_errors = 1`.

### Fallback de auditoría sin BD

Si el sistema de auditoría (`Logger::log()`) no puede escribir en `activity_log` (BD no disponible), el método captura la excepción y escribe el evento en `logs/activity-YYYY-MM-DD.log` en formato JSONL. El request HTTP continúa sin interrumpirse. La operación que originó el log (crear contenido, login, etc.) **no falla** por un error en el log de auditoría.

---

## RNF-10 — Disponibilidad: Modo Degradado

El sistema define comportamientos explícitos para los escenarios de fallo más probables:

### Modo apagado de cartelera (`tv_enabled = '0'`)

El administrador puede "apagar" la cartelera sin detener el servidor. Cuando `DisplayConfig::get('tv_enabled')` retorna `'0'`:

- El feed `GET /api/tv` incluye `config.tv_enabled = '0'`.
- El player detecta este valor y deja de rotar slides.
- En su lugar muestra la pantalla de `tv_off_message` (configurable, default: `"Cartelera apagada"`).
- El polling continúa funcionando. Cuando el admin reactive la cartelera (`tv_enabled = '1'`), el player lo detecta en el siguiente ping y retoma la rotación automáticamente sin necesidad de recargar la página.

### Player sin respuesta del servidor

Si el servidor no responde al ping o al feed completo (timeout, red caída, servidor reiniciando):

- El player mantiene el último payload conocido en memoria.
- La rotación de slides continúa con el contenido anterior.
- Se reintenta el ping en el siguiente intervalo de `api_poll_seconds`.

No hay comportamiento de "pantalla negra" por fallo de red.

### Cron con fuentes externas fallidas

Si una fuente externa del cron falla (timeout de red, API caída, respuesta con formato inesperado, JSON inválido):

- El cron registra el error (`$fail++`) y continúa con la siguiente fuente sin interrumpir el proceso.
- Los slides de indicadores externos correspondientes a esa fuente **no se actualizan** en esa ejecución; conservan los datos de la última ejecución exitosa.
- El resumen de errores queda en `logs/external-refresh.log` para diagnóstico.
- Las demás fuentes que sí respondieron correctamente se actualizan normalmente.

### Detección de duración de video sin ffprobe

Si `ffprobe` no está instalado o no es ejecutable:

1. El sistema intenta leer la duración desde el átomo `mvhd` del MP4 directamente (ver RF-14).
2. Si tampoco puede (formato no soportado o archivo corrupto), la subida falla con error claro: _"No se pudo leer la duración del video. Use MP4 o instale ffprobe."_
3. No se deja el archivo parcialmente subido en disco (se limpia antes de retornar el error).

### Fallo de transcodificación de video

Si ffmpeg no está instalado o falla durante la transcodificación:

1. El archivo temporal `.src` se borra del disco.
2. El archivo de destino se borra si existe (parcialmente escrito).
3. Se retorna error al cliente con el detalle de las últimas 12 líneas de salida de ffmpeg.
4. No queda ningún archivo corrupto en `img/uploads/`.

---

## RNF-11 — Stack Tecnológico Requerido

### Lenguaje de servidor

| Componente | Versión mínima | Versión recomendada |
|------------|----------------|---------------------|
| PHP | 8.0 | 8.2+ |

PHP 8.2+ es recomendado por mejoras en rendimiento (JIT), tipos más estrictos y menor uso de memoria.

### Extensiones PHP

#### Requeridas (el sistema no funciona sin ellas)

| Extensión | Uso |
|-----------|-----|
| `pdo_pgsql` | Conexión a PostgreSQL vía PDO. Sin esta extensión, ninguna query a la BD funciona. |
| `fileinfo` | Detección de MIME real con `finfo_open(FILEINFO_MIME_TYPE)`. Sin ella, la validación de uploads cae a `mime_content_type()`. |
| `mbstring` | Manejo de strings multibyte (acentos, UTF-8). Usado implícitamente por varias funciones de string. |
| `openssl` | `random_bytes()` para generación de tokens CSRF, nombres de archivo y contraseñas. |

#### Recomendadas (no bloquean el arranque)

| Extensión | Uso |
|-----------|-----|
| `gd` o `imagick` | Procesamiento de imágenes. Actualmente no se usa en el código relevado, pero está listada como recomendada en la documentación para futuras funcionalidades. |

### Base de datos

| Componente | Versión mínima |
|------------|----------------|
| PostgreSQL | 12 |

Se requiere PostgreSQL 12+ por el uso de funciones como `make_interval(mins => N)` (en rate limiting) y el tipo `JSONB` (en `external_data` de `general_content`). El schema usa funciones almacenadas (`log_activity`) y vistas (`v_dashboard_stats`) definidas en `scripts/sql/schema.sql`.

### Servidor web

| Componente | Versión mínima | Requisitos adicionales |
|------------|----------------|------------------------|
| Apache | 2.4 | `mod_rewrite` habilitado, `AllowOverride All` en el DirectoryRoot |

El sistema usa un único `.htaccess` en la raíz del repositorio que depende de `mod_rewrite` para el enrutamiento de URLs limpias y los redirects 301 de rutas legacy.

### Frontend (solo para build)

| Componente | Versión | Contexto |
|------------|---------|----------|
| Node.js | 18+ | Solo en entorno de desarrollo para build del frontend. No se requiere en el servidor de producción. |

### Herramientas opcionales de video

| Herramienta | Uso | Si no está disponible |
|-------------|-----|----------------------|
| `ffmpeg` | Transcodificación de videos a H.264 Baseline MP4 | Los uploads de video fallan con mensaje de error |
| `ffprobe` | Lectura de duración de videos | Se intenta fallback con parser de átomo `mvhd` |

El sistema busca los binarios en este orden:
1. `scripts/bin/ffmpeg` / `scripts/bin/ffprobe` (local al repositorio)
2. `$PATH` del sistema (`command -v ffmpeg`)

### Zona horaria

El servidor debe tener configurada (o el sistema lo fuerza via PHP) la zona horaria `America/Bogota`. Esto aplica a:
- `date()` y `new DateTimeImmutable('now')` en todo el código PHP.
- La clave `APP_TIMEZONE = 'America/Bogota'` en `Config.php`, aplicada con `date_default_timezone_set()`.
- Las queries a PostgreSQL que usan `now()` y `make_interval()` usan la zona del servidor PostgreSQL; debe estar en sincronía con la zona PHP.

---

## RNF-12 — Despliegue y Configuración de Servidor

### Base path y Alias Apache

El sistema está diseñado para servirse bajo un subdirectorio del servidor web, no en la raíz del DocumentRoot. La configuración típica:

```apache
Alias /tv /var/www/html/Cartelera
<Directory /var/www/html/Cartelera>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
```

La base path `/tv` es configurable mediante `APP_BASE` en `.env`. Todos los links, redirects y URLs de medios del sistema se construyen sobre `APP_BASE`.

### Estructura del repositorio en disco

```
/var/www/html/Cartelera/   ← raíz del repositorio (APP_BASE = /tv apunta aquí)
├── .htaccess              ← único .htaccess; gestiona rewrite y bloqueos
├── index.php              ← cartelera de producción (player)
├── .env                   ← secretos (no versionado)
├── css/  js/              ← estáticos del panel
├── img/uploads/           ← archivos subidos (necesita escritura www-data)
├── logs/                  ← logs del sistema (necesita escritura www-data)
├── src/                   ← código PHP (bloqueado para acceso web)
└── scripts/               ← herramientas, SQL, tests (bloqueado para acceso web)
```

### Sin carpeta `/public`

A diferencia de frameworks modernos, este sistema **no** usa una carpeta `public/` separada. `index.php` está en la raíz del repositorio. Apache gestiona los bloqueos de `src/`, `scripts/` y `logs/` directamente en el `.htaccess`.

### Único `.htaccess`

Hay un solo `.htaccess` en la raíz del repositorio. No existen `.htaccess` en subcarpetas. Toda la lógica de rewrite, bloqueos de directorios y headers de seguridad estáticos está centralizada ahí.

### Permisos de escritura requeridos

```bash
sudo chown -R www-data:www-data img/uploads logs
sudo chmod -R 775 img/uploads logs
```

El usuario de Apache (`www-data` en Debian/Ubuntu) necesita escritura en:
- `img/uploads/` y sus subcarpetas (`academic/`, `center/`, `general/`, `gallery/`, `branding/`, `welcome/`)
- `logs/`

Los archivos subidos se crean con permisos `0644`.

### Variables de entorno

El sistema carga variables de entorno desde `.env` en la raíz del repositorio mediante `EnvLoader::load()`. Si el archivo no existe, se usan los valores default definidos en `Config.php`. No se usa `putenv()` ni `$_ENV` directamente: las variables se acceden con `EnvLoader::get($key, $default)`.

Variables esperadas en `.env`:

```env
DB_HOST=10.5.213.103
DB_PORT=5432
DB_NAME=db_cset
DB_USER=postgres
DB_PASS=<contraseña_segura>
APP_ENV=production
APP_DEBUG=0
APP_URL=http://10.5.213.103/tv
APP_BASE=/tv
SECRET_KEY=<clave_aleatoria_32+_chars>
COOKIE_SECURE=0
TRUST_PROXY=0
LOGIN_MAX_ATTEMPTS=5
LOGIN_WINDOW_MINUTES=15
```

### Entornos soportados

| Variable | `production` | `development` |
|----------|--------------|---------------|
| `display_errors` | `0` | `1` |
| `APP_DEBUG` | `false` | `true` |
| `log_errors` | `1` (a `logs/php-errors.log`) | — |
| HSTS header | Habilitado | Deshabilitado |
| Rate limiting en loopback | Activo | Desactivado |
| Stack trace en pantalla | No (pantalla institucional) | Sí |

### Redirecciones de URLs legacy

El `.htaccess` redirige las rutas legacy `/cset/*` a `/tv/*` con HTTP **301** (permanente). Esto mantiene compatibilidad con bookmarks y enlaces externos que usaban la URL anterior. Ejemplo:

```
/cset/tv/  →  /tv/
/cset/admin/dashboard  →  /tv/admin/dashboard
```

### Despliegue alternativo en DocumentRoot

En instalaciones donde el DocumentRoot del servidor es `/var/www/html`, el sistema puede servirse directamente en `http://<IP>/` (sin subdirectorio) copiando o symlinkando el repositorio como DocumentRoot. En este caso, `APP_BASE` debe configurarse como `/` o vacío.

---

## RNF-13 — Mantenibilidad: Logs y Diagnóstico

### Archivos de log del sistema

| Archivo | Contenido | Formato | Generado por |
|---------|-----------|---------|--------------|
| `logs/activity-YYYY-MM-DD.log` | Acciones de auditoría (fallback cuando BD no disponible) | JSONL | `Logger::writeToFile()` |
| `logs/errors-YYYY-MM-DD.log` | Errores críticos de la aplicación | JSONL | `Logger::error()` |
| `logs/external-refresh.log` | Resultados de cada ejecución del cron de indicadores externos | Texto plano (append) | `cron_actualizar_externos.php` |
| `logs/php-errors.log` | Errores PHP del servidor | Formato nativo PHP | `error_log` de PHP |

### Formato JSONL

Los logs de actividad y errores usan JSONL (JSON Lines): un objeto JSON por línea. Esto facilita el procesamiento con herramientas de línea de comandos:

```bash
# Ver últimas acciones de login
grep '"login"' logs/activity-2026-10-30.log | tail -20

# Ver errores de hoy
cat logs/errors-2026-10-30.log | python3 -m json.tool

# Contar errores por tipo
cat logs/errors-*.log | jq '.message' | sort | uniq -c
```

### Dashboard en tiempo real

El endpoint `GET /api/dashboard` retorna las últimas 15 acciones de `activity_log` con JOIN a `users`. Disponible para cualquier usuario con sesión activa, sin necesidad de acceder a los archivos de log.

### Estructura de código plana

Todos los archivos PHP del sistema (modelos, controladores, utilidades) están en `src/` sin subcarpetas. No hay namespaces ni carga automática por PSR-4. El autoload es un `spl_autoload_register` plano que busca `src/{ClassName}.php`.

```php
spl_autoload_register(function ($class) {
    $path = BASE_PATH . '/src/' . $class . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});
```

Esto simplifica la navegación del código: el archivo de cualquier clase se puede encontrar directamente en `src/{NombreDeClase}.php`.

### Composer solo en scripts/tests

El sistema **no usa Composer en producción**. Las dependencias de producción (no las hay actualmente) se cargarían manualmente. Composer solo se usa en `scripts/` para las dependencias de testing (PHPUnit):

```bash
composer install --working-dir=scripts
composer test --working-dir=scripts
```

### Tests

Los tests están en `scripts/tests/` organizados en dos carpetas:

| Carpeta | Contenido |
|---------|-----------|
| `scripts/tests/Unit/` | Tests unitarios de modelos y validaciones |
| `scripts/tests/Api/` | Tests de integración de la API |

Configuración en `scripts/phpunit.xml`. Bootstrap en `scripts/tests/bootstrap.php`.

### Schema y migraciones

| Archivo | Descripción |
|---------|-------------|
| `scripts/sql/schema.sql` | Schema completo de la BD (tablas, índices, funciones, vistas, datos semilla) |
| `scripts/sql/migrations/` | Migraciones incrementales por fecha (`YYYY-MM-DD-descripcion.sql`) |
| `scripts/sql/seed_passwords.sql` | Hashes de contraseñas de ejemplo para desarrollo |

Las migraciones se aplican manualmente con `psql -f scripts/sql/migrations/<archivo>.sql`.

---

## RNF-14 — Mantenibilidad: Herramientas CLI

Herramientas de administración ejecutables desde la consola del servidor. **No son accesibles vía HTTP** (están en `src/`, bloqueado por Apache).

### `src/herramienta_reset_admin.php`

**Propósito:** restablecer las credenciales del usuario `admin` sin acceso al panel web.

**Cuándo usarla:**
- El administrador olvidó su contraseña.
- La cuenta `admin` fue desactivada accidentalmente.
- Se necesita restablecer el sistema a un estado conocido tras un incidente.

**Uso:**
```bash
php src/herramienta_reset_admin.php
```

### `src/herramienta_reencode_videos.php`

**Propósito:** re-transcodificar los videos existentes en `img/uploads/` al formato Signage actualizado.

**Cuándo usarla:**
- Se modificaron los parámetros de transcodificación en `VideoTranscoder` (CRF, escala, perfil) y se quiere unificar el formato de todos los videos existentes.
- Se detectaron videos en la biblioteca que no reproducen correctamente en los monitores Signage.
- Tras migrar de una versión anterior del sistema que no transcodificaba automáticamente.

**Uso:**
```bash
php src/herramienta_reencode_videos.php
```

### `src/herramienta_renumerar_orden.php`

**Propósito:** compactar y renumerar el campo `display_order` de 1…N en las cuatro tablas de contenido.

**Cuándo usarla:**
- El `display_order` tiene huecos o valores inconsistentes (por ejemplo, tras importar datos de un sistema externo).
- Se quiere reordenar el contenido cronológicamente (modo `--chronological`).

**Uso:**
```bash
# Renumerar manteniendo el orden actual de display_order
php src/herramienta_renumerar_orden.php

# Renumerar por fecha de creación (migración one-shot)
php src/herramienta_renumerar_orden.php --chronological
```

### `src/cron_actualizar_externos.php`

**Propósito:** actualizar los indicadores externos (divisas, cripto, café, petróleo, clima) desde APIs públicas.

**Uso automático (crontab recomendado):**
```bash
0 6 * * * /usr/bin/php /var/www/html/Cartelera/src/cron_actualizar_externos.php >> /var/www/html/Cartelera/logs/external-refresh.log 2>&1
```

**Uso manual para diagnóstico:**
```bash
php src/cron_actualizar_externos.php
```

Imprime el resultado de cada fuente en consola, lo que facilita identificar cuál API está fallando sin necesidad de revisar los logs.

---

## RNF-15 — Integridad de Datos

### Display order

- El campo `display_order` tiene un tope máximo de **999999** (`ContentDisplayOrder::MAX`).
- Al crear un elemento, se asigna `MAX(display_order) + 1`; si el máximo llega a 999999, los nuevos elementos también reciben 999999 (sin desbordamiento).
- Al eliminar cualquier elemento, `display_order` se renumera automáticamente de 1…N usando:
  ```sql
  WITH ordered AS (
      SELECT id, ROW_NUMBER() OVER (ORDER BY display_order ASC, created_at ASC NULLS LAST, id ASC) AS rn
      FROM {tabla}
  )
  UPDATE {tabla} AS t SET display_order = ordered.rn FROM ordered WHERE t.id = ordered.id
  ```
  Esta operación garantiza que no queden huecos en la secuencia tras eliminar elementos.
- Las cuatro tablas que usan `display_order` son: `academic_content`, `center_content`, `general_content`, `gallery_content`.

### Vigencia de slides

| Restricción | Valor |
|-------------|-------|
| Vigencia mínima (`show_until`) | 1 hora desde ahora (`ContentValidity::MIN_SECONDS = 3600`) |
| Vigencia máxima (`show_until`) | 6 meses desde ahora (`ContentValidity::MAX_MONTHS = 6`) |

Si se intenta guardar un slide en modo "mantener vigencia" cuando `show_until` ya está en el pasado, el sistema lanza un `InvalidArgumentException` con mensaje descriptivo. No guarda silenciosamente una fecha expirada.

La validación tiene una tolerancia de 2 segundos: si la diferencia entre el `show_until` existente y el nuevo es menor a 2 segundos, se considera "sin cambio" y se omite la validación de mínimo 1 hora (para evitar rechazar guardados que simplemente no tocan la fecha de vigencia).

### Protección de integridad de usuarios

| Restricción | Comportamiento |
|-------------|----------------|
| Último admin | No se puede eliminar el último usuario con rol `admin` en el sistema |
| Auto-desactivación | Un usuario no puede desactivar su propia cuenta |
| Confirmación de eliminación | Requiere escribir el `username` exacto (comparación `hash_equals`) |
| Username único | Si ya existe, retorna HTTP 409 en create y update |
| Username solo alfanumérico | Validado con `preg_match('/^[a-zA-Z0-9_]+$/', $username)` |

### Módulos deshabilitados en panel con datos activos en TV

Los módulos `center` y `gallery` están en `PANEL_DISABLED_MODULES`. Esto tiene implicaciones de integridad intencionales:

- La API del panel retorna HTTP 403 para estos módulos (previene edición accidental).
- El feed TV (`GET /api/tv`) **sí incluye** el contenido activo de estas tablas en los escenarios `center` y `gallery`.
- Los datos en `center_content` y `gallery_content` siguen siendo válidos y se sirven a la cartelera, aunque no sean editables desde el panel actual.
- Esta separación permite tener datos históricos o de referencia en la TV sin exponer su gestión en el panel de la versión actual.

### Restricciones de formato en campos específicos

| Campo | Restricción | Validación |
|-------|-------------|------------|
| Colores de bienvenida | Formato `#RRGGBB` exacto (6 dígitos hex) | Regex `/^#[0-9A-Fa-f]{6}$/` |
| `icon` en contenido general | Solo valores de `GENERAL_ICONS` | `in_array($raw, GENERAL_ICONS, true)` |
| `duration` de bienvenida | Entero en `[3, 120]` segundos | Ajuste automático (no error) |
| `duration_seconds` de video | Entero en `[5, 600]` segundos | `VideoDuration::clampSlideSeconds()` |
| Duración máxima de video | 600 segundos (10 minutos) | Rechazo si supera 600.5 s |
| `sector` de centro | Solo claves de `PROGRAM_AREAS` | Validación en controlador |
| Claves de config | Solo strings (no arrays) | `if (is_array($value)) continue` en ConfigController |

---

## RNF-16 — Acceso a Datos: Capa de Persistencia (PDO)

### Patrón Singleton

`Database` implementa el patrón Singleton: una única instancia de PDO por request PHP. La primera llamada a `Database::getInstance()` crea la conexión; las posteriores reutilizan la misma instancia. Esto evita abrir múltiples conexiones a PostgreSQL dentro de un mismo request.

### Configuración de PDO

| Opción PDO | Valor | Justificación |
|------------|-------|---------------|
| `PDO::ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | Los errores SQL lanzan `PDOException` en lugar de retornar `false`; simplifican el manejo de errores |
| `PDO::ATTR_DEFAULT_FETCH_MODE` | `FETCH_ASSOC` | Todas las filas se obtienen como arrays asociativos; nunca como arrays numéricos ni objetos |
| `PDO::ATTR_EMULATE_PREPARES` | `false` | Los prepared statements son reales en el driver de PostgreSQL; no emulados por PHP. Garantiza separación real de código y datos (prevención de SQL injection a nivel driver) |
| `PDO::ATTR_PERSISTENT` | `false` | Sin conexiones persistentes; cada request abre y cierra su propia conexión |

### DSN de conexión

```
pgsql:host={DB_HOST};port={DB_PORT};dbname={DB_NAME}
```

Con `DB_HOST = 10.5.213.103`, `DB_PORT = 5432`, `DB_NAME = db_cset` por defecto.

### Schema de PostgreSQL

Si `DB_SCHEMA` es distinto de `public` y no está vacío, el sistema ejecuta al conectar:

```sql
SET search_path TO {DB_SCHEMA}, public
```

Esto permite tener el schema de la aplicación en un schema distinto al `public` de PostgreSQL.

### INSERT con `RETURNING id`

Todos los inserts usan la cláusula `RETURNING id` de PostgreSQL. Esto permite obtener el ID auto-generado del registro insertado en una sola operación, sin necesidad de `lastInsertId()` (que no funciona igual en PostgreSQL que en MySQL).

```sql
INSERT INTO {tabla} (col1, col2) VALUES (:col1, :col2) RETURNING id
```

### Tipado correcto de booleanos en PostgreSQL

PDO no convierte automáticamente los valores PHP `true`/`false` al tipo booleano de PostgreSQL. Sin bind explícito, un `true` de PHP se enviaría como el string `'1'`, que PostgreSQL podría rechazar en columnas `BOOLEAN` estrictas.

`Database::bindValues()` aplica `PDO::PARAM_BOOL` para booleanos, `PDO::PARAM_INT` para enteros, `PDO::PARAM_NULL` para `null` y `PDO::PARAM_STR` para todo lo demás. Esto se usa en `insert()` y `update()`.

### Transacciones

La clase `Database` expone `beginTransaction()`, `commit()` y `rollback()`. El `rollback()` verifica `$pdo->inTransaction()` antes de ejecutar, evitando errores si se llama cuando no hay transacción activa (por ejemplo, en un bloque `finally`). Se usa en la eliminación de usuarios (`User::delete()`) para garantizar atomicidad entre el detach de referencias y el DELETE.

### Fallo de conexión

Si la conexión a PostgreSQL falla en el constructor:
- Se registra el error con `error_log()`.
- En `APP_DEBUG = true`: se relanza la excepción (muestra el stack trace).
- En producción: responde HTTP 500 con JSON `{"ok": false, "error": "Error de conexión a la base de datos"}` y termina.

---

## RNF-17 — Carga de Variables de Entorno (`EnvLoader`)

### Mecanismo de carga

`EnvLoader::load(string $path)` parsea el archivo `.env` en la raíz del repositorio una sola vez por proceso PHP (guardado en el flag estático `self::$loaded`). Llamadas adicionales dentro del mismo request son no-ops.

### Formato del archivo `.env`

El parser soporta:
- Líneas `KEY=VALUE` (separador `=`, primera ocurrencia).
- Comentarios con `#` al inicio de línea (líneas de comentario se saltan completamente).
- Líneas vacías se saltan.
- Valores sin comillas: se hace `trim()` del valor.
- Valores entre comillas dobles `"..."` o simples `'...'`: se remueven las comillas externas. Solo se soportan comillas que envuelvan el valor completo; las comillas internas no se escapan.
- Líneas sin `=` se ignoran.

### No pisa variables ya definidas

Si una variable de entorno ya está definida en el entorno del proceso (por ejemplo, por la configuración de Apache via `SetEnv` o por el sistema operativo), el `.env` **no la sobreescribe**. Se verifica con `isset($_ENV[$key])` antes de llamar `putenv()`.

### `EnvLoader::get(string $key, ?string $default)`

Consulta las variables en este orden:
1. `$_ENV[$key]` si existe y no está vacío.
2. `getenv($key)` si retorna un string no vacío.
3. `$default` si ninguna de las anteriores tiene valor.

Retorna `null` si no hay valor y `$default` es `null`.

### Uso en `Config.php`

Todas las constantes del sistema se definen usando `EnvLoader::get()`:

```php
define('DB_HOST', EnvLoader::get('DB_HOST', '10.5.213.103'));
define('SECRET_KEY', EnvLoader::get('SECRET_KEY', ''));
```

Esto significa que en producción sin `.env`, la aplicación arranca con los valores hardcodeados en `Config.php`. Para `DB_PASS` y `SECRET_KEY`, el default vacío genera una advertencia en `error_log` pero no rompe el arranque.

---

## RNF-18 — Construcción de URLs de Medios (Sin Esquema Fijo)

Todas las URLs de archivos multimedia (imágenes, videos, logo, fondos) se construyen **sin incluir el esquema HTTP/HTTPS ni el host**. Solo se incluye el path relativo a partir de `APP_BASE`:

```php
// BaseController::mediaUrl()
return APP_BASE . '/img/uploads/' . $category . '/' . ltrim($path, '/');
// Ejemplo: /tv/img/uploads/academic/20261030_100000_ab12cd34.jpg
```

### Justificación

La cartelera opera simultáneamente en dos contextos:
- **Monitor Signage:** accede por `http://10.5.213.103/tv/` (HTTP, red local).
- **Panel admin:** puede accederse por HTTPS desde fuera de la red.

Si las URLs de medios estuviesen hardcodeadas con `http://`, el panel en HTTPS mostraría advertencias de "mixed content" y los navegadores modernos bloquearían las imágenes. Si estuviesen con `https://`, la cartelera en HTTP no podría cargarlas (el monitor Signage no tiene certificado instalado).

Las URLs relativas funcionan en ambos contextos: el navegador las resuelve relativas al esquema y host del documento actual.

### Excepción: URLs absolutas ya existentes

Si `$path` comienza con `http://` o `https://`, `BaseController::mediaUrl()` lo retorna sin modificación. Esto da compatibilidad hacia atrás con registros que almacenaron URLs absolutas (datos migrados de versiones anteriores).

### Impacto en `brand_logo_url`

La URL del logo tiene lógica adicional en `DisplayConfig::logoPublicUrl()`: si el valor almacenado en `brand_logo_url` comienza con `/cset/` (path del sistema antiguo), se transforma reemplazando el prefijo `/cset` por `APP_BASE` actual. Esto garantiza que logos subidos antes de la migración del path base sigan funcionando.

---

## RNF-19 — Aislamiento de Roles en Controladores: Separación Admin/Editor/Program Head

El sistema implementa el principio de mínimo privilegio a nivel de controlador. Cada controlador verifica el rol requerido de forma independiente, sin delegar esa responsabilidad a un middleware central:

### Verificaciones por controlador

| Controlador | Verificación de rol |
|-------------|---------------------|
| `UsersController` | `requireAdmin()` en todas las operaciones |
| `ConfigController` | `requireAdmin()` en todas las operaciones |
| `WelcomeController` | `requireAdmin()` en todas las operaciones |
| `GeneralController` | Solo `admin` (implementado como `requireContentRole()` interna) |
| `MediaLibraryController` | `requireAdmin()` solo para DELETE; sesión para GET/POST |
| `AcademicController` | Sesión + verificación de área con `canManageItem()` |
| `DashboardController` | Solo sesión activa; cualquier rol |
| `TvController` | Público; sin verificación de sesión |
| `BrandingController` | Público; sin verificación de sesión |
| `MetaController` | Público; sin verificación de sesión |

### Verificación de ítem académico (`canManageItem`)

`AcademicController::canManageItem()` delega a `cset_can_manage_academic_item()`:
- `admin`: retorna `true` siempre.
- Cualquier otro rol: retorna `true` solo si `item['created_by'] === session.user_id`.

Un `program_head` solo puede editar o eliminar el contenido que él mismo creó, aunque sea de su mismo sector. No puede tocar contenido de su sector creado por el admin u otro `program_head`.

### Módulos deshabilitados en la API

`BaseController::requirePanelModule(string $module)` verifica si el módulo está en `PANEL_DISABLED_MODULES` y retorna HTTP 403 si está deshabilitado. Los controladores de `center` y `gallery` llaman esto al inicio de cada operación.

---

## RNF-20 — Accesibilidad del Panel de Administración

El panel implementa prácticas básicas de accesibilidad en la interfaz web:

### Iconos SVG decorativos

Todos los iconos generados con `icon(string $name)` incluyen `aria-hidden="true"`, declarándolos como decorativos para lectores de pantalla. El texto adyacente (etiquetas de menú, botones) provee el contexto semántico.

### Escape de salida HTML

La función `e()` usa `ENT_SUBSTITUTE` (en lugar de `ENT_HTML5` o `ENT_COMPAT`), que reemplaza caracteres inválidos en UTF-8 por el carácter de reemplazo Unicode (U+FFFD) en lugar de producir secuencias de bytes inválidas. Esto previene que caracteres corruptos en la BD rompan el HTML del panel.

### Respuestas de error HTTP 403

La pantalla de error 403 del panel (acceso denegado) está implementada como HTML completo con estructura semántica, estilos institucionales y un enlace de retorno al dashboard. No es una pantalla en blanco ni un mensaje de texto plano.

### Respuestas de error HTTP 500

El manejador global de excepciones genera una página de error institucional completa con código de estado HTTP correcto, sin exponer información técnica interna en producción.

---

## RNF-21 — Zona Horaria y Formato de Fechas

Todos los timestamps del sistema se gestionan con zona horaria `America/Bogota` (UTC-5).

### Configuración

```php
define('APP_TIMEZONE', 'America/Bogota');
date_default_timezone_set(APP_TIMEZONE);  // en Config.php
```

Esto afecta a todas las funciones de fecha de PHP: `date()`, `strtotime()`, `new DateTimeImmutable('now')`, etc.

### Formatos usados internamente

| Contexto | Formato | Ejemplo |
|----------|---------|---------|
| Timestamps almacenados en BD | ISO 8601 con timezone (`date('c')`) | `2026-10-30T10:00:00-05:00` |
| `last_update` en el feed TV | Unix epoch entero (`time()`) | `1761817200` |
| `publish_date` default en general | ISO 8601 (`date('c')`) | `2026-10-30T10:00:00-05:00` |
| `start_date` / `end_date` en académico | Fecha simple (`Y-m-d`) | `2026-10-30` |
| Display en el panel | `d/m/Y H:i` en Bogotá | `30/10/2026 10:00` |
| Log de cron | `Y-m-d H:i:s` | `2026-10-30 10:00:00` |

### Comparaciones de vigencia

Las queries de vigencia en PostgreSQL usan `now()` del servidor de BD. Para que las ventanas `show_from/show_until` y `expire_date` funcionen correctamente, **el servidor PostgreSQL debe estar en la misma zona horaria que la aplicación PHP** (o al menos sincronizado). Si el servidor PostgreSQL está en UTC y PHP en Bogotá, las ventanas de vigencia tendrían un desfase de 5 horas.

### Conversión en vistas del panel

`format_activity_when(?string $iso)` convierte cualquier timestamp ISO-8601 a `America/Bogota` explícitamente usando `DateTimeImmutable::setTimezone()`, independientemente de la zona del string de entrada. Esto garantiza visualización correcta aunque los timestamps de la BD estén en UTC.

---

## RNF-22 — Convenciones de Código y Arquitectura Interna

Aunque no son requisitos de usuario final, estas convenciones garantizan la mantenibilidad del sistema y deben respetarse al modificar el código.

### Estructura de archivos

- Todos los archivos PHP de la aplicación (modelos, controladores, utilidades) viven en `src/` sin subcarpetas.
- Los nombres de clase coinciden exactamente con el nombre del archivo: `AcademicContent.php` define la clase `AcademicContent`.
- No hay namespaces ni PSR-4. El autoload es plano via `spl_autoload_register`.
- Los archivos de vistas del panel se nombran `panel_{modulo}.php` y `panel_{modulo}_formulario.php`.
- Las herramientas CLI se nombran `herramienta_{funcion}.php`.

### Patrón de controladores REST

Todos los controladores REST extienden `BaseController` y siguen la convención de métodos:

| Método del controlador | Verbo HTTP | Acción |
|------------------------|------------|--------|
| `index()` | `GET` (sin ID) | Listar todos |
| `show(int $id)` | `GET` con ID | Obtener uno |
| `store()` | `POST` | Crear |
| `update(int $id)` | `PUT`/`PATCH` | Editar |
| `destroy(int $id)` | `DELETE` | Eliminar |
| `toggle(int $id)` | `PATCH .../toggle` | Invertir `is_active` |

### Respuestas JSON

Toda respuesta JSON se emite exclusivamente a través de `Security::jsonResponse()`, que:
1. Establece el código HTTP.
2. Envía `Content-Type: application/json; charset=utf-8`.
3. Envía `Cache-Control: no-store`.
4. Codifica con `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
5. Termina con `exit` (nunca retorna).

Esto garantiza que no haya salida PHP accidental (whitespace, BOM, warnings) después del JSON.

### Input JSON vs. multipart

Los controladores aceptan input en dos formatos y los fusionan:
- `Security::jsonBody()`: decodifica `php://input` como JSON. Retorna `[]` si está vacío o es JSON inválido.
- `$_POST`: campos de formularios HTML o multipart.

La fusión es `array_merge($_POST, $body)`, con `$body` (JSON) tomando precedencia sobre `$_POST`. Esto permite que el mismo controlador sirva tanto a llamadas AJAX con `Content-Type: application/json` como a formularios HTML clásicos.

### Modelo de datos: normalización antes de persistir

Todos los modelos implementan un método privado `normalizeData(array $data)` que:
- Convierte booleanos correctamente (`!empty($val) && $val !== '0' && $val !== 'false'`).
- Castea campos enteros con `(int)`.
- Convierte strings vacíos en campos opcionales a `null` (evita guardar `''` en columnas nullable).
- Descarta campos no permitidos (lista blanca explícita en `GeneralContent`).
- Valida fechas: strings como `'null'`, `'undefined'` o valores que `strtotime()` no puede parsear se descartan.

Esta normalización previene que datos mal formados del cliente lleguen a la BD.
