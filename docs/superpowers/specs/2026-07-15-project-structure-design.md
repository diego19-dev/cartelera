# Diseño: reorganización de carpetas (opción 2)

Fecha: 2026-07-15

## Objetivo

Ordenar la raíz del repo sin cambiar DocumentRoot Apache (`/cset/` → rewrite a `public/`). URLs públicas iguales; 301 de `/cset/public/...` se mantiene. Instaladores/seeds solo CLI, fuera de la web.

## Decisiones

- Alcance: reorganización media (no apuntar DocumentRoot solo a `public/`).
- Compatibilidad: redirecciones 301 legacy aceptables.
- Tools: `install*`, `seed_*`, `reset_admin.php`, `validar_js.php` viven en `tools/` y no son accesibles por URL.
- Apache: **Alias `/cset` → `public/`** (un solo `.htaccess` en `public/`).
- Plantilla canónica: `bash/htaccess.public`; activación: `bash/enable-cset-alias.sh`.
- Eliminar legado muerto: `src/Core/View.php`, `src/Views/`, backups `.htaccess.bak-*`.

## Estructura objetivo

```
cset/
├── frontend/
├── public/            # build + API + uploads + .htaccess (único)
├── src/               # PHP (sin Views) — fuera del Alias web
├── storage/           # fuera del Alias web
├── sql/
├── tests/
├── cron/
├── bash/           # build.sh + htaccess.public + apache-cset-alias.conf
├── tools/             # CLI mantenimiento
├── docs/
├── composer.json
├── phpunit.xml
└── README.md
```

## `.htaccess` único

| Archivo | Rol |
|---------|-----|
| `public/.htaccess` | API + SPA + bloquear PHP en uploads + MIME/caché/headers (fuente: `bash/htaccess.public`) |

`src/`, `tools/`, `storage/`, etc. no son alcanzables por URL porque `/cset` apunta solo a `public/`.

## Fuera de alcance

- Convertir formularios web de install/reset a CLI completo (quedan en `tools/`).
