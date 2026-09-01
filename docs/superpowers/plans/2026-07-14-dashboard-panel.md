# Dashboard Panel Principal — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar el panel principal del admin para quitar métricas duplicadas, usar una sola barra de stats y mantener el Live TV en `aspect-video` (tamaño actual).

**Architecture:** Cambio de markup/CSS Tailwind en una sola página Svelte. La data sigue viniendo de `GET /dashboard` (`stats` + `recent_activity`). No hay cambios de API ni de componentes compartidos.

**Tech Stack:** SvelteKit, Tailwind, clases utilitarias existentes (`card`, `btn`, `brand-*`, `surface-*`).

**Spec:** `docs/superpowers/specs/2026-07-14-dashboard-panel-design.md`

---

## File map

| File | Role |
|------|------|
| `frontend/src/routes/admin/dashboard/+page.svelte` | Único archivo a modificar: hero, barra de métricas, acciones, actividad, TV |
| `frontend/src/app.css` | Solo referencia (clase `.card`); no modificar |

No crear componentes nuevos: el alcance cabe en la página.

---

### Task 1: Limpiar config de métricas y hero (sin mini-cards)

**Files:**
- Modify: `frontend/src/routes/admin/dashboard/+page.svelte`

- [ ] **Step 1: Renombrar `cards` → `metrics` y quitar `color` (ya no se usa)**

En el `<script>`, reemplazar el array `cards` por:

```ts
  const metrics = [
    { key: 'active_academic', label: 'Contenido Académico' },
    { key: 'active_center', label: 'Contenido Centro' },
    { key: 'active_general', label: 'Contenido General' },
    { key: 'total_media', label: 'Archivos Multimedia' },
    { key: 'active_users', label: 'Usuarios Activos' },
    { key: 'activity_24h', label: 'Acciones (24h)' },
  ];
```

- [ ] **Step 2: Quitar el grid de mini-cards dentro del hero**

Dejar el hero solo con saludo + badge. Reemplazar el bloque `<section class="mb-6 ...">` actual (líneas ~35–49) por:

```svelte
    <section class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-primary to-brand-secondary p-5 text-white shadow-lg sm:p-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-widest text-white/70">Panel principal</p>
          <h2 class="mt-1 text-2xl font-bold">Bienvenido al panel CSET-TV</h2>
          <p class="mt-2 text-sm text-white/80">Gestione la cartelera digital y el contenido de las Smart TVs.</p>
        </div>
        <span class="rounded-full bg-white/15 px-3 py-1 text-xs">{stats.active_users ?? 0} usuarios activos</span>
      </div>
    </section>
```

**Importante:** no debe quedar ningún `stats.active_academic` / `active_center` / `active_general` dentro del `<section>` del hero.

- [ ] **Step 3: Commit (si el usuario pidió commits)**

```bash
git add frontend/src/routes/admin/dashboard/+page.svelte
git commit -m "$(cat <<'EOF'
refactor(dashboard): compact green hero without duplicate mini-stats

EOF
)"
```

Si el usuario no pidió commit, omitir este paso y seguir.

---

### Task 2: Barra única de métricas (responsive 2 → 3 → 6)

**Files:**
- Modify: `frontend/src/routes/admin/dashboard/+page.svelte`

- [ ] **Step 1: Reemplazar el grid de 6 cards por una sola card con grid interno**

Eliminar el bloque:

```svelte
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      {#each cards as c}
        ...
      {/each}
    </div>
```

Sustituirlo por:

```svelte
    <div class="card mb-6">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        {#each metrics as m, i}
          <div
            class="text-center sm:text-left {i < metrics.length - 1
              ? 'lg:border-r lg:border-surface-border lg:pr-4'
              : ''}"
          >
            <div class="text-3xl font-bold">{stats[m.key] ?? 0}</div>
            <div class="mt-1 text-sm text-surface-muted">{m.label}</div>
          </div>
        {/each}
      </div>
    </div>
```

Notas:
- `grid-cols-2` = móvil (2×3)
- `sm:grid-cols-3` = tablet (3×2)
- `lg:grid-cols-6` = desktop (1×6)
- Separadores verticales solo en `lg:` para no ensuciar móvil/tablet

- [ ] **Step 2: Verificar que no queden referencias a `cards`**

Buscar en el archivo: no debe existir `cards`. Solo `metrics`.

```bash
rg "cards" frontend/src/routes/admin/dashboard/+page.svelte
```

Expected: sin matches (o solo comentarios si hubiera; idealmente ninguno).

- [ ] **Step 3: Commit (si aplica)**

```bash
git add frontend/src/routes/admin/dashboard/+page.svelte
git commit -m "$(cat <<'EOF'
feat(dashboard): replace six metric cards with a single stats bar

EOF
)"
```

---

### Task 3: Confirmar paneles inferiores y TV (sin romper tamaño desktop)

**Files:**
- Modify: `frontend/src/routes/admin/dashboard/+page.svelte` (solo si hace falta; idealmente no cambiar)

- [ ] **Step 1: Revisar que Acciones + Actividad sigan iguales**

El bloque debe conservar:

```svelte
    <div class="grid gap-6 lg:grid-cols-2">
      <div class="card">
        <h3 class="mb-4 font-semibold">Acciones rápidas</h3>
        ...
      </div>
      <div class="card">
        <h3 class="mb-4 font-semibold">Actividad reciente</h3>
        ...
      </div>
    </div>
```

Enlaces:

- `{base}/admin/academic/new`
- `{base}/admin/center/new`
- `{base}/admin/general/new`

- [ ] **Step 2: Revisar Vista previa TV — mantener `aspect-video`**

El contenedor del iframe **debe** quedar exactamente con estas clases de tamaño (equivalente al actual):

```svelte
      <div
        class="relative aspect-video w-full overflow-hidden rounded-xl border border-surface-border bg-black"
      >
        <iframe
          title="TV Preview"
          src="{base}/tv"
          class="absolute inset-0 h-full w-full border-0"
        ></iframe>
      </div>
```

**No añadir** `max-h-*`, `aspect-[21/9]`, ni escalas que reduzcan la altura en desktop.

- [ ] **Step 3: Commit final del archivo completo (si aplica)**

Solo si hubo cambios en Task 3; si no, omitir.

```bash
git add frontend/src/routes/admin/dashboard/+page.svelte
git commit -m "$(cat <<'EOF'
chore(dashboard): keep actions, activity, and full-size TV preview

EOF
)"
```

---

### Task 4: Verificación manual y checklist del spec

**Files:** ninguno (verificación)

- [ ] **Step 1: Abrir el dashboard autenticado**

Ruta: `/admin/dashboard` (con el `base` path del proyecto si aplica).

Checklist visual:

1. Hero verde **sin** mini-cards de Académico/Centro/General.
2. Una sola barra con las 6 métricas (números coinciden con lo que antes mostraban las cards).
3. Acciones rápidas + actividad recentes presentes.
4. Preview TV en desktop con proporción 16:9 a ancho del contenido (mismo look que antes del rediseño).
5. En viewport móvil (~375px): métricas en 2 columnas, no 6; paneles apilados.
6. En ~768px: métricas en 3 columnas.
7. En ≥1024px: métricas en 6 columnas en una fila.
8. Loading: sigue mostrando “Cargando estadísticas...” antes de la data.

- [ ] **Step 2: Contar cards de bloque bajo el hero**

Deben quedar **4** contenedores tipo card en total:

1. Barra de métricas  
2. Acciones rápidas  
3. Actividad reciente  
4. Vista previa TV  

No 6 cards de métricas + extras.

- [ ] **Step 3: Smoke de build frontend (opcional pero recomendado)**

```bash
cd frontend && npm run test
```

Expected: tests unitarios existentes pasan (este cambio no debería romperlos).

Si el proyecto requiere build para desplegar a `public/`:

```bash
cd frontend && npm run build
```

Expected: build OK.

---

## Estado final esperado del markup (referencia)

Orden en `{#if !loading}`:

1. `<section>` hero verde compacto  
2. `<div class="card mb-6">` barra `metrics`  
3. `<div class="grid gap-6 lg:grid-cols-2">` acciones | actividad  
4. `<div class="card mt-6">` TV con `aspect-video`

Script: `onMount` + `api('/dashboard')` sin cambios; array `metrics` sin campo `color`.

---

## Spec coverage (self-review)

| Requisito spec | Task |
|----------------|------|
| Hero verde sin mini-cards | Task 1 |
| 6 métricas una sola vez en 1 card | Task 2 |
| Acciones \| Actividad sin cambios de lógica | Task 3 |
| TV `aspect-video` desktop = tamaño actual | Task 3 |
| Responsive 2 → 3 → 6 | Task 2 |
| Loading state | sin cambio (verificar Task 4) |
| Sin cambios API / AppShell | implícito (no se tocan) |

**Placeholders:** ninguno.  
**Tipos/API:** sin funciones nuevas; keys de stats invariables.
