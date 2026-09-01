# Dashboard Panel Principal — Rediseño visual

**Fecha:** 2026-07-14  
**Archivo afectado:** `frontend/src/routes/admin/dashboard/+page.svelte`  
**Enfoque elegido:** B — Barra de stats + 2 paneles

## Objetivo

Limpiar el panel principal: menos cards, sin métricas duplicadas, mejor jerarquía visual y responsive apilado — sin perder información ni el tamaño actual del Live TV en desktop.

## Problemas actuales

1. El banner verde incluye mini-cards de Académico / Centro / General.
2. Las primeras 3 cards del grid repiten exactamente esos mismos valores.
3. Seis cards sueltas dan sensación de “tablero de widgets” y ocupan mucha altura.
4. El hero verde es visualmente pesado respecto al resto del admin.

## Decisiones de producto

| Tema | Decisión |
|------|----------|
| Prioridad | Limpieza visual |
| Métricas | Las 6, una sola vez (sin duplicar) |
| Preview TV | Sigue al final; en **desktop mantiene `aspect-video` (16:9) a ancho completo como hoy** |
| Responsive | Apilar: móvil → tablet → desktop |
| Marca | Mantener acento verde en el saludo, **sin** mini-cards dentro |

## Estructura de la página

Orden de bloques (de arriba a abajo):

1. **Hero verde compacto** — saludo + subtítulo + badge de usuarios activos. Sin grid de métricas internos.
2. **Barra de estadísticas (1 card)** — las 6 métricas en un solo contenedor.
3. **Dos paneles** — Acciones rápidas | Actividad reciente.
4. **Vista previa TV** — título + botón “Abrir TV” + iframe.

### 1. Hero

- Fondo con gradiente de marca (`from-brand-primary to-brand-secondary`), padding moderado (`p-5`/`p-6`), `rounded-3xl`.
- Texto: label “Panel principal”, título “Bienvenido al panel CSET-TV”, subtítulo existente.
- Badge “N usuarios activos” alineado a la derecha en desktop; debajo o a la derecha en wrap en móvil.
- **No** incluir sub-cards de Académico/Centro/General.

### 2. Barra de métricas

Una sola card con las 6 claves actuales:

| Key | Label |
|-----|--------|
| `active_academic` | Contenido Académico |
| `active_center` | Contenido Centro |
| `active_general` | Contenido General |
| `total_media` | Archivos Multimedia |
| `active_users` | Usuarios Activos |
| `activity_24h` | Acciones (24h) |

- Número grande + label corto.
- Separadores verticales sutiles entre celdas en desktop (opcional, con `border-r` y oculto en la última).
- **No** usar 6 cards independientes.

### 3. Acciones y actividad

- Misma lógica y enlaces actuales.
- Grid `lg:grid-cols-2` (o `md:grid-cols-2` si ya se usa en el admin).
- Sin cambios de API.

### 4. Vista previa TV

- Misma estructura: card con título + `btn btn-primary` “Abrir TV” + iframe a `{base}/tv`.
- **Desktop:** `aspect-video w-full` — **mismo tamaño relativo que el actual** (sin `max-height` que lo reduzca).
- **Móvil/tablet:** también `aspect-video w-full` (proporción natural 16:9 a ancho del contenedor). No forzar ratio 21:9 ni recortar la altura del preview en desktop.

## Responsive

| Viewport | Hero | Métricas | Acciones / Actividad | TV |
|----------|------|----------|----------------------|-----|
| Móvil (&lt;640px) | Compacto, badge wrap | 1 card, grid 2×3 | Apilados (1 col) | `aspect-video` full width |
| Tablet (≥640px) | Compacto horizontal | 1 card, grid 3×2 | 2 cols desde `md`/`lg` | `aspect-video` |
| Desktop (≥1024px) | Compacto horizontal | 1 card, grid 1×6 | 2 cols | `aspect-video` **igual que hoy** |

Breakpoints Tailwind sugeridos:

- Métricas: `grid-cols-2 sm:grid-cols-3 lg:grid-cols-6`
- Paneles inferiores: `grid gap-6 lg:grid-cols-2`

## Fuera de alcance

- Cambios en la API `/dashboard`.
- Rediseño de `AppShell`, sidebar o tema global.
- Nuevas métricas o quitar las existentes.
- Cambiar la ruta `/tv` o el iframe (salvo layout CSS).

## Criterios de aceptación

1. No hay métricas duplicadas (banner sin mini-stats; stats solo en la barra).
2. Hay como máximo **3 cards de bloque** bajo el hero (stats + acciones + actividad) más la card de TV — no 6 cards de métricas.
3. En desktop el preview TV usa `aspect-video` a ancho completo, visualmente equivalente al tamaño actual.
4. En móvil las métricas no se aplastan en 6 columnas; usan 2 (y 3 en tablet).
5. Loading state existente se mantiene.
6. Enlaces de acciones rápidas y botón Abrir TV siguen funcionando.

## Implementación

- Archivo único principal: `frontend/src/routes/admin/dashboard/+page.svelte`.
- Reutilizar clases existentes (`card`, `btn`, tokens de marca / `surface-*`).
- No añadir dependencias.
