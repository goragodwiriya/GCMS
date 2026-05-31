# NOW.JS CSS QUICK REFERENCE FOR AI

> **Purpose**: Compact reference for AI to correctly use Now.js CSS classes and variables.
> **CDN**: `https://cdn.jsdelivr.net/gh/goragodwiriya/nowjs@main/Now/dist/now.core.min.css`

---

## CSS VARIABLES (Design Tokens)

### Colors - Semantic
| Variable | Value | Usage |
|----------|-------|-------|
| `--color-primary` | `#2563eb` | Main brand color |
| `--color-secondary` | `#6b7280` | Secondary accent |
| `--color-success` | `#10b981` | Success states |
| `--color-warning` | `#f59e0b` | Warning states |
| `--color-error` | `#ef4444` | Error states |
| `--color-info` | `#06b6d4` | Info states |

### Colors - Neutral
| Variable | Value |
|----------|-------|
| `--color-white` | `#FFFFFF` |
| `--color-light` | `#EEEEEE` |
| `--color-gray` | `#6C757D` |
| `--color-dark` | `#333333` |
| `--color-black` | `#000000` |

### Colors - Application
| Variable | Usage |
|----------|-------|
| `--color-background` | Page background |
| `--color-surface` | Card/component background |
| `--color-text` | Main text color |
| `--color-text-muted` | Secondary text |
| `--color-border` | Border color |

### Typography
| Variable | Value | Pixels |
|----------|-------|--------|
| `--font-size-xs` | `0.75rem` | 12px |
| `--font-size-sm` | `0.875rem` | 14px |
| `--font-size-base` | `1rem` | 16px |
| `--font-size-lg` | `1.125rem` | 18px |
| `--font-size-xl` | `1.25rem` | 20px |
| `--font-size-2xl` | `1.5rem` | 24px |
| `--font-size-3xl` | `1.875rem` | 30px |
| `--font-size-4xl` | `2.25rem` | 36px |

### Spacing (base: 0.25rem = 4px)
| Variable | Value |
|----------|-------|
| `--space-1` | `0.25rem` (4px) |
| `--space-2` | `0.5rem` (8px) |
| `--space-3` | `0.75rem` (12px) |
| `--space-4` | `1rem` (16px) |
| `--space-6` | `1.5rem` (24px) |
| `--space-8` | `2rem` (32px) |

### Borders & Shadows
| Variable | Value |
|----------|-------|
| `--border-radius` | `0.25rem` (4px) |
| `--border-radius-lg` | `0.5rem` (8px) |
| `--border-radius-full` | `9999px` |
| `--shadow-sm` | Small shadow |
| `--shadow-md` | Medium shadow |
| `--shadow-lg` | Large shadow |

---

## LAYOUT CLASSES

### Flexbox

#### Container
| Class | Effect |
|-------|--------|
| `.flex` | `display: flex` (row) |
| `.inline-flex` | `display: inline-flex` |
| `.flex.row` | Row direction |
| `.flex.column` | Column direction |
| `.flex.reverse` | Row reverse |
| `.flex.column-reverse` | Column reverse |
| `.flex.wrap` | Allow wrapping |
| `.flex.nowrap` | No wrapping |
| `.flex.wrap-reverse` | Wrap reversed |

#### Main axis (justify-content)
| Class | Effect |
|-------|--------|
| `.flex.justify-start` | Start |
| `.flex.justify-end` | End |
| `.flex.justify-center` | Center |
| `.flex.justify-between` | Space between |
| `.flex.justify-around` | Space around |
| `.flex.justify-evenly` | Space evenly |
| `.flex.fullwidth` | Alias of `.flex.justify-between` (legacy) |

#### Cross axis (align-items) — use on `.flex` container
| Class | Effect |
|-------|--------|
| `.flex.top` | Start |
| `.flex.middle` | Center |
| `.flex.bottom` | End |
| `.flex.baseline` | Baseline |
| `.flex.stretch` | Stretch |

#### Shortcuts — both axes
| Class | Effect |
|-------|--------|
| `.flex.left` | Start + start |
| `.flex.center` | Center + center |
| `.flex.right` | End + end |

#### Flex items
| Class | Effect |
|-------|--------|
| `.flex-auto` | `flex: 1 1 auto` |
| `.flex-none` | `flex: 0 0 auto` |
| `.flex-grow` / `.flex-grow-0` | Grow on/off |
| `.flex-shrink` / `.flex-shrink-0` | Shrink on/off |
| `.flex-basis-0` … `.flex-basis-100` | Explicit basis |
| `.flex-basis-auto` | `flex-basis: auto` |

#### Per-item alignment (align-self)
| Class | Effect |
|-------|--------|
| `.self-start` | Align self to start |
| `.self-end` | Align self to end |
| `.self-center` | Align self to center |
| `.self-stretch` | Stretch self |
| `.self-baseline` | Baseline |

#### Gap
| Class | Effect |
|-------|--------|
| `.gap` | `var(--space-1)` |
| `.gap-2` … `.gap-32` | Spacing scale |

```html
<!-- Row with space between -->
<div class="flex justify-between middle gap-4">
  <div>Left</div>
  <div class="self-end">Right</div>
</div>

<!-- Column, top-left -->
<div class="flex column left gap-6">...</div>
```

### Alignment (non-flex)
| Class | Effect |
|-------|--------|
| `.left` | Text align left |
| `.center` | Text align center |
| `.right` | Text align right |
| `.top` | Align items to top |
| `.middle` | Align items to middle |
| `.bottom` | Align items to bottom |

### Display
| Class | Effect |
|-------|--------|
| `.block` | `display: block` |
| `.inline` | `display: inline` |
| `.inline-block` | `display: inline-block` |
| `.hidden` | `display: none !important` |

### Sidebar Layouts
```html
<!-- Left Sidebar -->
<div class="sidebar-left gap">
  <aside class="sidebar">...</aside>
  <main class="content">...</main>
</div>

<!-- Right Sidebar -->
<div class="sidebar-right gap">
  <main class="content">...</main>
  <aside class="sidebar">...</aside>
</div>

<!-- Three Columns -->
<div class="three-columns gap">
  <aside>Left</aside>
  <main class="content">Main</main>
  <aside>Right</aside>
</div>
```

---

## GRID SYSTEM (12 columns)

### Basic Usage
```html
<div class="ggrid">
  <div class="block6">50%</div>
  <div class="block6">50%</div>
</div>
```

### Grid Classes
| Class | Columns | Width |
|-------|---------|-------|
| `.block1` | 1 | 8.33% |
| `.block2` | 2 | 16.66% |
| `.block3` | 3 | 25% |
| `.block4` | 4 | 33.33% |
| `.block5` | 5 | 41.66% |
| `.block6` | 6 | 50% |
| `.block7` | 7 | 58.33% |
| `.block8` | 8 | 66.66% |
| `.block9` | 9 | 75% |
| `.block10` | 10 | 83.33% |
| `.block11` | 11 | 91.66% |
| `.block12` | 12 | 100% |

### Grid Modifiers
| Class | Effect |
|-------|--------|
| `.ggrid` | 12-column grid with default gap |
| `.ggrid.collapse` | No gap |
| `.ggrid.space` | Larger gap |

### Responsive Prefixes
| Prefix | Breakpoint |
|--------|------------|
| `wide*` | > 1440px |
| `xlarge*` | ≤ 1440px |
| `large*` | ≤ 1130px |
| `tablet*` | ≤ 960px |
| `mobile*` | ≤ 480px |

**Example**: `class="block4 tablet6 mobile12"` = 4 cols desktop, 6 tablet, 12 mobile

---

## WIDTH UTILITIES

| Class | Width | Class | Width |
|-------|-------|-------|-------|
| `.width5` | 5% | `.width50` | 50% |
| `.width10` | 10% | `.width60` | 60% |
| `.width15` | 15% | `.width66` | 66.66% |
| `.width20` | 20% | `.width70` | 70% |
| `.width25` | 25% | `.width75` | 75% |
| `.width30` | 30% | `.width80` | 80% |
| `.width33` | 33.33% | `.width90` | 90% |
| `.width40` | 40% | `.width100` | 100% |
| `.fullwidth` | 100% | | |

---

## BUTTONS

### Basic Syntax
```html
<button class="btn">Default</button>
<button class="btn btn-primary">Primary</button>
```

### Button Variants
| Class | Color |
|-------|-------|
| `.btn` | Default (neutral) |
| `.btn-primary` | Blue (primary) |
| `.btn-secondary` | Gray (secondary) |
| `.btn-success` | Green (success) |
| `.btn-warning` | Yellow (warning) |
| `.btn-danger` | Red (error) |
| `.btn-info` | Cyan (info) |

### Button Modifiers
| Class | Effect |
|-------|--------|
| `.outline` | Transparent background, colored border |
| `.text` | Text only, no border/background |
| `.small` | Height 32px |
| `.large` | Height 44px |
| `.pill` | Fully rounded corners |
| `.circle` | Circle shape (36px) |
| `.loading` | Shows spinner |

### Gradient Buttons
| Class | Gradient |
|-------|----------|
| `.btn-gradient-blue` | Blue to purple |
| `.btn-gradient-purple` | Purple to pink |
| `.btn-gradient-green` | Green to cyan |

### Social Buttons
| Class | Platform |
|-------|----------|
| `.btn-facebook` | Facebook blue |
| `.btn-line` | LINE green |
| `.btn-twitter` | Twitter blue |

### Button Groups
```html
<div class="btn-group">
  <button class="btn">Left</button>
  <button class="btn">Middle</button>
  <button class="btn">Right</button>
</div>

<div class="btn-group vertical">
  <button class="btn">Top</button>
  <button class="btn">Bottom</button>
</div>
```

---

## FORM CONTROLS

### Form Control Wrapper
```html
<!-- Basic input -->
<div class="form-control">
  <input type="text" placeholder="Enter text">
</div>

<!-- Input with icon -->
<span class="form-control icon-user">
  <input type="text" placeholder="Username">
</span>

<span class="form-control icon-email">
  <input type="email" placeholder="Email">
</span>

<span class="form-control icon-password">
  <input type="password" placeholder="Password">
</span>
```

### Validation States
| Class | Effect |
|-------|--------|
| `.valid` | Green border |
| `.invalid` | Red border |

### Switch Toggle
```html
<input type="checkbox" class="switch" id="toggle1">
<label for="toggle1">Enable feature</label>
```

### Input Groups (side by side)
```html
<div class="form-group">
  <div class="width50"><input type="text"></div>
  <div class="width50"><input type="text"></div>
</div>
```

---

## COLOR UTILITIES

### Text Colors
| Class | Color |
|-------|-------|
| `.color-white` | White |
| `.color-light` | Light gray |
| `.color-gray` | Gray |
| `.color-dark` | Dark |
| `.color-black` | Black |
| `.color-blue` | Blue |
| `.color-green` | Green |
| `.color-red` | Red |
| `.color-orange` | Orange |
| `.color-purple` | Purple |

### Background Colors
| Class | Color |
|-------|-------|
| `.bg-white` | White |
| `.bg-light` | Light gray |
| `.bg-dark` | Dark |
| `.bg-blue` | Blue |
| `.bg-green` | Green |
| `.bg-red` | Red |

### Status Text
| Class | Color |
|-------|-------|
| `.success` | Success green |
| `.warning` | Warning yellow |
| `.error` | Error red |
| `.info` | Info cyan |

### Status Badges (status0-status11)
```html
<span class="status0">Status 0</span>  <!-- #486F96 -->
<span class="status1">Status 1</span>  <!-- #8D9440 -->
<span class="status2">Status 2</span>  <!-- #D59C30 -->
<!-- ... up to status11 -->
```

---

## VISUAL UTILITIES

### Borders
| Class | Effect |
|-------|--------|
| `.border` | 1px solid border |
| `.rounded` | Rounded corners |
| `.circle` | Fully round (for images/avatars) |

### Shadows
| Class | Effect |
|-------|--------|
| `.shadow-sm` | Small shadow |
| `.shadow-md` | Medium shadow |
| `.shadow-lg` | Large shadow |

### Text
| Class | Effect |
|-------|--------|
| `.big` | 1.2em font size |
| `.small` | 0.9em font size |
| `.comment` | Green comment text |
| `.nowrap` | Prevent text wrap |
| `.wrap` | Allow text wrap |

---

## BADGES

```html
<span class="badge-success" data-badge="Active">Status:</span>
<span class="badge-error" data-badge="Failed">Status:</span>
<span class="badge-warning" data-badge="Pending">Status:</span>
<span class="badge-info" data-badge="Info">Status:</span>
```

---

## TABLE UTILITIES

| Class | Effect |
|-------|--------|
| `.sortable` | Sortable column header |
| `.sort_asc` | Ascending sort indicator |
| `.sort_desc` | Descending sort indicator |
| `.table_nav` | Table navigation wrapper |
| `.splitpage` | Pagination wrapper |
| `.row-actions` | Center-aligned row actions |
| `.row-actions-cell` | Right-aligned action cell |

---

## DARK THEME

Enable with `data-theme="dark"` on `<html>` or `<body>`:

```html
<html data-theme="dark">
```

Dark theme automatically adjusts:
- `--color-background` → `#0f172a`
- `--color-surface` → `#1e293b`
- `--color-text` → `#f8fafc`
- `--color-border` → `#334155`

---

## COMMON PATTERNS

### Card
```html
<div class="border rounded shadow-md" style="background: var(--card-bg); padding: var(--space-4);">
  Card content
</div>
```

### Centered Content
```html
<div class="flex center middle" style="height: 100vh;">
  Centered content
</div>
```

### Form Layout
```html
<div class="form-group">
  <div class="width50">
    <span class="form-control"><input type="text"></span>
  </div>
  <div class="width50">
    <span class="form-control"><input type="text"></span>
  </div>
</div>
```

### Responsive Grid
```html
<div class="ggrid">
  <div class="block4 tablet6 mobile12">Item 1</div>
  <div class="block4 tablet6 mobile12">Item 2</div>
  <div class="block4 tablet12 mobile12">Item 3</div>
</div>
```

### Button with Icon
```html
<button class="btn btn-primary icon-save">Save</button>
```

---

## Z-INDEX LAYERS

| Layer | Variable | Value |
|-------|----------|-------|
| Dropdown | `--z-index-dropdown` | 1000 |
| Popover | `--z-index-popover` | 1050 |
| Modal | `--z-index-modal` | 1080 |
| Toast | `--z-index-toast` | 1090 |
| Alert | `--z-index-alert` | 1100 |
