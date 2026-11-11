# CSET-TV — Estructura del proyecto

Código: `/var/www/html/Cartelera`.

## Raíz

Archivos: `.env`, `.htaccess`, `index.php` (+ `.gitignore`, `.env.example`).

| Carpeta | Rol |
|---------|-----|
| `css/`, `js/` | Estilos y scripts del panel |
| `img/` | Logo + `img/uploads/` |
| `lib/` | Dependencias opcionales |
| `logs/` | Logs, cache y backups |
| `cambios/` | Changelogs Markdown |
| `docs/` | Documentación |
| `scripts/` | Alias Apache, desarrollo, SQL (`sql/`), tests PHPUnit |
| `src/` | Solo archivos PHP/HTML (sin subcarpetas) |

## URLs públicas

| URL | Archivo en `src/` |
|-----|-------------------|
| `/tv/` | `index.php` + `cartelera.html` |
| `/tv/login.php` | `iniciar_sesion.php` |
| `/tv/admin/dashboard.php` | `panel_inicio.php` |
| `/tv/admin/{modulo}/…` | `panel_*.php` |
| `/tv/api/…` | `api.php` |

## Comandos

```bash
bash scripts/desarrollo.sh
sudo bash scripts/activar-alias-tv.sh
bash scripts/preparar.sh
composer install --working-dir=scripts
composer test --working-dir=scripts
```

## Lectura del código

1. `src/bootstrap.php` — arranque  
2. `src/Config.php` — rutas y `APP_BASE`  
3. `src/ApiDispatcher.php` / `TvController.php` — API y feed TV  
4. `index.php` + `src/cartelera.html` — player  
5. `src/panel_*.php` — panel admin  
