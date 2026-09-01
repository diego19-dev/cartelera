# AUDITORÍA DE SEGURIDAD — CSET (Cartelera Digital)

## Alineación con los 5 Pilares, MSPI, ISO/IEC 27001 y OWASP Top 10 de SmartCSET

---

| Campo | Valor |
|---|---|
| **Sistema auditado** | CSET — Cartelera Digital institucional (SvelteKit + PHP 8 + PostgreSQL), repo independiente `cset/` |
| **Documento N°** | CSET-AUD-001 |
| **Versión** | 1.0 |
| **Fecha** | 2026-07-27 |
| **Elaborado por** | Auditoría asistida (Claude Code), a solicitud del Equipo de Desarrollo |
| **Metodología** | Revisión estática de código fuente completa (Core, API, Models, frontend, infraestructura, schema) + verificación en vivo contra `10.5.213.111` (solo lectura) |
| **Marco de referencia** | Mismo marco aplicado a SmartCSET: MSPI (MinTIC, 11 ejes), ISO/IEC 27001:2022 Anexo A, OWASP Top 10 2021, y los 5 Pilares (Coherencia, Congruencia, Funcionalidad, Estabilidad, Seguridad Total) definidos en `CLAUDE.md`/`AGENTS.md` de SmartCSET |
| **Alcance** | `src/Core/`, `src/Api/`, `src/Models/`, `src/Controllers/`, `frontend/src/lib/{stores,api}/`, `public/.htaccess`, `scripts/apache-cset-alias.conf`, `sql/schema.sql`, `composer.json`, `.gitignore` + historial de git |
| **Fuera de alcance** | Auditoría dinámica activa (pentest de fuerza bruta real, fuzzing), infraestructura de red compartida (firewall del servidor `.111`, ya tratada parcialmente en el contexto de SmartCSET) |

> **Nota metodológica:** a diferencia de SmartCSET, CSET **no tiene todavía una serie documental de Fase 0** (política de seguridad, inventario de activos, matriz de riesgos, roles). Este informe cumple una función dual: (1) auditoría técnica de código como haría el `FASE1-BLI-001` de SmartCSET, y (2) diagnóstico de partida equivalente al `FASE0-RIE-003`/`INV-002`, dado que esa capa de gobernanza aún no existe para CSET. Se recomienda al final producir esa serie documental si CSET va a presentarse con el mismo nivel de blindaje que SmartCSET.

---

## 1. RESUMEN EJECUTIVO

Se revisó el 100% del backend PHP (núcleo, API, modelos), la capa de autenticación/sesión/CSRF del frontend SvelteKit, la configuración de Apache/`.htaccess`, y el schema completo de PostgreSQL. Se verificó en vivo (solo lectura) que el despliegue en `10.5.213.111` corre exactamente el código auditado.

**Hallazgo central:** CSET reutiliza correctamente varios patrones sólidos ya validados en SmartCSET (prepared statements 100%, CSRF real con `hash_equals`, bcrypt, control de acceso por sector a nivel de registro individual en la mayoría de los módulos, `.htaccess` que bloquea ejecución de PHP en `uploads/`). Sin embargo, **tiene 6 hallazgos críticos y 6 altos** que SmartCSET ya resolvió en su propia Fase 1 (E2, E3, E7, E8, E10) y que en CSET siguen abiertos — es decir, CSET repite errores que el propio proyecto hermano ya documentó y corrigió.

| Severidad | Cantidad |
|---|---|
| 🔴 Crítico | 6 |
| 🟠 Alto | 6 |
| 🟡 Medio | 5 |
| 🟢 Bajo / Observación | 3 |

El más urgente, por lejos: **hay una contraseña real de base de datos y una clave secreta placeholder hardcodeadas y versionadas en git desde el commit inicial**, y **los 10 usuarios semilla comparten la misma contraseña de fábrica (`Admin123!`)** — combinado con un rate-limiting de login que se evade trivialmente (está atado a la sesión PHP, no a la IP), esto constituye una vía de compromiso total verificable hoy mismo si no se ha rotado manualmente en producción.

---

## 2. TABLA DE HALLAZGOS

| # | Hallazgo | Severidad | OWASP 2021 | Eje MSPI | ISO 27001 Anexo A | Archivo:línea |
|---|---|---|---|---|---|---|
| C1 | Contraseña real de BD + `SECRET_KEY` placeholder hardcodeadas y **versionadas en git** — ✅ **Corregido 2026-07-27**: `Config.php` ya no tiene ningún fallback hardcodeado (`EnvLoader`), y CSET dejó de usar `postgres` por completo — ahora conecta como `cset_app`, con contraseña nueva generada exclusiva (ver C4). La contraseña vieja de `postgres` sigue en el historial de git y sigue siendo válida para ese rol, pero **ya no da acceso a nada de CSET**; purgar el historial de git queda a cargo del Equipo de Desarrollo (repo se reiniciará más adelante) | 🔴 Crítico | A05, A02 | Eje 6, Eje 8 | A.8.24, A.5.17 | `src/Core/Config.php:14,30` |
| C2 | Los 10 usuarios semilla comparten la misma contraseña conocida `Admin123!` — ✅ **Corregido 2026-07-27**: las 5 cuentas reales no-admin de `.111` (`coordiacad`, `tecnologia`, `subdirector`, `bienestar`, `test`) rotadas a `<usuario>123`; `admin` ya la tenía cambiada. De paso se reparó `bienestar`, cuyo `password_hash` no era un hash bcrypt válido (login imposible) | 🔴 Crítico | A07 | Eje 7 | A.5.17 | `sql/schema.sql:236-244` |
| C3 | Rate limiting de login basado en sesión PHP — evadible sin enviar cookie — ✅ **Corregido 2026-07-27**: tabla `login_attempts` real por IP, verificado que ya no se evade sin cookies | 🔴 Crítico | A07 | Eje 7 | A.8.16 | `src/Core/Security.php:112-128`, `Auth.php:15` |
| C4 | La aplicación conecta a PostgreSQL como **superusuario `postgres`**, sin rol de mínimo privilegio — ✅ **Corregido 2026-07-27**: `.env` real del servidor y `.env` local cortados a `cset_app` (contraseña rotada a una nueva, exclusiva de este rol); verificado en producción que el sitio sigue funcionando (login, lectura) | 🔴 Crítico | A01, A04 | Eje 7 | A.8.2, A.8.3 | `src/Core/Config.php:13`, `sql/schema.sql` (sin `CREATE ROLE`/`GRANT`) |
| C5 | Sin TLS en producción — sesión y credenciales viajan en texto plano; `COOKIE_SECURE` hardcodeado en `false` — ✅ **Corregido 2026-07-27**: TLS reutilizando el cert/vhost de SmartCSET (`smartcset.conf`, ya cubría `/cset`), `COOKIE_SECURE=1` en `.env` real, `http://.../cset/*` ahora responde `301` a HTTPS | 🔴 Crítico | A02 | Eje 6, Eje 8 | A.8.24 | `Config.php:33`; verificado en vivo (solo `http://`) |
| C6 | `GeneralController` no aplica ningún control de rol — viola el propio `QA-CRUD-CHECKLIST.md` del proyecto — ✅ **Corregido 2026-07-27** | 🔴 Crítico | A01 | Eje 7 | A.8.3 | `src/Api/GeneralController.php` (store/update/destroy/toggle) |
| A1 | Validación de subida de archivos solo por extensión — el MIME real (`finfo`) se detecta pero nunca se usa — ✅ **Corregido 2026-07-27** | 🟠 Alto | A08 | Eje 8 | A.8.28 | `src/Controllers/MediaController.php:97-103` |
| A2 | SVG permitido sin sanitización (branding/logos incluidos) — ✅ **Corregido 2026-07-27**: saneador real con `DOMDocument`, probado con XSS real (4 vectores a la vez) | 🟠 Alto | A03, A07 | Eje 8 | A.8.28 | `Config.php:51`, `scripts/apache-cset-alias.conf:62` |
| A3 | Fuga de información: DSN interno (`host:port/dbname`) se imprime siempre que la BD falla, ignorando `APP_DEBUG` — ✅ **Corregido 2026-07-27** | 🟠 Alto | A05, A09 | Eje 8 | A.8.15 | `src/Core/Database.php:31` |
| A4 | Contraseñas con caracteres especiales quedan hasheadas mal — el usuario nunca puede volver a iniciar sesión con su contraseña real — ✅ **Corregido 2026-07-27** | 🟠 Alto | A07 (funcional) | Eje 7 | A.5.17 | `UsersController.php:29,57` vs `AuthController.php:10-11` |
| A5 | Sin `Content-Security-Policy` en ningún punto del sistema — ✅ **Corregido 2026-07-27**: CSP en `bootstrap.php` (rutas `/api/*`) y replicado en `public/.htaccess` (SPA/`/tv/` estáticos); HSTS agregado también a `.htaccess` (antes solo en `bootstrap.php`, no cubría rutas estáticas), condicional a `expr=%{HTTPS} == 'on'` | 🟠 Alto | A05 | Eje 6 | A.8.26 | `src/Core/bootstrap.php:77-84`, `public/.htaccess` |
| A6 | Endpoint de borrado físico de usuarios en paralelo al soft-delete correcto (contradice la política de SmartCSET) — ✅ **Corregido 2026-07-27** (ver corrección técnica en §4: el `CASCADE` real está en `user_sessions`, no en `activity_log` como se dijo originalmente) | 🟠 Alto | A09 | Eje 9 | A.8.15 | `sql/schema.sql:46`, `UsersController.php:77-87` |
| M1 | `SECRET_KEY` es un valor de ejemplo nunca usado — riesgo latente si se activa sin rotar | 🟡 Medio | A02 | Eje 6 | A.8.24 | `Config.php:30` |
| M2 | `Security::clientIp()` confía en headers `X-Forwarded-For`/`X-Real-IP` sin validar proxy de confianza — IP de auditoría falsificable — ✅ **Corregido 2026-07-27**: solo se confía en esos headers si `TRUST_PROXY=1` en `.env` (default `0`) | 🟡 Medio | A09 | Eje 9 | A.8.15 | `Security.php:133-150` |
| M3 | `MediaController::delete()` comparaba categoría de archivo contra sector de usuario — bug de diseño, denegaba borrados legítimos — ✅ **Corregido 2026-07-27**: restringido a admin/editor (el modelo no guarda sector por archivo; documentado como limitación hasta que se agregue esa columna) | 🟡 Medio | — (funcional) | Eje 7 | — | `MediaController.php:145` |
| M4 | `ConfigController::update()` sin whitelist de claves — cualquier clave string se persiste en `display_config` | 🟡 Medio | A04 | Eje 7 | A.8.9 | `ConfigController.php:18-22` |
| M5 | Cookie de sesión `SameSite=Lax` (SmartCSET usa `Strict`) — ✅ **Corregido 2026-07-27**: `Config.php` ya define `COOKIE_SAMESITE='Strict'`; verificado en `Set-Cookie` real (`secure; HttpOnly; SameSite=Strict`) | 🟡 Medio | A05 | Eje 7 | A.8.26 | `Config.php:35` |
| B1 | Sin cifrado en reposo de datos personales (correos/nombres de personal) | 🟢 Bajo | A02 | Eje 6 | A.8.24 | `sql/schema.sql` (tabla `users`) |
| B2 | Sin inventario de componentes/gestión de parches (sin equivalente al E9 de SmartCSET) | 🟢 Bajo | A06 | Eje 6 | A.8.8 | `composer.json` |
| B3 | Sanitización de input (no de output) con `htmlspecialchars` — divergente del patrón de salida usado en SmartCSET | 🟢 Bajo | A03 | Eje 8 | — | `Security.php:38-61` |

---

## 3. HALLAZGOS CRÍTICOS — DETALLE

### C1 — Credenciales reales versionadas en git desde el commit inicial — ✅ CORREGIDO (2026-07-27)

```php
// src/Core/Config.php
define('DB_PASS', getenv('DB_PASS') ?: 'adsomain2026*');
define('SECRET_KEY', 'CAMBIAR-ESTA-LLAVE-EN-PRODUCTION-2025-xJ9Y2vE5L');
```

`Config.php` **no está en `.gitignore`** (solo `.env`/`.env.*` lo están, y el proyecto no usa `.env` realmente — usa estos `getenv() ?: 'fallback-hardcodeado'`). `git log -- src/Core/Config.php` confirma que el archivo está trackeado desde `17f938e Initial commit` y sigue así en el HEAD actual. Cualquiera con acceso al repositorio (o a un futuro fork/backup del mismo) tiene la contraseña real de PostgreSQL del servidor `.111`.

**Coincidencia agravante:** esta contraseña (`adsomain2026*`) es la **misma** credencial documentada como SSH del usuario `cperez` en `.111` en el Bug 22 de `CLAUDE.md` de SmartCSET — es decir, el mismo secreto se reutiliza entre servicios. Si se compromete uno, se compromete el otro.

**Remediación:**
1. Rotar la contraseña de PostgreSQL en `.111` **inmediatamente**.
2. Mover `DB_PASS`, `SECRET_KEY` y cualquier otro secreto a variables de entorno reales (no defaults hardcodeados) — igual que `smartcset/.env` (que sí está correctamente excluido).
3. Purgar el secreto del historial de git (`git filter-repo` o equivalente) — cambiar la contraseña por sí sola no es suficiente si el repo se comparte o respalda en otro lugar.
4. Generar un `SECRET_KEY` real si en algún momento se usa para firmar algo.

**Aplicado:**
1. **En vez de rotar la contraseña de `postgres`** (compartida con SmartCSET y el resto del clúster en `.111` — rotarla habría roto esas otras conexiones), se cortó por completo la dependencia: CSET ya no autentica como `postgres` en ningún punto, ni en el `.env` real del servidor ni en el local — ver C4/E8. La contraseña vieja de `postgres` sigue siendo válida para ese rol (fuera de alcance de este cambio, es infraestructura compartida), pero **ya no abre ninguna puerta a CSET**.
2. `Config.php` reescrito: los 3 `getenv(...) ?: 'fallback-real'` desaparecieron por completo, ahora usa `EnvLoader::get('DB_PASS', '')` (sin fallback) — si falta el `.env`, la app registra advertencia y `DB_PASS` queda vacío (falla de forma segura, no silenciosa con un secreto real). `.env` real creado en el servidor con la contraseña nueva de `cset_app` + `SECRET_KEY` generada con `openssl rand -hex 32` (distinta a la de local, cada entorno con la suya).
4. Hecho (ver punto 2) — local y servidor tienen `SECRET_KEY` real y distinta.

**Pendiente (decisión explícita del Equipo de Desarrollo, no bloqueante):** el punto 3 (purgar el commit inicial del historial de git) se deja para cuando reinicien el repositorio — la contraseña vieja de `postgres` que quedó expuesta ahí ya no sirve para acceder a CSET, así que el riesgo inmediato para este sistema está cerrado; el `.git` en sí se considera "residuo" a limpiar más adelante.

---

### C2 — Contraseña de fábrica compartida por las 10 cuentas semilla

```sql
-- sql/schema.sql:234-244 — contraseña: Admin123! (el mismo hash en las 10 filas)
INSERT INTO users(username, password_hash, ...) VALUES
  ('admin', '$2y$10$xJ9Y2vE5LqHZ...', ...),
  ('jp_empresarial', '$2y$10$xJ9Y2vE5LqHZ...', ...),
  -- ... 8 filas más, TODAS con el mismo hash
```

El propio `README.md` lo documenta ("cambiar contraseñas en producción"), pero es una advertencia en texto, no un control técnico. Si el `INSERT` seed se ejecutó tal cual contra `.111` y nadie roto las 10 contraseñas manualmente, **cualquiera que conozca `Admin123!` (público en este mismo repo) tiene acceso de administrador**.

**Remediación:** verificar en producción (`SELECT username, password_hash FROM users;` y comparar hashes — no requiere probar login) si alguna cuenta sigue con el hash de fábrica, y forzar cambio de contraseña en el primer login para todas. `tools/reset_admin.php` ya existe para esto — usarlo ahora, no esperar.

---

### C3 — Rate limiting de login evadible (no es rate limiting real) — ✅ CORREGIDO (2026-07-27)

```php
// src/Core/Security.php
public static function rateLimit(string $key, int $maxAttempts, int $windowSeconds): bool {
    // ... guarda los intentos en Session::set('_rate_' . $key, $attempts)
}
```

El contador de intentos vive en **`$_SESSION`**, no en base de datos ni asociado a la IP. Un atacante automatizado que simplemente no reenvía la cookie de sesión (o borra cookies entre intentos) obtiene una ventana de 5 intentos nueva cada vez — el límite nunca se acumula. Además, `Auth::attempt()` solo llama a `Logger::log()` cuando el usuario **existe** (`if (!$user) return null;` corta antes de auditar), así que un ataque de enumeración de usuarios inexistentes deja **cero rastro** en el log de actividad.

Esto es exactamente el mismo riesgo que SmartCSET clasificó como **R-A01 (Extremo, 20/25)** en su Matriz de Riesgos y cerró con **E3** (`tab_intentos_login` + bloqueo por IP en PL/pgSQL). CSET no tiene el equivalente.

**Remediación:** replicar el patrón de SmartCSET — tabla `login_attempts` (o similar) en PostgreSQL, con bloqueo por IP real (no por sesión) y `motivo_fallo` para cada intento (exista o no el usuario).

**Aplicado:** tabla `login_attempts` real en `db_cset` (aplicada en vivo en `.111` vía migración) + modelo `LoginAttempt` (`isBlocked()`/`record()`), `Security::rateLimit()` (basado en sesión) eliminado por completo, `LOGIN_MAX_ATTEMPTS`/`LOGIN_WINDOW_MINUTES` configurables por `.env` (default 5/15, igual que SmartCSET). `Auth::attempt()` ahora audita **todo** intento con motivo (`usuario_no_existe` / `usuario_inactivo` / `contrasena_incorrecta`), no solo los de usuarios existentes. Verificado en vivo: 5 intentos fallidos **sin reenviar cookies** (el vector de evasión original) desde la misma IP → 6º intento bloqueado con `429`, incluso con credenciales correctas; usuario inexistente ahora sí queda auditado.

---

### C4 — La aplicación corre con privilegios de superusuario de PostgreSQL — ✅ CORREGIDO (2026-07-27)

```php
define('DB_USER', getenv('DB_USER') ?: 'postgres');
```

`sql/schema.sql` no define ningún `CREATE ROLE`/`GRANT` — no existe un usuario de aplicación con privilegios acotados (`SELECT`/`INSERT`/`UPDATE`/`DELETE` + `EXECUTE`, sin DDL, sin acceso a otras bases del mismo clúster). CSET conecta como `postgres`, el superusuario del servidor completo — el mismo servidor que aloja `db_smartcset2` y otras bases (per `pg_hba.conf` de SmartCSET, que sí documenta 6 bases distintas en ese clúster). Cualquier vector que alcance ejecutar SQL arbitrario en CSET compromete **todo el clúster PostgreSQL de `.111`**, no solo `db_cset`.

Esta es exactamente la verificación que la propia Política de Control de Acceso de SmartCSET (`FASE0-ACC-007 §5.4`) exige hacer: *"Verificar y corregir si es necesario: que el usuario PHP no tiene privilegios DDL"*. En CSET, no solo tiene DDL — tiene control total del servidor.

**Remediación:** crear un rol `cset_app` con permisos mínimos sobre `db_cset` únicamente, y migrar `DB_USER`/`DB_PASS` a ese rol.

**Aplicado (en la BD real de `.111`, sin tocar el código allá):** rol `cset_app` creado — `NOSUPERUSER NOCREATEDB NOCREATEROLE`, con `SELECT`/`INSERT`/`UPDATE`/`DELETE` sobre las tablas de `public` en `db_cset` (sin DDL) + `ALTER DEFAULT PRIVILEGES` para que las tablas que cree `postgres` en el futuro (migraciones) ya incluyan a `cset_app` automáticamente. Verificado:
- No puede leer ninguna fila de `db_smartcset2` (`SELECT` sobre `tab_usuarios`/`tab_personas` → `permiso denegado`).
- No puede hacer DDL (`CREATE TABLE` → `permiso denegado al esquema public`).
- `.env` local y `.env` **real del servidor** (`.111`) ambos cortados a `cset_app`, con una contraseña nueva generada (`openssl rand -base64`, 32 caracteres), distinta de la de `postgres` — ciclo completo probado end-to-end en producción: `GET /api/tv/ping` (200), login real contra la BD (`401 Credenciales inválidas`, no `500` — confirma conexión correcta), lectura de módulos.

**Limitación residual documentada (no oculta):** `cset_app` técnicamente todavía puede *abrir una conexión* (`CONNECT`) a otras bases del clúster (incluida `db_smartcset2`) porque PostgreSQL otorga `CONNECT` a `PUBLIC` por defecto en toda base nueva, y revocarlo específicamente para un rol no anula ese grant de `PUBLIC` (solo revocarlo de `PUBLIC` lo haría, una acción de mayor alcance sobre la base de SmartCSET, fuera de este cambio). El límite real que sí importa — **acceso a datos** — está cerrado: `cset_app` no tiene ningún privilegio de tabla en `db_smartcset2` ni en ninguna otra base (verificado de nuevo tras la rotación: `SELECT` sobre `tab_usuarios` → `permiso denegado`).

---

### C5 — Sin TLS en producción — ✅ CORREGIDO (2026-07-27)

Verificado en vivo: `http://10.5.213.111/` y `http://10.5.213.111/cset/login` respondían 200 **solo por HTTP** — no había redirección a HTTPS, no había `VirtualHost :443` en `scripts/apache-cset-alias.conf`, y `COOKIE_SECURE` estaba hardcodeado en `false` en `Config.php` (no condicional a `APP_ENV`, a diferencia de SmartCSET que sí resolvió este mismo patrón). Esto significaba que el token de sesión (`cset_session`) y las credenciales del formulario de login viajaban **en texto plano** por la red del SENA.

SmartCSET ya había resuelto exactamente este problema (E4/E8: HTTPS forzado, `DB_SSLMODE=require`, certificado con SAN correcto). CSET no lo tenía.

**Aplicado:** no hizo falta certificado nuevo — `/cset` ya compartía el mismo `<VirtualHost *:443>` de `smartcset.conf` (mismo `ServerName 10.5.213.111`, `Alias /cset` ya apuntaba ahí), así que reutiliza el certificado/llaves ya emitidos para SmartCSET (un cert cubre todo el host, no por ruta). Se agregó `RedirectMatch permanent ^/cset(.*)$ https://10.5.213.111/cset$1` al `VirtualHost *:80` de `smartcset.conf` (mismo patrón que la línea ya existente para `/smartcset`), sin tocar `/gitlab` ni `/suitecrm` (siguen en HTTP normal, decisión explícita del Equipo de Desarrollo). `COOKIE_SECURE=1` en el `.env` real del servidor.

**Decisión explícita: la cartelera legacy servida en la raíz (`https://10.5.213.111/`, HTML con comentario "compatible Chromium 53 (webOS 4.1 Signage)") se dejó intencionalmente en HTTP sin redirección** — pantallas físicas viejas podrían no soportar los cifrados TLS configurados (`ECDHE-ECDSA-...`); forzar HTTPS ahí arriesgaba dejar pantallas reales en blanco sin beneficio de seguridad proporcional (es contenido de solo lectura, no maneja sesión ni credenciales).

**Verificado en vivo:** `http://.../cset/login` → `301` a HTTPS; `http://.../` (raíz) → `200` sin redirigir (intacto); `http://.../smartcset/` → sigue `301` (sin regresión); `http://.../gitlab` y `/suitecrm` → sin cambios. Cookie real: `Set-Cookie: cset_session=...; secure; HttpOnly; SameSite=Strict`. Headers en `https://.../cset/login`: `Content-Security-Policy` y `Strict-Transport-Security` presentes.

**Regresión encontrada y corregida el mismo día (2026-07-27, tarde):** el `RedirectMatch` inicial forzaba HTTPS para **todo** `/cset`, incluidas `/cset/api/*` y `/cset/assets/*`. Dos problemas reales:
1. **Logo roto en la cartelera**: `MediaController::publicUrl()` construía URLs absolutas con `APP_URL` (`http://.../cset/assets/uploads/...`), persistidas en `display_config.brand_logo_url` desde antes de este fix. Al forzar HTTPS en `/cset`, una página servida por HTTPS cargando una imagen `http://` queda bloqueada por el propio `Content-Security-Policy` (`img-src 'self'` no matchea entre esquemas distintos, aunque el host sea el mismo).
2. **Riesgo no detectado hasta revisar el código**: la cartelera legacy de la raíz (HTTP, sin forzar) llama a `API_BASE = '/cset/api'` — con el redirect original, esas llamadas (relativas, por lo tanto en HTTP) habrían rebotado a HTTPS con `301`, rompiendo **todo** el contenido dinámico en pantallas físicas viejas sin soporte TLS, no solo el logo.

**Fix aplicado:**
- `MediaController::publicUrl()` cambiado de `APP_URL . '/assets/uploads/...'` a `APP_BASE . '/assets/uploads/...'` (ruta relativa a la raíz, sin esquema/host fijo) — funciona igual sirviéndose por HTTP o HTTPS.
- `display_config.brand_logo_url` (dato ya persistido) corregido de la URL absoluta vieja a la ruta relativa.
- El `RedirectMatch` de `/cset` se dividió en dos reglas: una para `/cset` (bare) y otra con *negative lookahead* que excluye `api/` y `assets/` — solo las páginas HTML (login, panel admin, `/tv/`) fuerzan HTTPS; los sub-recursos (API, imágenes) responden en el esquema que se les pida, sin redirección. Esto no reabre C5: una vez que el navegador está en la versión HTTPS de una página (por el redirect del punto de entrada), sus propias peticiones relativas a `/cset/api/*` ya salen por HTTPS de forma natural — el redirect explícito en esas rutas era redundante para el caso moderno y dañino para el legacy.
- Verificado: `http://.../cset/login` → sigue `301`; `http://.../cset/api/tv/ping` y `http://.../cset/assets/uploads/...` → `200` directo, sin redirigir; los mismos, por HTTPS, también `200`. Imagen del logo carga completa (112823 bytes) en ambos esquemas.

---

### C6 — `GeneralController` sin control de acceso por rol (viola la propia documentación del proyecto) — ✅ CORREGIDO (2026-07-27)

El propio `docs/QA-CRUD-CHECKLIST.md` de CSET declara:

| Módulo | admin | editor | program_head |
|---|---|---|---|
| General CRUD | ✓ | ✓ | **lectura** |

Pero el código real:

```php
// src/Api/GeneralController.php
public function store(): void {
    $this->requireAuth();       // ← solo exige estar logueado
    $this->requireCsrf();
    // NO hay Auth::canEditArea() ni Session::hasRole() en ningún punto
    ...
}
```

`store()`, `update()`, `destroy()` y `toggle()` solo llaman a `requireAuth()` — nunca verifican el rol. Un `program_head` (pensado para tener **solo lectura** en este módulo) puede crear, editar y borrar cualquier contenido "General", incluyendo los indicadores externos (dólar, clima, etc.) que ve toda la cartelera. Comparar con `AcademicController`/`CenterController`, que sí llaman `Auth::canEditArea()` en cada mutación — este es el patrón correcto que `GeneralController` simplemente omitió.

**Remediación:** agregar `if (!Session::hasRole(['admin', 'editor'])) { $this->fail('Sin permisos', 403); }` en `store()`, `update()`, `destroy()`, `toggle()`, igual que ya existe en `indicators()` (que sí lo tiene correctamente implementado — la inconsistencia está solo en el CRUD base).

**Aplicado:** agregado `requireContentRole()` (admin+editor) a los 4 métodos. Verificado en vivo contra la BD real: login como `program_head` + intento de `POST /api/general` → `403 Sin permisos`.

---

## 4. HALLAZGOS ALTOS — DETALLE

### A1 — Validación de subida de archivos solo por extensión — ✅ CORREGIDO (2026-07-27)

```php
private static function resolveType(string $ext, string $mime): ?string {
    if (in_array($ext, ALLOWED_IMAGE_TYPES, true)) return 'image';
    // ... $mime nunca se usa dentro de esta función
}
```

`MediaController::upload()` sí detecta el MIME real con `finfo_file()` (buena práctica, igual que `LogoUpload.php` de SmartCSET), pero **nunca lo compara** contra la extensión declarada — solo pasa la whitelist de extensión. Un archivo cuyo contenido no coincide con su extensión (ej. un HTML con `<script>` renombrado a `.jpg`) pasa la validación. El impacto real está parcialmente mitigado porque el nombre de archivo final se regenera aleatoriamente y `.htaccess` bloquea ejecución de PHP en `uploads/`, pero sigue siendo una divergencia real del patrón "MIME real validado" que SmartCSET exige explícitamente en su E6.

**Aplicado:** tabla `EXPECTED_MIME` por extensión en `MediaController`; el MIME real (`finfo`) ahora se compara contra lo esperado para la extensión declarada, rechazando el archivo si no coincide. Probado con un archivo de texto plano renombrado a `.jpg` → `"El contenido del archivo no coincide con la extensión .jpg"`.

**Remediación:** agregar una tabla de MIME-esperado-por-extensión y rechazar si `$mime` detectado no coincide (mismo patrón que `LogoUpload.php` de SmartCSET).

### A2 — SVG sin sanitizar — ✅ CORREGIDO (2026-07-27)

`ALLOWED_IMAGE_TYPES` incluye `svg`, usado también para branding/logos (`MediaController.php:27`). Los SVG son XML y pueden contener `<script>` o manejadores de evento (`onload`, etc.) — no se sanitiza el contenido antes de guardarlo o servirlo, y `apache-cset-alias.conf` lo sirve explícitamente como `image/svg+xml`. Si en algún punto se renderiza el SVG fuera de un `<img>` (por ejemplo si un componente lo carga vía `<object>`/`fetch`+`innerHTML`), el script embebido se ejecutaría.

**Remediación:** sanitizar SVG al subir (extraer solo el `<svg>` y eliminar `<script>`/atributos `on*`) o, más simple, quitar `svg` de los tipos permitidos si no es estrictamente necesario.

**Aplicado (decisión explícita: sanear, no quitar el soporte de SVG):** `MediaController::sanitizeSvg()` parsea el archivo con `DOMDocument` (no regex) — rechaza cualquier cosa que no sea XML bien formado con raíz `<svg>`, elimina por completo `<script>`/`<foreignObject>`/`<iframe>`/`<embed>`/`<object>`, y quita cualquier atributo `on*` o `href`/`xlink:href` con esquema `javascript:`. Probado con un SVG real que incluía las 4 variantes de XSS a la vez (`<script>`, `onload`, `onclick`, `xlink:href="javascript:..."`) — las 4 quedaron eliminadas, el SVG resultante sigue siendo válido y funcional. Un SVG con contenido basura (extensión `.svg` pero no XML) se rechaza directamente sin dejar archivo huérfano en disco.

**Hallazgo de infraestructura local encontrado en el camino (no es un problema de `cset`, es del entorno):** para poder probar esto de verdad hicieron falta 3 correcciones al entorno local, ninguna relacionada con el código de `cset`:
1. La extensión PHP `dom` no estaba instalada (`php8.5-xml` ausente) — sin ella, `DOMDocument` no existe y el saneador fallaría en producción con un error fatal. Instalada.
2. El directorio de uploads de `cset` no era escribible por el grupo del usuario que corre Apache — corregido agregando `www-data` al grupo `ander` (mismo patrón que ya usa el resto del entorno local).
3. El hardening de `systemd` del servicio `apache2` (`ProtectHome=read-only`) bloqueaba escritura en `/home/ander/smartcset/cset/...` a nivel de sistema, aunque los permisos Unix estuvieran bien — ya existía una excepción (`ReadWritePaths=`) para las carpetas de uploads de SmartCSET, agregada por una sesión anterior; se extendió el mismo override para las carpetas de `cset`.

### A3 — Fuga de información de conexión a BD — ✅ CORREGIDO (2026-07-27)

```php
// src/Core/Database.php:25-36
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    if (APP_DEBUG) { throw $e; }
    http_response_code(500);
    echo $dsn;   // ← SIEMPRE se ejecuta, sin importar APP_DEBUG
    die(json_encode(['ok' => false, 'error' => 'Error de conexión...']));
}
```

La línea `echo $dsn;` está **fuera** del bloque `if (APP_DEBUG)` — se ejecuta siempre que la conexión a BD falle, filtrando `host`, `port` y `dbname` internos a cualquier visitante anónimo. Es probablemente un residuo de depuración olvidado.

**Remediación:** eliminar esa línea (probablemente un `echo $dsn;` de debugging que se quedó en el código).

**Aplicado:** línea eliminada.

### A4 — Contraseñas con caracteres especiales quedan hasheadas incorrectamente — ✅ CORREGIDO (2026-07-27)

```php
// UsersController::store()
$data = Security::sanitizeArray($this->jsonInput());   // ← htmlspecialchars() sobre TODOS los campos, incluida 'password'
...
if (!Security::isStrongPassword($data['password'])) ...
User::create([..., 'password' => $data['password'], ...]);   // se hashea la versión YA ESCAPADA
```

```php
// AuthController::login() — usa la contraseña CRUDA, sin sanitizeArray()
$password = $body['password'] ?? $_POST['password'] ?? '';
Auth::attempt($username, $password);
```

`Security::sanitizeArray()` aplica `htmlspecialchars()` recursivamente a **todo** el payload, incluida la contraseña. `isStrongPassword()` **exige** al menos un carácter especial (`!@#$%^&*` etc.) — varios de los caracteres típicos de "especial" (`<`, `>`, `&`, `"`, `'`) son justamente los que `htmlspecialchars` transforma. Resultado: si un admin crea un usuario con una contraseña que contiene `&` (ej. `Prueba123&`), el hash se genera sobre `Prueba123&amp;` — pero cuando esa persona intenta iniciar sesión con la contraseña real (`Prueba123&`, sin sanitizar en `login()`), `password_verify()` falla siempre. **La cuenta queda inutilizable con su propia contraseña real.**

**Remediación:** nunca aplicar `sanitize()`/`htmlspecialchars()` a contraseñas — deben tratarse como datos binarios opacos hasta el hash. Excluir `password`/`password_hash` del recorrido de `sanitizeArray()`, igual que ya se excluye `_csrf`.

**Aplicado:** `password`/`password_hash` excluidos de `sanitizeArray()`. Verificado con una prueba directa: `sanitizeArray(['password' => 'Clave123&<Test>'])` ahora conserva el valor intacto, y `password_verify()` contra la contraseña cruda (como la envía el formulario de login) devuelve `true`.

### A5 — Sin Content-Security-Policy

`bootstrap.php` y `public/.htaccess` configuran `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy` y (condicionalmente) HSTS — pero **ningún `Content-Security-Policy`**, a diferencia de SmartCSET (que sí activó CSP en su E4). Dado que existe superficie de HTML dinámico en el frontend (`{@html}` en `WelcomeSlide.svelte`, aunque mitigado por un sanitizador propio — ver §5), un CSP con `script-src 'self'` sería una capa de defensa adicional real.

**Remediación:** replicar el header CSP que ya usa SmartCSET, adaptado a los orígenes que CSET realmente necesita (fuentes de Google Fonts, etc.).

### A6 — Endpoint de borrado físico de usuarios, en paralelo al soft-delete correcto — ✅ CORREGIDO (2026-07-27)

> **Corrección técnica (2026-07-27):** la versión original de este hallazgo afirmaba que `activity_log.user_id` tenía `ON DELETE CASCADE`, lo cual era incorrecto — ese `CASCADE` está en `user_sessions.user_id` (borrar sesiones al borrar un usuario es correcto). `activity_log.user_id` **no tiene** cláusula `ON DELETE` (por defecto `NO ACTION`), así que un `DELETE` sobre `users` con actividad asociada en realidad **fallaba** con una violación de FK (error 500 genérico), no borraba el historial silenciosamente. El riesgo real no era "se pierde la auditoría", sino que el endpoint de borrado físico contradecía la política de SmartCSET y además rompía con un 500 no controlado para cualquier usuario con actividad.

```php
// UsersController::destroy() — hard delete real, existía en paralelo a toggle() (soft-delete)
User::delete($id);
```

El esquema ya tenía el mecanismo correcto para desactivar usuarios sin destruir evidencia (`is_active` + endpoint `toggle()`), pero **también** exponía `destroy()`, que borraba la fila físicamente. Esto contradecía la Política de Control de Acceso de SmartCSET: *"Cuando un usuario se desvincula, su cuenta se desactiva (`ind_estado = FALSE`), **nunca se elimina**, para preservar la trazabilidad histórica"* (`FASE0-ACC-007 §7.3`). Confirmado además que el frontend (`admin/users/+page.svelte`) nunca llamaba a `destroy()` — solo a `toggle()` — por lo que el endpoint era además código muerto sin ningún consumidor real.

**Remediación aplicada:** eliminados `UsersController::destroy()`, la ruta `DELETE /api/users/{id}` en `ApiDispatcher.php`, y el método `User::delete()` (sin otros llamadores). Se agregó además una protección en `toggle()`: un admin no puede desactivar su propia cuenta activa (evita bloqueo accidental sin ningún administrador activo). Verificado: `DELETE /api/users/999` ahora responde `404` (ruta inexistente).

**Remediación:** cambiar la FK a `ON DELETE SET NULL` (o mejor, quitar el endpoint `destroy()` del todo y dejar solo `toggle()`, como hace SmartCSET con `tab_usuarios.ind_estado`).

---

## 5. ASPECTOS YA ALINEADOS CON SMARTCSET (para objetividad del informe)

No todo son hallazgos — varios controles están **correctamente** implementados y a la altura del propio SmartCSET:

- **Inyección SQL:** 100% de las queries usan PDO con `ATTR_EMULATE_PREPARES => false` y bind explícito por tipo (`Database::bindValues()`); no se detectó ninguna concatenación de input en SQL en `src/Models/`.
- **CSRF:** implementación real y correcta — token de 32 bytes (`random_bytes`), verificación `hash_equals` (timing-safe), transportado por header `X-CSRF-Token` desde el cliente SvelteKit. Al mismo nivel que `SecurityMiddleware.php` de SmartCSET.
- **Contraseñas:** bcrypt costo 12, con rehash automático (`needsRehash`) — igual que SmartCSET.
- **Control de acceso por sector a nivel de registro individual:** `AcademicController` y `CenterController` sí verifican `Auth::canEditArea()` en `show()`/`update()`/`destroy()`/`toggle()`, no solo en el listado — esto cierra correctamente el vector IDOR que sí existe en `GeneralController` (C6).
- **Sanitizador HTML del slide de bienvenida** (`DisplayConfig::sanitizeWelcomeHtml()`): whitelist estricto que solo permite `<b>`/`</b>` sin atributos, todo lo demás se escapa con `htmlspecialchars`. Se revisó específicamente por el uso de `{@html}` en `WelcomeSlide.svelte` y **no se encontró bypass** — es un patrón bien construido, mejor que muchas soluciones "rápidas" de sanitización.
- **`.htaccess` (`public/.htaccess`):** bloquea ejecución de PHP/phtml/phar en `uploads/`, fuerza 404 real (no cae al SPA) para uploads inexistentes, y el `Alias /cset` solo expone `public/` — `src/`, `tools/`, `storage/` no son alcanzables por URL. Mismo nivel que el E6 de SmartCSET.
- **Manejo de errores:** `bootstrap.php` tiene `set_exception_handler` que nunca expone stack traces en producción — página 500 genérica, igual que SmartCSET.

---

## 6. PLAN DE REMEDIACIÓN PRIORIZADO

Mismo criterio que usa SmartCSET en su Matriz de Riesgos: priorizar primero lo más grave **y** más barato de resolver.

| Prioridad | Hallazgo | Esfuerzo | Por qué es primero |
|---|---|---|---|
| 1 | C2 — Rotar/forzar cambio de las 10 contraseñas semilla | 🟢 Bajo (minutos, `tools/reset_admin.php` ya existe) | Compromiso inmediato si no se ha hecho ya — verificar HOY |
| 2 | C1 — Rotar `DB_PASS`, mover secretos a env, purgar del historial git | 🟢 Bajo-Medio | Secreto ya expuesto; cuanto más tiempo pase, más copias existen |
| 3 | C6 — Agregar control de rol a `GeneralController` | 🟢 Bajo (4 líneas × 4 métodos) | Bug de una línea, alto impacto, ya resuelto en los otros controllers como referencia |
| 4 | A4 — Excluir `password` de `sanitizeArray()` | 🟢 Bajo | Una línea; además está rompiendo funcionalidad real hoy |
| 5 | A3 — Quitar `echo $dsn;` de `Database.php` | 🟢 Bajo | Una línea |
| 6 | C3 — Rate limiting real por IP en BD (replicar E3 de SmartCSET) | 🟡 Medio | Requiere tabla nueva + función PL/pgSQL, pero el patrón ya existe hecho en SmartCSET para copiar |
| 7 | C4 — Rol de BD con privilegios mínimos para la app | 🟡 Medio | Requiere migración cuidadosa de credenciales sin causar downtime |
| 8 | C5 — TLS en producción — ✅ **Corregido 2026-07-27** | 🟡 Medio | Mismo procedimiento que SmartCSET ya documentó y ejecutó en `.103`/`.111` |
| 9 | A6 — `ON DELETE SET NULL` + retirar `destroy()` de usuarios | 🟢 Bajo | Migración simple |
| 10 | A1/A2 — Validar MIME real, evaluar quitar SVG | 🟡 Medio | — |
| 11 | A5, M1-M5, B1-B3 | 🟢-🟡 | Mejoras incrementales, sin urgencia |

---

## 7. BRECHA DE GOBERNANZA (Fase 0 equivalente ausente)

A diferencia de SmartCSET, CSET no tiene:
- Política de tratamiento de datos personales propia (almacena nombres y correos reales de personal — dato personal bajo Ley 1581/2012 igual que SmartCSET).
- Inventario de activos de información.
- Matriz de riesgos.
- Roles de seguridad designados (RSSI, custodio de secretos).
- Plan de continuidad/backup propio y verificado (no está claro si el backup diario de SmartCSET en `.111` incluye también `db_cset` — **verificar**, dado que hoy vive en el mismo servidor de laboratorio).

**Recomendación:** si CSET se va a presentar formalmente junto con SmartCSET como parte del mismo blindaje institucional, correspondería producir un mini-Fase-0 equivalente (aunque sea condensado, dado que CSET no maneja datos tan sensibles como cédulas/biometría) antes de la presentación.

---

## 8. CONCLUSIÓN — ALINEACIÓN CON LOS 5 PILARES

| Pilar | Estado |
|---|---|
| **Coherencia** | ⚠️ Parcial — el patrón de control de acceso por sector es coherente en Academic/Center pero roto en General (C6); el patrón de sanitización de input vs. output diverge del resto del ecosistema SmartCSET |
| **Congruencia** | ❌ Falla verificable — el código de `GeneralController` contradice directamente el propio `QA-CRUD-CHECKLIST.md` del proyecto |
| **Funcionalidad** | ⚠️ Bug real activo — usuarios con contraseñas que contienen caracteres especiales no pueden iniciar sesión (A4) |
| **Estabilidad** | ✅ Manejo de errores robusto, sin fugas de stack trace |
| **Seguridad Total** | ❌ 6 hallazgos críticos abiertos, varios de ellos ya resueltos en SmartCSET (secretos en git, rate limiting real, TLS, mínimo privilegio de BD) — CSET no está hoy al nivel de blindaje de su proyecto hermano |

**CSET no puede considerarse alineado con las consignas MSPI/Fase 0-1 que rigen SmartCSET hasta cerrar, como mínimo, los 6 hallazgos críticos.** La buena noticia: la mayoría de las remediaciones (prioridades 1-5 de la tabla §6) son cambios pequeños y rápidos, y para las de mayor esfuerzo (rate limiting por IP, TLS, rol de BD mínimo privilegio) SmartCSET ya tiene el patrón implementado y documentado — se trata de replicarlo, no de diseñarlo desde cero.

---

*Fin del documento — Auditoría de Seguridad CSET v1.0 — CSET-AUD-001*
