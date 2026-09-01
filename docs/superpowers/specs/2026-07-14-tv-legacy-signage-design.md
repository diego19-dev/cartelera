# Cartelera TV para monitores LG Signage (webOS 4.1 / Chromium 53)

**Fecha:** 2026-07-14 (actualizado)  
**Objetivo:** `/tv` es la cartelera de producción compatible con monitores comerciales LG (p. ej. 65UH5F-H).

## Problema

El SPA SvelteKit usaba ES modules (`import()`). webOS 4.1 Signage usa **Chromium 53**, que no los soporta → pantalla en blanco.

## Solución

`/cset/tv/` es una página **estática** (HTML/CSS/JS ES5-safe), no el SPA:

`http://10.5.213.111/cset/tv/`

- Sin módulos ES, sin `async/await`
- CSS compatible (flex, sin `color-mix` / `clamp` / CSS Grid moderno)
- Misma API: `/cset/api/tv` y `/cset/api/tv/ping`
- Reloj forzado a `America/Bogota` + sync con `server_ts`

La vista Svelte moderna (solo PCs/navegadores actuales) quedó en `/cset/tv-modern`.

## Uso en el monitor

1. Preferir **Play via URL** (pantalla completa).
2. URL recomendada (índice del servidor): `http://10.5.213.111/`
3. También: `http://10.5.213.111/cset/tv/`
4. Usar **HTTP**, no HTTPS (certificado autofirmado).

`/cset/tv-legacy/` redirige a `/cset/tv/`.

## Fuente y build

- Canónico: `frontend/static/tv/index.html`
- `bash/build.sh` copia/preserva `public/tv/` y publica `/var/www/html/index.html` en el DocumentRoot.
- URLs de plataforma sin `/public`: p. ej. `http://10.5.213.111/cset/login`
