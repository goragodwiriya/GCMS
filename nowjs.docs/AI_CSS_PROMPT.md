# AI Design Prompt Template for Now.js CSS

Use this prompt when asking AI to design UI with Now.js CSS Framework.

---

## Copy This Prompt:

```
I need you to design/create [DESCRIBE YOUR UI HERE] using the Now.js CSS Framework.

**IMPORTANT RULES:**
1. Use ONLY Now.js CSS classes and variables (no Bootstrap, Tailwind, or other frameworks)
2. Include this CSS: `<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/goragodwiriya/nowjs@main/Now/dist/now.core.min.css">`

**Available CSS Classes:**

LAYOUT:
- `.flex` `.inline-flex` `.flex.row` `.flex.column` `.flex.reverse` `.flex.column-reverse`
- `.flex.wrap` `.flex.nowrap` `.flex.wrap-reverse`
- `.flex.justify-start` `.flex.justify-end` `.flex.justify-center` `.flex.justify-between` `.flex.justify-around` `.flex.justify-evenly`
- `.flex.left` `.flex.center` `.flex.right` (shortcuts: both axes)
- `.flex.top` `.flex.middle` `.flex.bottom` `.flex.baseline` `.flex.stretch`
- `.flex-auto` `.flex-none` `.flex-grow` `.flex-shrink-0`
- `.self-start` `.self-end` `.self-center`
- `.gap` `.gap-2` … `.gap-32`
- `.flex.fullwidth` (legacy alias for `.flex.justify-between`)
- `.left` `.center` `.right` (text alignment)
- `.top` `.middle` `.bottom` (vertical alignment, non-flex)
- `.sidebar-left` `.sidebar-right` `.three-columns` (layouts)

GRID (12 columns):
- `.ggrid` (container)
- `.block1` to `.block12` (column spans)
- Responsive: `.tablet*` `.mobile*` prefixes
- Example: `class="block4 tablet6 mobile12"`

WIDTH:
- `.width25` `.width33` `.width50` `.width66` `.width75` `.width100` `.fullwidth`

BUTTONS:
- `.btn` (base)
- `.btn-primary` `.btn-secondary` `.btn-success` `.btn-warning` `.btn-danger` `.btn-info`
- Modifiers: `.outline` `.text` `.small` `.large` `.pill` `.circle` `.loading`
- Groups: `.btn-group` `.btn-group.vertical`

FORMS:
- `.form-control` (input wrapper)
- `.form-control.icon-*` (with icons: icon-user, icon-email, icon-password)
- `.valid` `.invalid` (validation states)
- `.switch` (toggle switch)
- `.form-group` (side by side inputs)

COLORS:
- Text: `.color-white` `.color-gray` `.color-dark` `.color-blue` `.color-green` `.color-red`
- Background: `.bg-white` `.bg-light` `.bg-dark` `.bg-blue` `.bg-green` `.bg-red`
- Status: `.success` `.warning` `.error` `.info`
- Badges: `.status0` to `.status11`

VISUAL:
- `.border` `.rounded` `.circle`
- `.shadow-sm` `.shadow-md` `.shadow-lg`
- `.hidden` `.block` `.inline-block`

BADGES:
- `<span class="badge-success" data-badge="Active">Label</span>`

DARK THEME:
- Add `data-theme="dark"` to `<html>` or `<body>`

CSS VARIABLES (use in inline styles if needed):
- Colors: `var(--color-primary)` `var(--color-success)` `var(--color-surface)`
- Spacing: `var(--space-1)` to `var(--space-8)` (4px increments)
- Borders: `var(--border-radius)` `var(--border-radius-lg)`
- Shadows: `var(--shadow-sm)` `var(--shadow-md)` `var(--shadow-lg)`

**My Request:**
[DESCRIBE WHAT YOU WANT HERE]
```

---

## Example Usage:

### Example 1: Login Form
```
I need you to design a modern login form using the Now.js CSS Framework.

[Include the rules above]

My Request:
Create a centered login form with:
- Email and password fields with icons
- "Remember me" switch toggle
- Primary login button
- Link to forgot password
- Support dark theme
```

### Example 2: Dashboard Layout
```
I need you to design a dashboard layout using the Now.js CSS Framework.

[Include the rules above]

My Request:
Create a dashboard with:
- Left sidebar (280px)
- Main content area with 3-column grid of stat cards
- Cards should be 4 columns on desktop, 6 on tablet, 12 on mobile
- Use shadow and rounded corners on cards
```

### Example 3: Data Table
```
I need you to design a data table using the Now.js CSS Framework.

[Include the rules above]

My Request:
Create a user management table with:
- Search input at top right
- Sortable columns: Name, Email, Status, Actions
- Status badges using status classes
- Action buttons (Edit, Delete) in button group
- Pagination at bottom
```

---

## Quick Reference Card

```
╔══════════════════════════════════════════════════════════════╗
║                    NOW.JS CSS CHEAT SHEET                     ║
╠══════════════════════════════════════════════════════════════╣
║ LAYOUT                                                        ║
║   .flex .inline-flex .flex.column .flex.wrap .flex.justify-between .gap ║
║   .flex.left .flex.center .flex.top .flex.middle .flex-shrink-0           ║
║   .sidebar-left .sidebar-right .three-columns                ║
║                                                               ║
║ GRID (inside .ggrid)                                         ║
║   .block1-12  |  .tablet1-12  |  .mobile1-12                 ║
║                                                               ║
║ BUTTONS                                                       ║
║   .btn .btn-primary .btn-success .btn-danger                 ║
║   + .outline .text .small .large .pill .circle               ║
║                                                               ║
║ FORMS                                                         ║
║   .form-control .form-control.icon-* .valid .invalid         ║
║   .switch .form-group                                       ║
║                                                               ║
║ COLORS                                                        ║
║   .color-* .bg-* .success .warning .error .info              ║
║                                                               ║
║ VISUAL                                                        ║
║   .border .rounded .shadow-sm .shadow-md .shadow-lg          ║
║                                                               ║
║ WIDTH                                                         ║
║   .width25 .width33 .width50 .width66 .width75 .fullwidth    ║
╚══════════════════════════════════════════════════════════════╝
```
