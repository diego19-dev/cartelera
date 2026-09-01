# CSET-TV — Estructura del proyecto

Código en disco: `/var/www/html/cset`.

## Carpetas

| Carpeta | Rol |
|---------|-----|
| `public/` | DocumentRoot / Alias `/tv` (panel PHP, API, cartelera, assets) |
| `src/Core/` | Bootstrap, Config, Auth, Session, Database, Security, Logger |
| `src/Views/` | Helpers, layout (`header`/`nav`/`footer`), auth_guard, crud |
| `src/Models/` | Modelos de dominio |
| `src/Api/` | Controladores API REST |
| `src/Controllers/` | Controladores adicionales |
| `cron/` | Tareas programadas |
| `scripts/` | Alias Apache, `.htaccess`, stubs |
| `tools/` | Utilidades CLI (reset admin, reencode) |
| `tests/` | PHPUnit |
| `storage/` | Logs y cache (no públicos) |
| `sql/` | Schema / migraciones |
| `docs/` | Documentación |
| `backups/` | Respaldos |
| `img/` | Imágenes de documentación |

## URLs públicas

| URL | Destino |
|-----|---------|
| `/tv/` | Cartelera (`public/index.php` + player ES5 en `public/signage/`) |
| `/tv/login.php` | Login panel PHP |
| `/tv/admin/dashboard.php` | Dashboard |
| `/tv/admin/{modulo}/` | CRUD PHP |
| `/tv/api/...` | API REST (`public/api/index.php`) |

Bookmarks `/cset/*` → 301 a `/tv/*`. `/tv/tv-modern` → 301 a `/tv/`.

## Build / Apache

- Alias: `sudo bash scripts/enable-cset-alias.sh` → `/tv` → `public/`
- `.htaccess` canónico: `scripts/htaccess.public` → `public/.htaccess` (`bash scripts/build.sh`)
- Secretos solo en `.env` en la raíz del proyecto (no versionar).

## Notas

- El panel operativo es PHP clásico en `public/admin/` y `public/login.php`.
- La cartelera de producción es el player ES5 en `public/signage/` (LG Signage / Chromium 53).
