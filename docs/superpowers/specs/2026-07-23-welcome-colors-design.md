# Diseño: colores por bloque en Bienvenida

**Fecha:** 2026-07-23  
**Ámbito:** plantilla welcome (panel admin, tv-modern, TV Signage `/tv`)  
**Estado:** aprobado en conversación (enfoque 1 — variables CSS por elemento; opción B granular; alcance A en todas las pantallas)

## Problema

El panel `/admin/bienvenida` ya permite editar `bg_color`, `accent_color` y `text_color`, pero el CSS del slide (Svelte y ES5 Signage) deja hardcodeados verdes/grises SENA en saludo, título, centro, regional, pie, chispas lima, aros y parte del divisor. Al cambiar colores en el panel, la vista previa y las TVs apenas reaccionan.

## Decisiones

| Decisión | Valor |
|----------|--------|
| Enfoque | Variables CSS por elemento + claves `welcome_*` en `display_config` |
| Granularidad | Un color por bloque visible + acento decorativo |
| Alcance pantallas | Panel preview, `/tv-modern` y `/cset/tv/` (Signage) |
| Presets / temas | Fuera de alcance |
| Compatibilidad | Si faltan claves nuevas, derivar desde `text_color` / `accent_color` legacy |

## Modelo de colores

| Campo UI | Clave API (sin prefijo `welcome_`) | Clave BD | Default |
|----------|-------------------------------------|----------|---------|
| Fondo | `bg_color` | `welcome_bg_color` | `#eaf4e1` |
| Saludo | `saludo_color` | `welcome_saludo_color` | `#39a900` |
| Título | `titulo_color` | `welcome_titulo_color` | `#39a900` |
| Centro | `centro_color` | `welcome_centro_color` | `#4d4d4d` |
| Regional | `regional_color` | `welcome_regional_color` | `#6b6b6b` |
| Lema | `lema_color` | `welcome_lema_color` | `#4d4d4d` |
| Lema/pie negrita (`<b>`) | `accent_color` | `welcome_accent_color` | `#39a900` |
| Hashtag (fondo pill) | `hashtag_color` | `welcome_hashtag_color` | `#39a900` |
| Pie | `pie_color` | `welcome_pie_color` | `#6b6b6b` |
| Decoración (chispas, aros, 2º del divisor) | `deco_color` | `welcome_deco_color` | `#a6ce39` |

- Validación: hex `#RRGGBB` (misma regla que colores actuales en `WelcomeController`).
- `text_color` (`welcome_text_color`): dejar de usar como color único de texto en CSS y **no** exponerlo en el formulario.
- Lectura (`getWelcome` / `mergeWelcome`): si falta una clave nueva, fallback — `saludo_color` / `titulo_color` / `hashtag_color` ← `accent_color`; `centro_color` / `regional_color` / `lema_color` / `pie_color` ← `text_color`; `deco_color` ← default `#a6ce39`.
- Escritura: el PUT envía las claves nuevas. Al guardar desde el panel, sincronizar `welcome_text_color = lema_color` para clientes/feed antiguos que aún lean `text_color`.

## Variables CSS

Inyectadas en el root del slide:

`--welcome-bg`, `--welcome-saludo`, `--welcome-titulo`, `--welcome-centro`, `--welcome-regional`, `--welcome-lema`, `--welcome-accent`, `--welcome-hashtag`, `--welcome-pie`, `--welcome-deco`

Más `--welcome-bg-image` cuando hay imagen de fondo (sin cambio de comportamiento).

## UI del panel (`/admin/bienvenida`)

1. Sección **Colores** con un picker por fila de la tabla (etiqueta + `type="color"`; input hex editable si encaja con el patrón del proyecto).
2. Vista previa en vivo vía `WelcomeSlide` y el `form` reactivo.
3. **Guardar plantilla** persiste textos + duración + todos los colores.
4. **Restaurar textos** solo textos/duración (mantiene colores e imagen).
5. **Restaurar colores** restaura defaults de la tabla (mantiene textos e imagen).
6. Sin presets ni temas extra.

## CSS y render TV

- `frontend/src/lib/tv.css` y estilos equivalentes en `frontend/static/tv/index.html`: cada bloque usa su variable; eliminar hardcodes `#39a900`, `#a6ce39`, `#4d4d4d`, `#6b6b6b`, `#2d8500`. En Signage (Chromium 53): **no** usar `color-mix`; aros/sombras con `opacity` sobre el color de la variable o sombra neutra fija (`rgba(0,0,0,.2)`), no verdes hardcodeados.
- `WelcomeSlide.svelte`: emitir todas las variables desde `mergeWelcome(data)`.
- Render ES5 en `frontend/static/tv/index.html`: al pintar slide `bienvenida`, leer los nuevos campos del payload welcome y setear las mismas variables.
- `DisplayConfig::welcomeDefaults`, `getWelcome`, `welcomeEditableKeys` + `WelcomeController` color keys: incluir las nuevas claves.
- Feed `/api/tv`: ya incluye welcome vía `getWelcome()`; debe devolver los nuevos campos tras el cambio del modelo.

## Archivos a tocar (previsto)

- `src/Models/DisplayConfig.php`
- `src/Api/WelcomeController.php`
- `frontend/src/lib/tv/welcome.ts` (+ tests unitarios)
- `frontend/src/lib/components/ui/WelcomeSlide.svelte`
- `frontend/src/lib/tv.css`
- `frontend/src/routes/admin/bienvenida/+page.svelte`
- `frontend/static/tv/index.html` (fuente Signage; luego build a `public/tv/`)
- `tests/Unit/` relacionados con welcome sanitize/defaults si aplica

## Fuera de alcance

- Presets de tema (SENA / oscuro / alto contraste)
- Tipografía, tamaños, espaciados o layout del slide
- Color de texto del hashtag (sigue blanco sobre la pill)
- Cambiar el botón «Tema» del shell admin (tema del panel ≠ plantilla welcome)

## Criterios de aceptación

- [ ] En `/admin/bienvenida` hay un control de color por cada bloque de la tabla.
- [ ] Cambiar un color actualiza la vista previa al instante sin guardar.
- [ ] Tras guardar, los colores se reflejan en `/tv-modern` y en `/cset/tv/`.
- [ ] Saludo, título, centro, regional, lema, pie, hashtag y decoración ya no ignoran la plantilla por hardcodes CSS.
- [ ] Instalaciones con solo `welcome_text_color` / `welcome_accent_color` siguen viendo colores coherentes vía fallback.
- [ ] «Restaurar colores» vuelve a los defaults SENA sin borrar textos ni imagen de fondo.
- [ ] Tests unitarios cubren defaults y merge/fallback de colores legacy.
