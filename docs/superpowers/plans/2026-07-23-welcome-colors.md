# Welcome per-block colors — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hacer personalizable por bloque cada color del slide de Bienvenida (panel, tv-modern y `/tv` Signage), eliminando hardcodes CSS que ignoran la plantilla.

**Architecture:** Ampliar claves `welcome_*` en `display_config` con un color por elemento; `getWelcome` / `mergeWelcome` aplican fallback desde `text_color`/`accent_color` legacy; el slide inyecta variables CSS `--welcome-*` y el CSS (Svelte + ES5) las consume. Sin presets ni cambios de tipografía.

**Tech Stack:** PHP 8 (`DisplayConfig`, `WelcomeController`, `TvController`), SvelteKit/Svelte 5, Vitest, TV Signage ES5 en `frontend/static/tv/index.html`.

**Spec:** `docs/superpowers/specs/2026-07-23-welcome-colors-design.md`

## Global Constraints

- Hex solo `#RRGGBB` (validación existente en `WelcomeController`).
- Signage: Chromium 53 / ES5 — sin `color-mix`, sin módulos ES, sin `async/await` nativo.
- Fuente canónica TV: `frontend/static/tv/` (luego `npm run build` → `public/tv/`).
- No exponer `text_color` en el formulario admin; al guardar sincronizar `welcome_text_color = lema_color`.
- Hashtag: texto sigue blanco; solo se personaliza el fondo de la pill.

---

## File map

| File | Responsibility |
|------|----------------|
| `frontend/src/lib/tv/welcome.ts` | Tipo, defaults, `resolveWelcomeColors`, `mergeWelcome` |
| `frontend/tests/unit/welcome.test.ts` | Defaults + fallback legacy |
| `src/Models/DisplayConfig.php` | `welcomeDefaults`, `getWelcome`, `welcomeEditableKeys` |
| `tests/Unit/WelcomeSanitizeTest.php` | Shape defaults + keys nuevas |
| `src/Api/WelcomeController.php` | Lista `$colorKeys` ampliada |
| `src/Api/TvController.php` | Payload welcome del feed `/api/tv` |
| `frontend/src/lib/components/ui/WelcomeSlide.svelte` | Emitir variables CSS |
| `frontend/src/lib/tv.css` | Consumir variables; quitar hardcodes |
| `frontend/src/lib/tv/types.ts` | Campos color en `TvSlide` |
| `frontend/src/lib/components/ui/SlideView.svelte` | Pasar colores a `WelcomeSlide` |
| `frontend/src/lib/tv/carousel.ts` | Fallback slide welcome con colores nuevos |
| `frontend/src/routes/admin/bienvenida/+page.svelte` | Pickers + restaurar colores + PUT |
| `frontend/static/tv/index.html` | CSS ES5 + style vars en render bienvenida |

---

### Task 1: Helpers TS — colores por bloque + fallback

**Files:**
- Modify: `frontend/src/lib/tv/welcome.ts`
- Modify: `frontend/tests/unit/welcome.test.ts`

**Interfaces:**
- Produces: `WelcomeTemplate` con `saludo_color`, `titulo_color`, `centro_color`, `regional_color`, `lema_color`, `hashtag_color`, `pie_color`, `deco_color` (más `bg_color`, `accent_color`, `text_color` legacy).
- Produces: `resolveWelcomeColors(partial):` objeto con todos los colores resueltos.
- Produces: `mergeWelcome` que aplica `resolveWelcomeColors` sobre el merge.

- [ ] **Step 1: Escribir tests que fallen**

Añadir a `frontend/tests/unit/welcome.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import {
  mergeWelcome,
  resolveWelcomeColors,
  welcomeTituloHtml,
  WELCOME_DEFAULTS
} from '../../src/lib/tv/welcome';

describe('welcome template helpers', () => {
  it('mergeWelcome applies defaults', () => {
    expect(mergeWelcome(null).saludo).toBe(WELCOME_DEFAULTS.saludo);
    expect(mergeWelcome({ saludo: 'Hola' }).saludo).toBe('Hola');
    expect(mergeWelcome({ saludo: 'Hola' }).accent_color).toBe('#39a900');
  });

  it('welcomeTituloHtml escapes and breaks lines', () => {
    expect(welcomeTituloHtml('A\nB <x>')).toBe('A<br>B &lt;x&gt;');
  });

  it('WELCOME_DEFAULTS includes per-block colors', () => {
    expect(WELCOME_DEFAULTS.saludo_color).toBe('#39a900');
    expect(WELCOME_DEFAULTS.titulo_color).toBe('#39a900');
    expect(WELCOME_DEFAULTS.centro_color).toBe('#4d4d4d');
    expect(WELCOME_DEFAULTS.regional_color).toBe('#6b6b6b');
    expect(WELCOME_DEFAULTS.lema_color).toBe('#4d4d4d');
    expect(WELCOME_DEFAULTS.hashtag_color).toBe('#39a900');
    expect(WELCOME_DEFAULTS.pie_color).toBe('#6b6b6b');
    expect(WELCOME_DEFAULTS.deco_color).toBe('#a6ce39');
  });

  it('resolveWelcomeColors falls back from legacy text/accent', () => {
    const c = resolveWelcomeColors({
      accent_color: '#112233',
      text_color: '#445566'
    });
    expect(c.saludo_color).toBe('#112233');
    expect(c.titulo_color).toBe('#112233');
    expect(c.hashtag_color).toBe('#112233');
    expect(c.centro_color).toBe('#445566');
    expect(c.regional_color).toBe('#445566');
    expect(c.lema_color).toBe('#445566');
    expect(c.pie_color).toBe('#445566');
    expect(c.deco_color).toBe('#a6ce39');
  });

  it('resolveWelcomeColors keeps explicit per-block colors', () => {
    const c = resolveWelcomeColors({
      accent_color: '#112233',
      text_color: '#445566',
      titulo_color: '#ff0000',
      deco_color: '#00ff00'
    });
    expect(c.titulo_color).toBe('#ff0000');
    expect(c.deco_color).toBe('#00ff00');
    expect(c.saludo_color).toBe('#112233');
  });

  it('mergeWelcome resolves colors after partial merge', () => {
    const w = mergeWelcome({ text_color: '#abcdef', accent_color: '#fedcba' });
    expect(w.lema_color).toBe('#abcdef');
    expect(w.titulo_color).toBe('#fedcba');
  });
});
```

- [ ] **Step 2: Correr test y ver fallar**

Run: `cd frontend && npm test -- --run tests/unit/welcome.test.ts`  
Expected: FAIL (faltan campos / `resolveWelcomeColors`).

- [ ] **Step 3: Implementar en `welcome.ts`**

Reemplazar el contenido de tipos/defaults/merge por:

```ts
/** Defaults institucionales (deben coincidir con DisplayConfig::welcomeDefaults). */
export type WelcomeTemplate = {
  saludo: string;
  titulo: string;
  centro: string;
  regional: string;
  lema: string;
  hashtag: string;
  pie: string;
  duration: number;
  bg_color: string;
  accent_color: string;
  /** Legacy: color de texto único; ya no se edita en UI. */
  text_color: string;
  saludo_color: string;
  titulo_color: string;
  centro_color: string;
  regional_color: string;
  lema_color: string;
  hashtag_color: string;
  pie_color: string;
  deco_color: string;
  bg_path?: string;
  bg_url?: string | null;
};

export type WelcomeColors = Pick<
  WelcomeTemplate,
  | 'bg_color'
  | 'accent_color'
  | 'text_color'
  | 'saludo_color'
  | 'titulo_color'
  | 'centro_color'
  | 'regional_color'
  | 'lema_color'
  | 'hashtag_color'
  | 'pie_color'
  | 'deco_color'
>;

export const WELCOME_DEFAULTS: WelcomeTemplate = {
  saludo: 'El conocimiento es de todos',
  titulo: '¡Te damos la\nbienvenida!',
  centro: 'Centro de Servicios Empresariales y Turísticos',
  regional: 'Regional Santander',
  lema: '¿Y si <b>este fuera tu momento</b>?',
  hashtag: '#SigueTuMomentoSENA',
  pie: 'Prepárate · Fórmate · <b>Abre nuevas oportunidades</b>',
  duration: 12,
  bg_color: '#eaf4e1',
  accent_color: '#39a900',
  text_color: '#4d4d4d',
  saludo_color: '#39a900',
  titulo_color: '#39a900',
  centro_color: '#4d4d4d',
  regional_color: '#6b6b6b',
  lema_color: '#4d4d4d',
  hashtag_color: '#39a900',
  pie_color: '#6b6b6b',
  deco_color: '#a6ce39',
  bg_path: '',
  bg_url: ''
};

function pickColor(value: string | undefined | null, fallback: string): string {
  return value && value.trim() !== '' ? value : fallback;
}

/** Resuelve colores por bloque con fallback legacy text/accent. */
export function resolveWelcomeColors(
  partial?: Partial<WelcomeTemplate> | null
): WelcomeColors {
  const p = partial ?? {};
  const accent = pickColor(p.accent_color, WELCOME_DEFAULTS.accent_color);
  const text = pickColor(p.text_color, WELCOME_DEFAULTS.text_color);
  return {
    bg_color: pickColor(p.bg_color, WELCOME_DEFAULTS.bg_color),
    accent_color: accent,
    text_color: text,
    saludo_color: pickColor(p.saludo_color, accent),
    titulo_color: pickColor(p.titulo_color, accent),
    hashtag_color: pickColor(p.hashtag_color, accent),
    centro_color: pickColor(p.centro_color, text),
    regional_color: pickColor(p.regional_color, text),
    lema_color: pickColor(p.lema_color, text),
    pie_color: pickColor(p.pie_color, text),
    deco_color: pickColor(p.deco_color, WELCOME_DEFAULTS.deco_color)
  };
}

export function mergeWelcome(partial?: Partial<WelcomeTemplate> | null): WelcomeTemplate {
  const merged = { ...WELCOME_DEFAULTS, ...(partial ?? {}) };
  return { ...merged, ...resolveWelcomeColors(merged) };
}

/** Título con saltos de línea → HTML escapado + <br>. */
export function welcomeTituloHtml(titulo: string): string {
  return titulo
    .split(/\r\n|\r|\n/)
    .map((line) =>
      line
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
    )
    .join('<br>');
}
```

- [ ] **Step 4: Correr tests**

Run: `cd frontend && npm test -- --run tests/unit/welcome.test.ts`  
Expected: PASS

- [ ] **Step 5: Commit** (solo si el usuario pidió commits; si no, omitir)

```bash
git add frontend/src/lib/tv/welcome.ts frontend/tests/unit/welcome.test.ts
git commit -m "$(cat <<'EOF'
feat(welcome): resolve per-block colors with legacy fallback

EOF
)"
```

---

### Task 2: Backend DisplayConfig — defaults, lectura y keys editables

**Files:**
- Modify: `src/Models/DisplayConfig.php` (`welcomeDefaults`, `getWelcome`, `welcomeEditableKeys`)
- Modify: `tests/Unit/WelcomeSanitizeTest.php`

**Interfaces:**
- Consumes: claves BD `welcome_*_color` (pueden no existir aún).
- Produces: `getWelcome(): array` con todos los campos de color de la spec.

- [ ] **Step 1: Ampliar test de shape**

En `tests/Unit/WelcomeSanitizeTest.php`, actualizar `test_welcome_defaults_shape` y añadir asserts:

```php
    public function test_welcome_defaults_shape(): void {
        $d = \DisplayConfig::welcomeDefaults();
        $this->assertSame('El conocimiento es de todos', $d['saludo']);
        $this->assertSame(12, $d['duration']);
        $this->assertSame('#39a900', $d['accent_color']);
        $this->assertSame('#39a900', $d['saludo_color']);
        $this->assertSame('#39a900', $d['titulo_color']);
        $this->assertSame('#4d4d4d', $d['centro_color']);
        $this->assertSame('#6b6b6b', $d['regional_color']);
        $this->assertSame('#4d4d4d', $d['lema_color']);
        $this->assertSame('#39a900', $d['hashtag_color']);
        $this->assertSame('#6b6b6b', $d['pie_color']);
        $this->assertSame('#a6ce39', $d['deco_color']);
    }

    public function test_welcome_editable_keys_include_block_colors(): void {
        $keys = \DisplayConfig::welcomeEditableKeys();
        foreach ([
            'welcome_saludo_color',
            'welcome_titulo_color',
            'welcome_centro_color',
            'welcome_regional_color',
            'welcome_lema_color',
            'welcome_hashtag_color',
            'welcome_pie_color',
            'welcome_deco_color',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }
```

- [ ] **Step 2: Correr PHPUnit y ver fallar**

Run: `composer test -- --filter WelcomeSanitizeTest`  
Expected: FAIL (keys/campos ausentes).

- [ ] **Step 3: Actualizar `welcomeDefaults`**

Añadir a `DisplayConfig::welcomeDefaults()` (junto a los colores existentes):

```php
            'saludo_color'   => '#39a900',
            'titulo_color'   => '#39a900',
            'centro_color'   => '#4d4d4d',
            'regional_color' => '#6b6b6b',
            'lema_color'     => '#4d4d4d',
            'hashtag_color'  => '#39a900',
            'pie_color'      => '#6b6b6b',
            'deco_color'     => '#a6ce39',
```

- [ ] **Step 4: Actualizar `getWelcome` con fallback**

Tras leer `accent_color` y `text_color`, resolver bloque a bloque. Sustituir el `return [...]` de colores por lógica equivalente a:

```php
        $accent = self::get('welcome_accent_color', $d['accent_color']) ?? $d['accent_color'];
        $text = self::get('welcome_text_color', $d['text_color']) ?? $d['text_color'];

        $pick = static function (?string $stored, string $fallback): string {
            if ($stored === null || $stored === '') {
                return $fallback;
            }
            return $stored;
        };

        return [
            // ... textos y duration iguales ...
            'bg_color'       => self::get('welcome_bg_color', $d['bg_color']) ?? $d['bg_color'],
            'accent_color'   => $accent,
            'text_color'     => $text,
            'saludo_color'   => $pick(self::get('welcome_saludo_color'), $accent),
            'titulo_color'   => $pick(self::get('welcome_titulo_color'), $accent),
            'hashtag_color'  => $pick(self::get('welcome_hashtag_color'), $accent),
            'centro_color'   => $pick(self::get('welcome_centro_color'), $text),
            'regional_color' => $pick(self::get('welcome_regional_color'), $text),
            'lema_color'     => $pick(self::get('welcome_lema_color'), $text),
            'pie_color'      => $pick(self::get('welcome_pie_color'), $text),
            'deco_color'     => $pick(self::get('welcome_deco_color'), $d['deco_color']),
            'bg_path'        => self::get('welcome_bg_path', '') ?? '',
            'bg_url'         => self::get('welcome_bg_url', '') ?? '',
        ];
```

Mantener el resto de campos de texto/duration como están.

- [ ] **Step 5: Ampliar `welcomeEditableKeys`**

Añadir:

```php
            'welcome_saludo_color',
            'welcome_titulo_color',
            'welcome_centro_color',
            'welcome_regional_color',
            'welcome_lema_color',
            'welcome_hashtag_color',
            'welcome_pie_color',
            'welcome_deco_color',
```

Conservar `welcome_text_color` y `welcome_accent_color` / `welcome_bg_color`.

- [ ] **Step 6: Correr PHPUnit**

Run: `composer test -- --filter WelcomeSanitizeTest`  
Expected: PASS

- [ ] **Step 7: Commit** (si el usuario pidió commits)

```bash
git add src/Models/DisplayConfig.php tests/Unit/WelcomeSanitizeTest.php
git commit -m "$(cat <<'EOF'
feat(api): welcome per-block color keys with legacy fallback

EOF
)"
```

---

### Task 3: API — validar colores y exponerlos en `/api/tv`

**Files:**
- Modify: `src/Api/WelcomeController.php` (array `$colorKeys`)
- Modify: `src/Api/TvController.php` (payload `scenarios.welcome[0]`)

**Interfaces:**
- Consumes: `DisplayConfig::getWelcome()` con los nuevos campos.
- Produces: PUT acepta `*_color` nuevos; feed TV los incluye.

- [ ] **Step 1: Ampliar `$colorKeys` en `WelcomeController::update`**

```php
        $colorKeys = [
            'welcome_bg_color',
            'welcome_accent_color',
            'welcome_text_color',
            'welcome_saludo_color',
            'welcome_titulo_color',
            'welcome_centro_color',
            'welcome_regional_color',
            'welcome_lema_color',
            'welcome_hashtag_color',
            'welcome_pie_color',
            'welcome_deco_color',
        ];
```

- [ ] **Step 2: Ampliar payload en `TvController::index`**

En el array del slide welcome, tras `text_color`, añadir:

```php
                'saludo_color'   => $welcome['saludo_color'],
                'titulo_color'   => $welcome['titulo_color'],
                'centro_color'   => $welcome['centro_color'],
                'regional_color' => $welcome['regional_color'],
                'lema_color'     => $welcome['lema_color'],
                'hashtag_color'  => $welcome['hashtag_color'],
                'pie_color'      => $welcome['pie_color'],
                'deco_color'     => $welcome['deco_color'],
```

- [ ] **Step 3: Verificar tests API TV si existen asserts estrictos de keys**

Run: `composer test -- --filter TvApiTest`  
Si falla por shape estricto, actualizar el test para aceptar/exigir las nuevas keys.  
Expected: PASS

- [ ] **Step 4: Commit** (si el usuario pidió commits)

```bash
git add src/Api/WelcomeController.php src/Api/TvController.php tests/Api/TvApiTest.php
git commit -m "$(cat <<'EOF'
feat(api): accept and broadcast welcome block colors

EOF
)"
```

---

### Task 4: Slide Svelte + CSS — variables por bloque

**Files:**
- Modify: `frontend/src/lib/components/ui/WelcomeSlide.svelte`
- Modify: `frontend/src/lib/tv.css` (bloque `.tv-slide--bienvenida` y derivados)
- Modify: `frontend/src/lib/tv/types.ts`
- Modify: `frontend/src/lib/components/ui/SlideView.svelte`
- Modify: `frontend/src/lib/tv/carousel.ts` (fallback welcome)

**Interfaces:**
- Consumes: `mergeWelcome` / campos `*_color` del slide.
- Produces: style con `--welcome-saludo`, `--welcome-titulo`, etc.

- [ ] **Step 1: Ampliar `TvSlide` en `types.ts`**

```ts
  bg_color?: string;
  accent_color?: string;
  text_color?: string;
  saludo_color?: string;
  titulo_color?: string;
  centro_color?: string;
  regional_color?: string;
  lema_color?: string;
  hashtag_color?: string;
  pie_color?: string;
  deco_color?: string;
  bg_url?: string | null;
```

- [ ] **Step 2: Pasar colores en `SlideView.svelte`**

En el `data={{...}}` de `WelcomeSlide`, añadir:

```ts
      saludo_color: slide.saludo_color,
      titulo_color: slide.titulo_color,
      centro_color: slide.centro_color,
      regional_color: slide.regional_color,
      lema_color: slide.lema_color,
      hashtag_color: slide.hashtag_color,
      pie_color: slide.pie_color,
      deco_color: slide.deco_color,
```

- [ ] **Step 3: Actualizar `styleVars` en `WelcomeSlide.svelte`**

```ts
  let styleVars = $derived(
    [
      `--welcome-bg:${w.bg_color}`,
      `--welcome-saludo:${w.saludo_color}`,
      `--welcome-titulo:${w.titulo_color}`,
      `--welcome-centro:${w.centro_color}`,
      `--welcome-regional:${w.regional_color}`,
      `--welcome-lema:${w.lema_color}`,
      `--welcome-accent:${w.accent_color}`,
      `--welcome-hashtag:${w.hashtag_color}`,
      `--welcome-pie:${w.pie_color}`,
      `--welcome-deco:${w.deco_color}`,
      w.bg_url ? `--welcome-bg-image:url(${JSON.stringify(String(w.bg_url))})` : null
    ]
      .filter(Boolean)
      .join(';')
  );
```

- [ ] **Step 4: Reemplazar hardcodes en `tv.css`**

Cambios concretos (buscar y sustituir en el bloque bienvenida):

| Selector | Antes (hardcode) | Después |
|----------|------------------|---------|
| `.tv-bienv-saludo` | `color: #39a900` | `color: var(--welcome-saludo, #39a900)` |
| `.tv-bienv-titulo` | `color: #39a900` | `color: var(--welcome-titulo, #39a900)` |
| `.tv-bienv-centro` | `color: #4d4d4d` | `color: var(--welcome-centro, #4d4d4d)` |
| `.tv-bienv-regional` | `color: #6b6b6b` | `color: var(--welcome-regional, #6b6b6b)` |
| `.tv-bienv-lema` | `var(--welcome-text, …)` | `color: var(--welcome-lema, #4d4d4d)` |
| `.tv-bienv-lema b` | accent (ok) | sin cambio (`--welcome-accent`) |
| `.tv-bienv-hashtag` | `background: var(--welcome-accent)` | `background: var(--welcome-hashtag, #39a900)` |
| `.tv-bienv-hashtag` box-shadow | `rgba(57, 169, 0, 0.28)` | `rgba(0, 0, 0, 0.22)` |
| `.tv-bienv-pie` | `color: #6b6b6b` | `color: var(--welcome-pie, #6b6b6b)` |
| `.tv-bienv-pie b` | `color: #2d8500` | `color: var(--welcome-accent, #39a900)` |
| `.tv-bienv-divisor` | gradient accent + `#a6ce39` | `linear-gradient(90deg, var(--welcome-accent, #39a900), var(--welcome-deco, #a6ce39))` |
| `.tv-bienv-aro` | `border: … rgba(57,169,0,.1)` | `border: 2px solid var(--welcome-deco, #a6ce39); opacity: 0.25` (ajustar opacity del aro, no del border rgba) |
| `.tv-bienv-aro--2` | `border-color: rgba(141,198,63,.14)` | quitar override de color; opcional `opacity: 0.18` |
| `.tv-bienv-chispa--lima path` | `stroke: #a6ce39` | `stroke: var(--welcome-deco, #a6ce39)` |
| `.tv-bienv-trazo` | `stroke: #39a900` | `stroke: var(--welcome-accent, #39a900)` |

En `.tv-slide--bienvenida` se puede dejar `color: var(--welcome-lema, #4d4d4d)` en lugar de `--welcome-text`.

- [ ] **Step 5: Actualizar fallback en `carousel.ts`**

En el objeto welcome sintético, añadir defaults de color (mismos hex que `WELCOME_DEFAULTS`) o importar `WELCOME_DEFAULTS` y spread de colores. Preferible:

```ts
import { WELCOME_DEFAULTS } from './welcome';
// ...
scenarios.welcome = [
  {
    id: 0,
    title: 'Bienvenida',
    layout: 'bienvenida',
    duration: WELCOME_DEFAULTS.duration,
    saludo: WELCOME_DEFAULTS.saludo,
    titulo: WELCOME_DEFAULTS.titulo,
    centro: WELCOME_DEFAULTS.centro,
    regional: WELCOME_DEFAULTS.regional,
    lema: WELCOME_DEFAULTS.lema,
    hashtag: WELCOME_DEFAULTS.hashtag,
    pie: WELCOME_DEFAULTS.pie,
    bg_color: WELCOME_DEFAULTS.bg_color,
    accent_color: WELCOME_DEFAULTS.accent_color,
    text_color: WELCOME_DEFAULTS.text_color,
    saludo_color: WELCOME_DEFAULTS.saludo_color,
    titulo_color: WELCOME_DEFAULTS.titulo_color,
    centro_color: WELCOME_DEFAULTS.centro_color,
    regional_color: WELCOME_DEFAULTS.regional_color,
    lema_color: WELCOME_DEFAULTS.lema_color,
    hashtag_color: WELCOME_DEFAULTS.hashtag_color,
    pie_color: WELCOME_DEFAULTS.pie_color,
    deco_color: WELCOME_DEFAULTS.deco_color
  }
];
```

- [ ] **Step 6: Tests unitarios existentes**

Run: `cd frontend && npm test -- --run tests/unit/welcome.test.ts tests/unit/carousel.test.ts`  
Expected: PASS (ajustar carousel test solo si aserta shape del fallback).

- [ ] **Step 7: Commit** (si el usuario pidió commits)

```bash
git add frontend/src/lib/components/ui/WelcomeSlide.svelte frontend/src/lib/tv.css frontend/src/lib/tv/types.ts frontend/src/lib/components/ui/SlideView.svelte frontend/src/lib/tv/carousel.ts
git commit -m "$(cat <<'EOF'
feat(tv): wire welcome CSS variables per text block

EOF
)"
```

---

### Task 5: Panel admin Bienvenida — UI de colores

**Files:**
- Modify: `frontend/src/routes/admin/bienvenida/+page.svelte`

**Interfaces:**
- Consumes: `WelcomeTemplate` / `WELCOME_DEFAULTS`.
- Produces: PUT con todos los `*_color` + `text_color: form.lema_color`.

- [ ] **Step 1: Definir lista de pickers y `restoreColors`**

En el `<script>`, tras el estado:

```ts
  const COLOR_FIELDS: { key: keyof WelcomeTemplate; label: string }[] = [
    { key: 'bg_color', label: 'Fondo' },
    { key: 'saludo_color', label: 'Saludo' },
    { key: 'titulo_color', label: 'Título' },
    { key: 'centro_color', label: 'Centro' },
    { key: 'regional_color', label: 'Regional' },
    { key: 'lema_color', label: 'Lema' },
    { key: 'accent_color', label: 'Negrita (lema/pie)' },
    { key: 'hashtag_color', label: 'Hashtag' },
    { key: 'pie_color', label: 'Pie' },
    { key: 'deco_color', label: 'Decoración' }
  ];

  function restoreColors() {
    form = {
      ...form,
      bg_color: WELCOME_DEFAULTS.bg_color,
      accent_color: WELCOME_DEFAULTS.accent_color,
      text_color: WELCOME_DEFAULTS.text_color,
      saludo_color: WELCOME_DEFAULTS.saludo_color,
      titulo_color: WELCOME_DEFAULTS.titulo_color,
      centro_color: WELCOME_DEFAULTS.centro_color,
      regional_color: WELCOME_DEFAULTS.regional_color,
      lema_color: WELCOME_DEFAULTS.lema_color,
      hashtag_color: WELCOME_DEFAULTS.hashtag_color,
      pie_color: WELCOME_DEFAULTS.pie_color,
      deco_color: WELCOME_DEFAULTS.deco_color
    };
  }
```

Actualizar `restoreDefaults` para **no** tocar colores (solo textos + duration), p. ej.:

```ts
  function restoreDefaults() {
    form = {
      ...form,
      saludo: WELCOME_DEFAULTS.saludo,
      titulo: WELCOME_DEFAULTS.titulo,
      centro: WELCOME_DEFAULTS.centro,
      regional: WELCOME_DEFAULTS.regional,
      lema: WELCOME_DEFAULTS.lema,
      hashtag: WELCOME_DEFAULTS.hashtag,
      pie: WELCOME_DEFAULTS.pie,
      duration: WELCOME_DEFAULTS.duration
    };
  }
```

- [ ] **Step 2: Ampliar payload de `save()`**

Incluir todos los colores y sincronizar legacy:

```ts
          welcome: {
            saludo: form.saludo,
            titulo: form.titulo,
            centro: form.centro,
            regional: form.regional,
            lema: form.lema,
            hashtag: form.hashtag,
            pie: form.pie,
            duration: form.duration,
            bg_color: form.bg_color,
            accent_color: form.accent_color,
            text_color: form.lema_color,
            saludo_color: form.saludo_color,
            titulo_color: form.titulo_color,
            centro_color: form.centro_color,
            regional_color: form.regional_color,
            lema_color: form.lema_color,
            hashtag_color: form.hashtag_color,
            pie_color: form.pie_color,
            deco_color: form.deco_color
          }
```

Tras cargar API: `form = mergeWelcome(res.welcome)` (importar `mergeWelcome`) para aplicar fallbacks si el backend aún no tiene claves nuevas.

- [ ] **Step 3: Reemplazar sección Colores en el markup**

Quitar los 3 pickers actuales. Sustituir por:

```svelte
        <h2 class="pt-2 text-sm font-semibold uppercase tracking-wide text-surface-muted">Colores</h2>
        <div class="grid gap-3 sm:grid-cols-2">
          {#each COLOR_FIELDS as field}
            <label class="text-sm">
              <span class="mb-1 block text-surface-muted">{field.label}</span>
              <div class="flex items-center gap-2">
                <input
                  type="color"
                  class="h-9 w-12 cursor-pointer rounded border border-surface-border bg-transparent p-0"
                  bind:value={form[field.key]}
                />
                <input class="input w-full font-mono text-xs" bind:value={form[field.key]} maxlength="7" />
              </div>
            </label>
          {/each}
        </div>
```

Nota: `bind:value={form[field.key]}` puede requerir cast/asociación tipada en Svelte 5; si el checker se queja, usar helper:

```ts
  function colorValue(key: keyof WelcomeTemplate): string {
    const v = form[key];
    return typeof v === 'string' ? v : '';
  }
  function setColor(key: keyof WelcomeTemplate, value: string) {
    form = { ...form, [key]: value };
  }
```

y bindings `value={colorValue(field.key)}` / `oninput`.

Añadir botón junto a «Restaurar textos»:

```svelte
      <button type="button" class="btn btn-outline" onclick={restoreColors}>Restaurar colores</button>
```

- [ ] **Step 4: Verificación manual rápida**

Abrir `/cset/admin/bienvenida`, cambiar «Título» a rojo → la preview debe mostrar el título rojo sin guardar. Guardar y recargar → persiste.

- [ ] **Step 5: Commit** (si el usuario pidió commits)

```bash
git add frontend/src/routes/admin/bienvenida/+page.svelte
git commit -m "$(cat <<'EOF'
feat(admin): per-block welcome color pickers and restore

EOF
)"
```

---

### Task 6: Signage ES5 — CSS + render de variables

**Files:**
- Modify: `frontend/static/tv/index.html` (CSS bienvenida + función que arma el HTML del slide)

**Interfaces:**
- Consumes: campos `*_color` del slide en el feed.
- Produces: mismos `--welcome-*` que Svelte.

- [ ] **Step 1: Actualizar construcción de `style` en el render bienvenida**

Sustituir el bloque que hoy usa solo `bg` / `accent` / `text` por:

```js
    var bg = s.bg_color || '#eaf4e1';
    var accent = s.accent_color || '#39a900';
    var text = s.text_color || '#4d4d4d';
    var saludoColor = s.saludo_color || accent;
    var tituloColor = s.titulo_color || accent;
    var hashtagColor = s.hashtag_color || accent;
    var centroColor = s.centro_color || text;
    var regionalColor = s.regional_color || text;
    var lemaColor = s.lema_color || text;
    var pieColor = s.pie_color || text;
    var decoColor = s.deco_color || '#a6ce39';
    var bgUrl = s.bg_url ? fixAssetUrl(s.bg_url) : '';
    var hasBg = !!bgUrl;
    var style =
      '--welcome-bg:' +
      bg +
      ';--welcome-saludo:' +
      saludoColor +
      ';--welcome-titulo:' +
      tituloColor +
      ';--welcome-centro:' +
      centroColor +
      ';--welcome-regional:' +
      regionalColor +
      ';--welcome-lema:' +
      lemaColor +
      ';--welcome-accent:' +
      accent +
      ';--welcome-hashtag:' +
      hashtagColor +
      ';--welcome-pie:' +
      pieColor +
      ';--welcome-deco:' +
      decoColor;
```

- [ ] **Step 2: Espejar cambios CSS de Task 4 en el `<style>` de `index.html`**

Aplicar las mismas sustituciones de hardcodes → `var(--welcome-*)` (incl. hashtag background, pie, divisor, chispas lima, aros con opacity, sombra neutra). Sin `color-mix`.

- [ ] **Step 3: Build frontend**

Run: `cd frontend && npm run build`  
Expected: copia `frontend/static/tv/` → `public/tv/` sin errores.

- [ ] **Step 4: Commit** (si el usuario pidió commits; incluir `public/tv` solo si el repo versiona el build TV)

```bash
git add frontend/static/tv/index.html
git commit -m "$(cat <<'EOF'
feat(tv-signage): apply welcome per-block CSS variables

EOF
)"
```

---

### Task 7: Verificación final

**Files:** ninguno nuevo (solo corridas).

- [ ] **Step 1: Tests**

```bash
composer test -- --filter 'WelcomeSanitizeTest|TvApiTest'
cd frontend && npm test -- --run tests/unit/welcome.test.ts tests/unit/carousel.test.ts
```

Expected: PASS

- [ ] **Step 2: Checklist manual (criterios de la spec)**

1. `/cset/admin/bienvenida` muestra 10 controles de color.
2. Cambiar título → preview inmediata.
3. Guardar → `/cset/tv-modern` y `/cset/tv/` reflejan el color.
4. «Restaurar colores» no borra textos ni imagen.
5. Instalación sin claves nuevas: colores coherentes vía fallback accent/text.

- [ ] **Step 3: Commit final de docs/plan checkboxes solo si se actualiza el plan en repo** (opcional)

---

## Spec coverage (self-review)

| Requisito spec | Task |
|----------------|------|
| Colores por bloque + defaults | 1, 2 |
| Fallback legacy text/accent | 1, 2, 6 |
| No exponer text_color; sync lema→text al guardar | 5 |
| Variables CSS listadas | 4, 6 |
| UI pickers + restore colores/textos | 5 |
| CSS sin hardcodes (Svelte + Signage) | 4, 6 |
| Feed `/api/tv` | 3 |
| Tests unitarios | 1, 2, 7 |
| Alcance A (panel + tv-modern + /tv) | 4–6 |
| Fuera de alcance (presets, tipografía, tema panel) | no hay tasks |
