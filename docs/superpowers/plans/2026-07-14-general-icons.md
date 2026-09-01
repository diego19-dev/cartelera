# Banco de iconos Información General — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir elegir un icono Lucide por cada contenido de Información General y mostrarlo a la derecha del slide en la TV (layout anuncio), omitiendo el slot si no hay icono.

**Architecture:** Columna `icon` (clave Lucide) en `general_content`. Whitelist compartida en PHP (`GENERAL_ICONS`) y frontend (`generalIcons.ts`). Admin selecciona en `GeneralForm`; `TvController` expone `icon`; `SlideView` renderiza Lucide a la derecha solo si hay valor.

**Tech Stack:** PostgreSQL, PHP API, SvelteKit, lucide-svelte, Vitest, PHPUnit

**Spec:** `docs/superpowers/specs/2026-07-14-general-icons-design.md`

---

## File map

| File | Responsibility |
|------|----------------|
| `sql/schema.sql` | Columna `icon` en instalaciones nuevas |
| `sql/migrations/2026-07-14-general-content-icon.sql` | `ALTER` para BD existente |
| `src/Core/Config.php` | Constante `GENERAL_ICONS` (whitelist) |
| `src/Models/GeneralContent.php` | Normalizar/validar `icon` en save |
| `src/Api/TvController.php` | Incluir `icon` en payload TV |
| `frontend/src/lib/tv/generalIcons.ts` | Banco + mapa Lucide + helpers |
| `frontend/tests/unit/generalIcons.test.ts` | Tests del catálogo |
| `frontend/src/lib/tv/types.ts` | `icon?: string \| null` en `TvSlide` |
| `frontend/src/lib/components/crud/GeneralForm.svelte` | Selector de iconos |
| `frontend/src/routes/admin/general/+page.svelte` | Miniatura en listado |
| `frontend/src/lib/components/ui/SlideView.svelte` | Slot derecho layout anuncio |
| `frontend/src/lib/tv.css` | Estilos `.tv-slide-body-row` / `.tv-slide-visual` |
| `.gitignore` | Ignorar `.superpowers/` |

`GeneralController` ya reenvía campos del FormData a `create`/`update`; no requiere cambios de routing si `icon` llega en POST y el modelo lo normaliza.

---

### Task 1: Migración BD + schema + whitelist PHP

**Files:**
- Create: `sql/migrations/2026-07-14-general-content-icon.sql`
- Modify: `sql/schema.sql` (tabla `general_content`)
- Modify: `src/Core/Config.php`
- Modify: `src/Models/GeneralContent.php`
- Create: `tests/Unit/GeneralIconSanitizeTest.php`

- [ ] **Step 1: Añadir archivo de migración**

Crear `sql/migrations/2026-07-14-general-content-icon.sql`:

```sql
ALTER TABLE general_content
  ADD COLUMN IF NOT EXISTS icon VARCHAR(50) NULL;

COMMENT ON COLUMN general_content.icon IS
  'Clave Lucide del banco (ej. dollar-sign). NULL = sin icono en TV.';
```

- [ ] **Step 2: Actualizar `sql/schema.sql`**

Dentro del `CREATE TABLE general_content`, después de `image_path VARCHAR(500),` añadir:

```sql
    icon                VARCHAR(50),
```

- [ ] **Step 3: Definir whitelist en `Config.php`**

Después de `CONTENT_CATEGORIES`, añadir:

```php
define('GENERAL_ICONS', [
    'dollar-sign',
    'trending-up',
    'cloud',
    'thermometer',
    'newspaper',
    'calendar',
    'megaphone',
    'building-2',
    'landmark',
    'trophy',
    'droplets',
    'bell',
    'info',
    'bitcoin',
]);
```

- [ ] **Step 4: Escribir test PHP que falle**

Crear `tests/Unit/GeneralIconSanitizeTest.php`:

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GeneralIconSanitizeTest extends TestCase {

    public function test_valid_icon_is_kept(): void {
        $this->assertSame('dollar-sign', GeneralContent::sanitizeIcon('dollar-sign'));
    }

    public function test_empty_or_invalid_becomes_null(): void {
        $this->assertNull(GeneralContent::sanitizeIcon(''));
        $this->assertNull(GeneralContent::sanitizeIcon('   '));
        $this->assertNull(GeneralContent::sanitizeIcon('not-a-real-icon'));
        $this->assertNull(GeneralContent::sanitizeIcon(null));
    }
}
```

Nota: el bootstrap de PHPUnit debe cargar `Config.php` y poder autoload/requerir `GeneralContent.php` (mismo patrón que otros tests del repo). Si hace falta, al inicio del test:

```php
require_once dirname(__DIR__, 2) . '/src/Core/Config.php';
require_once dirname(__DIR__, 2) . '/src/Models/GeneralContent.php';
```

(solo si el bootstrap actual no carga modelos).

- [ ] **Step 5: Correr test y ver fallo**

Run: `composer test -- --filter GeneralIconSanitizeTest`

Expected: FAIL (método `sanitizeIcon` no existe).

- [ ] **Step 6: Implementar `sanitizeIcon` y usarlo en `normalizeData`**

En `src/Models/GeneralContent.php`, añadir método público:

```php
public static function sanitizeIcon(?string $icon): ?string {
    if ($icon === null) {
        return null;
    }
    $raw = trim($icon);
    if ($raw === '' || !in_array($raw, GENERAL_ICONS, true)) {
        return null;
    }
    return $raw;
}
```

En `normalizeData`, después del bucle de strings opcionales, añadir:

```php
if (array_key_exists('icon', $data)) {
    $data['icon'] = self::sanitizeIcon(
        $data['icon'] === null ? null : (string) $data['icon']
    );
}
```

Incluir `'icon'` en el `foreach` de strings opcionales **o** dejar solo el bloque `array_key_exists` (preferible el bloque dedicado para whitelist).

- [ ] **Step 7: Correr test y ver pase**

Run: `composer test -- --filter GeneralIconSanitizeTest`

Expected: PASS

- [ ] **Step 8: Aplicar migración en la BD local**

Run (ajustar host/db/user según `.env`):

```bash
psql "$DATABASE_URL" -f sql/migrations/2026-07-14-general-content-icon.sql
```

Si no hay `DATABASE_URL`, usar las credenciales de `src/Core/Config.php` / `.env`.

Expected: `ALTER TABLE` OK; `\d general_content` muestra columna `icon`.

- [ ] **Step 9: Commit**

```bash
git add sql/migrations/2026-07-14-general-content-icon.sql sql/schema.sql src/Core/Config.php src/Models/GeneralContent.php tests/Unit/GeneralIconSanitizeTest.php
git commit -m "$(cat <<'EOF'
feat(general): add icon column and Lucide whitelist

EOF
)"
```

Solo si el usuario autorizó commits en esta sesión; si no, omitir y continuar.

---

### Task 2: Exponer `icon` en API TV

**Files:**
- Modify: `src/Api/TvController.php`

- [ ] **Step 1: Añadir `icon` al mapa de slides general**

En el `return [...]` de `scenarios['general']` (aprox. líneas 80–92), añadir:

```php
'icon' => !empty($g['icon']) ? $g['icon'] : null,
```

junto a `'category'`, etc.

- [ ] **Step 2: Verificar payload (smoke)**

Run:

```bash
curl -sS "http://localhost/cset-svelte/public/api/tv" | python3 -c "import sys,json; d=json.load(sys.stdin); g=d.get('scenarios',{}).get('general',[]); print('ok', d.get('ok')); print('sample', g[0] if g else None)"
```

Expected: respuesta `ok`; cada ítem general incluye clave `icon` (puede ser `null`).

- [ ] **Step 3: Commit (si autorizado)**

```bash
git add src/Api/TvController.php
git commit -m "$(cat <<'EOF'
feat(tv): include general content icon in TV payload

EOF
)"
```

---

### Task 3: Catálogo frontend `generalIcons.ts` + tests

**Files:**
- Create: `frontend/src/lib/tv/generalIcons.ts`
- Create: `frontend/tests/unit/generalIcons.test.ts`

- [ ] **Step 1: Escribir test Vitest que falle**

Crear `frontend/tests/unit/generalIcons.test.ts`:

```ts
import { describe, it, expect } from 'vitest';
import { GENERAL_ICON_KEYS, isGeneralIconKey, resolveGeneralIcon } from '$lib/tv/generalIcons';

describe('generalIcons', () => {
  it('lists the shared bank keys', () => {
    expect(GENERAL_ICON_KEYS).toContain('dollar-sign');
    expect(GENERAL_ICON_KEYS).toContain('cloud');
    expect(GENERAL_ICON_KEYS.length).toBeGreaterThanOrEqual(10);
  });

  it('validates keys', () => {
    expect(isGeneralIconKey('dollar-sign')).toBe(true);
    expect(isGeneralIconKey('nope')).toBe(false);
    expect(isGeneralIconKey('')).toBe(false);
  });

  it('resolves a Lucide component for known keys', () => {
    expect(resolveGeneralIcon('dollar-sign')).toBeTruthy();
    expect(resolveGeneralIcon(null)).toBeNull();
    expect(resolveGeneralIcon('nope')).toBeNull();
  });
});
```

- [ ] **Step 2: Correr test y ver fallo**

Run: `cd frontend && npm test -- tests/unit/generalIcons.test.ts`

Expected: FAIL (módulo no existe).

- [ ] **Step 3: Implementar `generalIcons.ts`**

Crear `frontend/src/lib/tv/generalIcons.ts`:

```ts
import type { ComponentType } from 'svelte';
import {
  Bell,
  Bitcoin,
  Building2,
  Calendar,
  Cloud,
  DollarSign,
  Droplets,
  Info,
  Landmark,
  Megaphone,
  Newspaper,
  Thermometer,
  TrendingUp,
  Trophy
} from 'lucide-svelte';

/** Debe coincidir con GENERAL_ICONS en src/Core/Config.php */
export const GENERAL_ICON_BANK: { key: string; label: string; icon: ComponentType }[] = [
  { key: 'dollar-sign', label: 'Dólar', icon: DollarSign },
  { key: 'trending-up', label: 'Tendencia', icon: TrendingUp },
  { key: 'bitcoin', label: 'Cripto', icon: Bitcoin },
  { key: 'cloud', label: 'Clima', icon: Cloud },
  { key: 'thermometer', label: 'Temperatura', icon: Thermometer },
  { key: 'newspaper', label: 'Noticia', icon: Newspaper },
  { key: 'calendar', label: 'Evento', icon: Calendar },
  { key: 'megaphone', label: 'Anuncio', icon: Megaphone },
  { key: 'building-2', label: 'Institucional', icon: Building2 },
  { key: 'landmark', label: 'Edificio', icon: Landmark },
  { key: 'trophy', label: 'Deportes', icon: Trophy },
  { key: 'droplets', label: 'Líquidos', icon: Droplets },
  { key: 'bell', label: 'Alerta', icon: Bell },
  { key: 'info', label: 'Info', icon: Info }
];

export const GENERAL_ICON_KEYS = GENERAL_ICON_BANK.map((i) => i.key);

export function isGeneralIconKey(key: string | null | undefined): boolean {
  return !!key && GENERAL_ICON_KEYS.includes(key);
}

export function resolveGeneralIcon(key: string | null | undefined): ComponentType | null {
  if (!key) return null;
  return GENERAL_ICON_BANK.find((i) => i.key === key)?.icon ?? null;
}
```

Si TypeScript se queja de `ComponentType` con Lucide en Svelte 5, usar `typeof DollarSign` como tipo del campo `icon` y retorno de `resolveGeneralIcon`.

- [ ] **Step 4: Correr tests y ver pase**

Run: `cd frontend && npm test -- tests/unit/generalIcons.test.ts`

Expected: PASS

- [ ] **Step 5: Commit (si autorizado)**

```bash
git add frontend/src/lib/tv/generalIcons.ts frontend/tests/unit/generalIcons.test.ts
git commit -m "$(cat <<'EOF'
feat(frontend): add general info Lucide icon bank

EOF
)"
```

---

### Task 4: Tipo `TvSlide.icon` + formulario admin

**Files:**
- Modify: `frontend/src/lib/tv/types.ts`
- Modify: `frontend/src/lib/components/crud/GeneralForm.svelte`

- [ ] **Step 1: Extender `TvSlide`**

En `frontend/src/lib/tv/types.ts`, dentro de `TvSlide`, añadir:

```ts
  icon?: string | null;
```

- [ ] **Step 2: Extender estado del formulario**

En `GeneralForm.svelte`:

1. Importar el banco:

```ts
  import { GENERAL_ICON_BANK } from '$lib/tv/generalIcons';
```

2. Añadir `icon: ''` al objeto `form` inicial:

```ts
  let form = {
    category: 'news',
    title: '',
    description: '',
    source_url: '',
    duration_seconds: 10,
    display_order: 0,
    expire_date: '',
    is_external: false,
    is_active: true,
    icon: ''
  };
```

3. Tras el campo Título (o Categoría), insertar el selector:

```svelte
    <div>
      <label class="mb-2 block text-sm">Icono para la TV</label>
      <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
        <button
          type="button"
          class="flex aspect-square flex-col items-center justify-center rounded-lg border text-xs {form.icon === '' ? 'border-brand-primary bg-brand-primary/10' : 'border-surface-border'}"
          on:click={() => (form.icon = '')}
        >
          Ninguno
        </button>
        {#each GENERAL_ICON_BANK as item}
          <button
            type="button"
            title={item.label}
            class="flex aspect-square items-center justify-center rounded-lg border {form.icon === item.key ? 'border-brand-primary bg-brand-primary/10' : 'border-surface-border'}"
            on:click={() => (form.icon = item.key)}
          >
            <svelte:component this={item.icon} class="size-6" />
          </button>
        {/each}
      </div>
    </div>
```

Al cargar el ítem existente, si `item.icon` es `null`, dejar `form.icon = ''` (ya cubierto por el spread si se normaliza). Tras el load, forzar:

```ts
      form = { ...form, ...item, is_active: !!item.is_active, is_external: !!item.is_external, icon: item.icon || '' };
```

El `save` existente ya hace `Object.entries(form).forEach` → `icon` viaja en FormData. Vacío → PHP `sanitizeIcon` → `NULL`.

- [ ] **Step 3: Smoke manual admin**

1. Abrir `/admin/general` → Editar un ítem (p. ej. cotización USD).
2. Elegir icono dólar → Guardar.
3. Reabrir edición: debe seguir seleccionado.
4. Elegir “Ninguno” → Guardar → reabrir sin selección.

- [ ] **Step 4: Commit (si autorizado)**

```bash
git add frontend/src/lib/tv/types.ts frontend/src/lib/components/crud/GeneralForm.svelte
git commit -m "$(cat <<'EOF'
feat(admin): icon bank picker on general content form

EOF
)"
```

---

### Task 5: Miniatura en listado admin

**Files:**
- Modify: `frontend/src/routes/admin/general/+page.svelte`

- [ ] **Step 1: Mostrar icono junto a la categoría**

Importar:

```ts
  import { resolveGeneralIcon } from '$lib/tv/generalIcons';
```

Extender el tipo:

```ts
  type Item = { id: number; title: string; category: string; is_active: boolean; is_external?: boolean; icon?: string | null };
```

En la celda de categoría, reemplazar el contenido por:

```svelte
            <td class="p-2">
              <div class="flex items-center gap-2">
                {#if resolveGeneralIcon(item.icon)}
                  <svelte:component this={resolveGeneralIcon(item.icon)} class="size-4 shrink-0 text-surface-muted" />
                {/if}
                <span>{categories[item.category] || item.category}</span>
                {#if item.is_external}<span class="badge bg-amber-100 text-amber-800">API</span>{/if}
              </div>
            </td>
```

Nota: llamar `resolveGeneralIcon` una sola vez por fila si molesta el doble call:

```svelte
{#each items as item}
  {@const Icon = resolveGeneralIcon(item.icon)}
  ...
  {#if Icon}<svelte:component this={Icon} class="size-4 shrink-0 text-surface-muted" />{/if}
```

- [ ] **Step 2: Verificar listado**

Abrir `/admin/general`: ítems con icono muestran el Lucide; sin icono, solo texto.

- [ ] **Step 3: Commit (si autorizado)**

```bash
git add frontend/src/routes/admin/general/+page.svelte
git commit -m "$(cat <<'EOF'
feat(admin): show general icon thumbnail in list

EOF
)"
```

---

### Task 6: Slot de icono en TV (`SlideView` + CSS)

**Files:**
- Modify: `frontend/src/lib/components/ui/SlideView.svelte`
- Modify: `frontend/src/lib/tv.css`

- [ ] **Step 1: Resolver componente icono en el script**

En `SlideView.svelte`, importar:

```ts
  import { resolveGeneralIcon } from '$lib/tv/generalIcons';
```

Añadir derived:

```ts
  let SlideIcon = $derived(resolveGeneralIcon(slide.icon ?? null));
```

- [ ] **Step 2: Envolver el layout `anuncio` (rama `else`)**

Reemplazar el bloque `{:else}` actual (aprox. líneas 96–116) por:

```svelte
{:else}
  <div class="tv-slide" style:background="var(--tv-color-header)">
    <div class="tv-slide-body-row">
      <div class="tv-slide-main">
        <span class="tv-badge bg-white/15 text-white">
          <Megaphone class="size-[1.1em]" />
          {categoryLabel}
        </span>
        <h2 class="tv-slide-title tv-slide-title--wide text-white">{slide.title}</h2>
        {#if description}
          <p class="tv-slide-body text-white/90">{description}</p>
        {/if}
        {#if externalRows.length}
          <div class="tv-metrics" class:tv-metrics--dense={metricsDense}>
            {#each externalRows as [key, value] (key)}
              <div class="tv-metric-row">
                <span class="tv-metric-key">{key}</span>
                <span class="tv-metric-val">{value}</span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
      {#if SlideIcon}
        <div class="tv-slide-visual" aria-hidden="true">
          <SlideIcon class="tv-slide-visual-icon" />
        </div>
      {/if}
    </div>
  </div>
{/if}
```

Si Svelte 5 no acepta `<SlideIcon class=... />`, usar:

```svelte
<svelte:component this={SlideIcon} class="tv-slide-visual-icon" />
```

- [ ] **Step 3: Estilos en `tv.css`**

Después de `.tv-slide { ... }` (o cerca de `.tv-slide-title--wide`), añadir:

```css
.tv-slide-body-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: clamp(1.5rem, 4vw, 3.5rem);
  width: 100%;
  min-height: 0;
}

.tv-slide-main {
  flex: 1 1 auto;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.tv-slide-visual {
  flex: 0 0 auto;
  width: clamp(6.5rem, 14vw, 11rem);
  height: clamp(6.5rem, 14vw, 11rem);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
}

.tv-slide-visual-icon {
  width: 55%;
  height: 55%;
  stroke-width: 1.5;
}
```

- [ ] **Step 4: Verificar en `/tv`**

1. Con un ítem general que tenga `icon=dollar-sign` y sea layout anuncio (p. ej. divisas): icono grande a la derecha.
2. Con ítem sin icono: mismo layout de antes, sin hueco vacío a la derecha.
3. Layouts `afiche` / `evento` sin cambios.

- [ ] **Step 5: Commit (si autorizado)**

```bash
git add frontend/src/lib/components/ui/SlideView.svelte frontend/src/lib/tv.css
git commit -m "$(cat <<'EOF'
feat(tv): render general icon on the right of anuncio slides

EOF
)"
```

---

### Task 7: `.gitignore` + verificación final

**Files:**
- Modify: `.gitignore`

- [ ] **Step 1: Ignorar brainstorm companion**

Añadir al final de `.gitignore`:

```
# Brainstorm / visual companion local
.superpowers/
```

- [ ] **Step 2: Suite de tests**

```bash
composer test -- --filter GeneralIconSanitizeTest
cd frontend && npm test -- tests/unit/generalIcons.test.ts
```

Expected: ambos PASS.

- [ ] **Step 3: Checklist de aceptación (spec)**

- [ ] Formulario: elegir icono / Ninguno y persistir
- [ ] Listado: miniatura cuando hay icono
- [ ] TV: icono a la derecha si hay valor
- [ ] TV: sin valor = sin slot
- [ ] Cron/upsertExternal no pisa iconos existentes (el payload de upsert **no** incluye `icon` → al UPDATE no debe sobrescribir la columna; verificar en `upsertExternal` que el `$payload` no setea `icon`. Si el `update` del Database solo toca claves del array, los iconos manuales se conservan.)
- [ ] Clave inválida vía API → se guarda `NULL`

- [ ] **Step 4: Commit final (si autorizado)**

```bash
git add .gitignore
git commit -m "$(cat <<'EOF'
chore: ignore .superpowers brainstorm artifacts

EOF
)"
```

---

## Spec coverage (self-review)

| Requisito spec | Task |
|----------------|------|
| Columna `icon` + schema/migración | Task 1 |
| Whitelist / vacío → NULL / claves inválidas | Task 1 |
| Cron no asigna icono | Task 1 (upsert sin `icon`) + checklist Task 7 |
| Banco Lucide frontend | Task 3 |
| API TV expone `icon` | Task 2 |
| Tipo `TvSlide.icon` | Task 4 |
| Selector en `GeneralForm` + Ninguno | Task 4 |
| Miniatura listado | Task 5 |
| Slot derecho anuncio; ocultar si vacío | Task 6 |
| No tocar afiche/evento | Task 6 (solo rama else) |
| Sin upload / sin tabla iconos | Cumplido por alcance (no hay tasks) |

No quedan TBD/placeholders en pasos de código.
