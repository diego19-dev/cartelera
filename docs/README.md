# CSET :: Cartelera Digital

Sistema de cartelería digital para Institución de Educación Superior. Contenido en Smart TVs / monitores Signage 24/7, gestionado desde un **panel PHP clásico** sobre API PHP + PostgreSQL.

Base URL típica: `http://<servidor>/tv/` (Alias Apache `/tv` → raíz del repo; un solo `.htaccess`).

El panel está en `src/panel_*.php` (URLs públicas siguen siendo `/tv/admin/...`).

| URL | Uso |
|-----|-----|
| `/tv/` | Cartelera Signage (`index.php` en la raíz) |
| `/tv/login.php` | Login del panel |
| `/tv/admin/dashboard.php` | Dashboard |
| `/tv/api/...` | API REST |

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
| Panel | `src/panel_*.php` | Nombres en español; CSS/JS en `css/`, `js/` |
| Cartelera | `index.php` + `src/cartelera.html` | Player ES5 (LG Signage) |
| API | `src/api.php` + controladores en `src/` | PHP 8 |
| Código | `src/` (solo archivos) | Sin subcarpetas |
| BD | PostgreSQL | `scripts/sql/` |
| Herramientas | `scripts/` | Apache, desarrollo local, SQL |

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
- Escritura en `img/uploads/` y `logs/`

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
psql -U postgres -d db_cset -f scripts/sql/schema.sql
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

Editar `src/Config.php` (también admite variables de entorno: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, etc.):

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
sudo chown -R www-data:www-data img/uploads logs
sudo chmod -R 775 img/uploads logs
```

### 5. Apache (Alias `/tv` → raíz del repo)

Un solo `.htaccess` en la raíz. En Apache:

```apache
Alias /tv /var/www/html/Cartelera
<Directory /var/www/html/Cartelera>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
```

### 6. Cron (opcional)

```bash
*/30 * * * * /usr/bin/php /var/www/html/Cartelera/src/cron_actualizar_externos.php
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
Cartelera/
├── .env / .htaccess / index.php   # Únicos archivos de app en la raíz
├── css/  js/                      # Estáticos del panel
├── img/                      # Logo + uploads/
├── lib/                     # Composer
├── logs/                     # Logs, cache, backups
├── cambios/                       # Changelogs .md
├── docs/                          # Documentación
├── scripts/sql/                   # Schema y migraciones
├── scripts/tests/                 # PHPUnit
└── src/                           # Solo archivos (panel_*.php, api.php, cartelera.html, …)
```


Mantenimiento CLI:

```bash
php src/herramienta_reset_admin.php
# Schema BD: psql -f scripts/sql/schema.sql
```

---

## Tests / QA

```bash
composer install --working-dir=scripts
composer test --working-dir=scripts
```

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
- Errores PHP: `logs/php-errors.log`
- Cron: `logs/external-refresh.log`
- Actividad: tabla `activity_log`

---

## Documentación adicional

| Doc | Contenido |
|-----|-----------|
| `scripts/sql/schema.sql` | Esquema BD |
| `scripts/sql/migrations/` | Migraciones SQL |
| `cambios/` | Changelogs Markdown |
| `src/Config.php` | Constantes y env |
| `src/cron_actualizar_externos.php` | Cron APIs externas |

---

## Licencia

Uso interno de la Institución.

**Stack:** PHP 8 · PostgreSQL 12+ · Apache 2.4  
**Despliegue:** base path `/tv` vía Alias Apache → raíz del repo (un `.htaccess` en la raíz)
