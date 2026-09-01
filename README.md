# CSET :: Cartelera Digital

Sistema de cartelería digital para Institución de Educación Superior. Contenido en Smart TVs / monitores Signage 24/7, gestionado desde un **panel PHP clásico** sobre API PHP + PostgreSQL.

Base URL típica: `http://<servidor>/tv/` (Alias Apache `/tv` → `public/`; un solo `.htaccess` en `public/`).

**Estructura actual:** ver [`AGENTS.md`](AGENTS.md). El panel operativo es PHP en `public/admin/`.

| URL | Uso |
|-----|-----|
| `/tv/` | Cartelera Signage (`public/index.php`) |
| `/tv/login.php` | Login del panel |
| `/tv/admin/dashboard.php` | Dashboard |
| `/tv/api/...` | API REST |

**Agentes / IA:** mapa operativo en [`AGENTS.md`](AGENTS.md).

---

## Características principales

- **3 escenarios rotativos:**
  - **Información académica** — Programación, ofertas, anuncios
  - **Nuestro centro** — Sectores: Empresarial, Salud, Tecnología, Hotelería, Turismo, Barismo, Gestión de Eventos
  - **Información general** — Noticias, eventos, indicadores (dólar, café, petróleo, clima, deportes, etc.)
- **Polling** configurable (por defecto ~30 s; modo turbo tras cambios)
- **Auto-rotación** de escenarios desde configuración
- **Panel admin** (PHP) con roles: admin, jefe de programa, editor
- **Multimedia:** imágenes, video, audio, PDF
- **Datos externos** vía cron (dólar, cripto, clima, etc.)
- **Tema claro/oscuro** y branding configurable
- **Cartelera de producción** compatible con LG Signage (webOS / Chromium 53)

---

## Arquitectura

| Capa | Ubicación | Notas |
|------|-----------|--------|
| Panel admin | `public/admin/` | PHP clásico; CSS en `public/assets/css/panel.css` |
| Cartelera TV (producción) | `public/index.php` + `public/signage/` | HTML/JS ES5-safe para LG Signage / Chromium 53 |
| API REST | `public/api/` + `src/Api/` | PHP 8 |
| Backend | `src/` | Core, Models, Controllers |
| Base de datos | PostgreSQL 12+ | Schema en `sql/` |
| API REST | `public/api/index.php` | PHP 8, JSON, sesión + CSRF |
| Backend | `src/` | Core, Api, Models (fuera del Alias web) |
| BD | PostgreSQL | Schema en `sql/schema.sql` |

### URLs públicas (sin `/public`)

| Ruta | Uso |
|------|-----|
| `/tv/login.php` | Acceso al panel |
| `/tv/admin/dashboard.php` | Dashboard admin |
| `/tv/admin/*` | CRUD académico, centro, general, usuarios, config, media |
| `/tv/` | Cartelera de producción (Signage / kiosko) |
| `/tv/api/...` | API REST |

Bookmarks antiguos `/cset/*` reciben **301** a `/tv/*` (`/cset/tv/` → `/tv/`).

En despliegues con DocumentRoot del servidor en `/var/www/html`, el build puede copiar la cartelera a `http://<IP>/` (Play via URL en el monitor).

---

## Requisitos

- **PHP** 8.0+ (8.2+ recomendado)
- **PostgreSQL** 12+
- **Apache** 2.4+ con `mod_rewrite` (o Nginx equivalente)
- **Node.js** 18+ (solo para build del frontend)
- Extensiones PHP: `pdo_pgsql`, `fileinfo`, `mbstring`, `openssl`; `gd` o `imagick` recomendado
- Escritura en `public/assets/uploads/` y `storage/logs/`

---

## Instalación

### 1. Código en el servidor

```bash
# Ejemplo Linux
# /var/www/html/cset/
```

### 2. Base de datos

**Opción A — instalador CLI** (recomendado):

```bash
php tools/install.php
```

**Opción B — manual:**

```bash
psql -U postgres
```

```sql
CREATE DATABASE db_cset WITH ENCODING 'UTF8';
-- Crear usuario de app y GRANT según tu política
\q
```

```bash
psql -U postgres -d db_cset -f sql/schema.sql
```

Usuarios semilla en el schema (cambiar contraseñas en producción). Hash de ejemplo corresponde a `Admin123!` — regenerar:

```bash
php -r "echo password_hash('TuPasswordSeguro!', PASSWORD_BCRYPT);"
```

```sql
UPDATE users SET password_hash = '<HASH>' WHERE username = 'admin';
```

O:

```bash
php tools/reset_admin.php
```

### 3. Configuración

Editar `src/Core/Config.php` (también admite variables de entorno: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, etc.):

```php
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'db_cset');
define('DB_USER',    getenv('DB_USER')    ?: 'postgres');
define('DB_PASS',    getenv('DB_PASS')    ?: 'TuPasswordSeguro!');
define('APP_URL',    getenv('APP_URL')    ?: 'http://localhost/tv');
define('APP_BASE',   '/tv');
define('SECRET_KEY', 'CAMBIAR-ESTA-LLAVE-EN-PRODUCTION');
```

### 4. Permisos

```bash
sudo chown -R www-data:www-data public/assets/uploads storage/logs
sudo chmod -R 775 public/assets/uploads storage/logs
```

### 5. Apache (`.htaccess`)

```bash
bash scripts/build.sh   # copia scripts/htaccess.public → public/.htaccess
```

Dependencias PHP de desarrollo (tests):

```bash
composer install
```

### 6. Apache (Alias → `public/`)

`/tv` debe apuntar a la carpeta `public/` (mismas URLs; `src/`, `tools/`, `storage/` quedan fuera de la web).

```bash
sudo bash scripts/enable-cset-alias.sh
```

Eso instala el Alias, recarga Apache y deja **un solo** `.htaccess`:

| Archivo | Rol |
|---------|-----|
| `public/.htaccess` | API + SPA + bloqueo PHP en uploads + MIME/caché/headers |

Plantilla canónica: `bash/htaccess.public` (también `bash/apache-cset-alias.conf`).

### 7. Cron (opcional)

```bash
*/30 * * * * /usr/bin/php /var/www/html/cset/src/cron/refresh_external.php
```

---

## Uso

### Panel admin

```
http://localhost/tv/login
```

Credenciales semilla (cambiar en producción):

| Usuario | Rol |
|---------|-----|
| `admin` | Administrador |
| `jp_empresarial`, `jp_salud`, `jp_tecnologia`, … | Jefe de programa |
| `comunicaciones` | Editor (contenido general) |

### Cartelera en monitores

| Entorno | URL |
|---------|-----|
| Producción (LG Signage / Chromium antiguo) | `http://<IP>/tv/` |
| Navegador / pruebas | `http://<IP>/tv/` |

Usar **HTTP** en Signage si el certificado HTTPS es autofirmado.

Kiosko Chrome/Chromium (ejemplo):

```text
chrome.exe --kiosk --noerrdialogs --disable-infobars --no-first-run --start-fullscreen "http://<IP>/tv/"
```

---

## Roles y permisos

| Rol | Permisos |
|-----|----------|
| **admin** | Usuarios, configuración, todo el contenido |
| **program_head** | Contenido de su sector (`program_area`) |
| **editor** | Contenido general e indicadores |

---

## API REST (resumen)

Autenticación por sesión + CSRF (`X-CSRF-Token`).

| Recurso | Descripción |
|---------|-------------|
| `/api/auth/login\|logout\|me\|csrf` | Sesión |
| `/api/tv`, `/api/tv/ping` | Feed cartelera + health |
| `/api/academic`, `/api/center`, `/api/general` | CRUD contenido |
| `/api/general/indicators` | Indicadores |
| `/api/media` | Biblioteca de medios |
| `/api/users`, `/api/config`, `/api/dashboard` | Admin |
| `/api/branding`, `/api/meta` | Branding y meta |

---

## Estructura del proyecto

```
cset/
├── public/                   # DocumentRoot / Alias /tv
│   ├── index.php             # Cartelera
│   ├── login.php
│   ├── admin/                # Panel PHP
│   ├── api/                  # REST (index.php)
│   ├── signage/              # Player ES5
│   └── assets/
├── src/                      # Backend PHP
│   ├── Core/
│   ├── Views/
│   ├── Api/
│   ├── Controllers/
│   └── Models/
├── sql/
├── cron/
├── storage/
├── tests/
├── scripts/
├── tools/
├── docs/
├── composer.json
└── README.md
```

Tras el Alias, el único `.htaccess` es `public/.htaccess`.

Mantenimiento CLI (bloqueado por HTTP):

```bash
php tools/install.php
php tools/seed_branding.php
php tools/seed_indicators.php
php tools/reset_admin.php
```

---

## Tests

```bash
composer test                         # PHPUnit
```

Checklist manual: [docs/QA-CRUD-CHECKLIST.md](docs/QA-CRUD-CHECKLIST.md)

---

## Seguridad

- Contraseñas bcrypt (cost 12)
- CSRF en formularios / API mutativa
- Sesiones HttpOnly, SameSite, regeneración
- Rate limiting en login
- Sanitización XSS; PDO prepared statements
- Validación MIME y bloqueo de PHP en uploads
- Headers de seguridad HTTP
- Auditoría en `activity_log`
- Roles por sector; `tools/`, `src/`, `sql/`, etc. inaccesibles por URL

---

## Monitoreo

- Dashboard: `/tv/admin/dashboard`
- Errores PHP: `storage/logs/php-errors.log`
- Cron: `storage/logs/external-refresh.log`
- Actividad: tabla `activity_log`

---

## Documentación adicional

| Doc | Contenido |
|-----|-----------|
| [AGENTS.md](AGENTS.md) | Mapa para agentes: dónde tocar qué, restricciones, verificación |
| [docs/superpowers/specs/2026-07-15-project-structure-design.md](docs/superpowers/specs/2026-07-15-project-structure-design.md) | Estructura de carpetas / Alias Apache |
| [docs/superpowers/specs/2026-07-14-tv-legacy-signage-design.md](docs/superpowers/specs/2026-07-14-tv-legacy-signage-design.md) | Cartelera Signage `/tv` |
| [docs/superpowers/specs/2026-07-14-dashboard-panel-design.md](docs/superpowers/specs/2026-07-14-dashboard-panel-design.md) | Dashboard admin |
| [docs/QA-CRUD-CHECKLIST.md](docs/QA-CRUD-CHECKLIST.md) | QA manual CRUD |
| `sql/schema.sql` | Esquema BD |
| `cron/refresh_external.php` | APIs externas |
| `src/Core/Config.php` | Constantes y env |

Skills Cursor: ver [`AGENTS.md`](AGENTS.md).

---

## Licencia

Uso interno de la Institución.

**Stack:** PHP 8 · PostgreSQL 12+ · Apache 2.4  
**Despliegue:** base path `/tv` vía Alias Apache → `public/` (un `.htaccess`)
