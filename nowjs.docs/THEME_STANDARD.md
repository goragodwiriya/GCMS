# GCMS Theme Standard — ฐานความรู้มาตรฐาน Theme

> **อัปเดต:** 11 กรกฎาคม 2568 (source-validated)
> **วัตถุประสงค์:** กำหนดมาตรฐานโครงสร้าง, CSS variables, และ template patterns
> สำหรับใช้เป็น reference ในการสร้างหรือปรับ theme ใหม่ทุก theme ให้สอดคล้องกัน

---

## 1. หลักการสำคัญ

| หลักการ | รายละเอียด |
|---------|-----------|
| **gcms.css = Base** | เก็บ layout กลาง, module CSS (board/gallery/personnel/auth/404) ทั้งหมด — ทุก theme ใช้ร่วมกัน (1404 บรรทัด) |
| **styles.css = Skin** | เก็บเฉพาะสี, typography, header/footer ของแต่ละ theme — สั้นมาก (118–592 บรรทัด) |
| **ไม่มี module/style.css** | CSS ทั้งหมดของ module อยู่ใน gcms.css แล้ว — ไม่ต้องสร้าง board/style.css, gallery/style.css ฯลฯ |
| **home.html คือหน้าหลัก** | หน้าเดียวที่แตกต่างกันระหว่าง theme ได้อย่างอิสระ |
| **Theme prefix เฉพาะ home** | ถ้าใช้ prefix ให้ใช้เฉพาะ home-specific components เท่านั้น เช่น `gz-` ใน greenz |
| **หน้าอื่นใช้ template กลาง** | main.html, login.html, module templates — ใช้ standard class names ร่วมกันทุก theme |

---

## 2. CSS Loading Chain (ทุก theme)

```html
<!-- ใน index.html <head> -->
<link rel="stylesheet" href="{WEBURL}Now/dist/now.core.min.css">   <!-- Framework base + variables -->
<link rel="stylesheet" href="{WEBURL}themes/gcms.css">              <!-- GCMS layout กลาง -->
<link rel="stylesheet" href="{WEBURL}{SKIN}css/styles.css">         <!-- Theme skin -->
```

```
now.core.min.css     → design tokens (--color-*, --space-*, --font-*, --shadow-*, --border-radius-*)
        ↓
themes/gcms.css      → layout, container, footer, widget, module, auth — ทุก theme ใช้ร่วมกัน
        ↓
{SKIN}css/styles.css → override สี, ปรับ header/footer/hero ของ theme นั้นโดยเฉพาะ
```

> **CSS เสริม** (โหลดท้าย body): `Now/css/fonts.css`, `Now/css/carousel.css`, `Now/dist/now.syntaxhighlighter.min.css`
> **ไม่มี module style.css** — CSS ทั้งหมดของ board/gallery/personnel รวมอยู่ใน `themes/gcms.css` แล้ว

---

## 3. CSS Variables มาตรฐาน

### 3.1 ที่มาจาก `now.core.min.css` (framework — ห้ามประกาศซ้ำ)

```css
/* Color */
--color-primary          /* สีหลัก */
--color-primary-hover    /* สีหลัก hover */
--color-primary-dark     /* สีหลักเข้ม */
--color-text             /* สีตัวอักษรหลัก */
--color-text-muted       /* สีตัวอักษรรอง (dim) */
--color-border           /* สีเส้นขอบ */
--color-surface          /* สี surface card */
--color-background       /* สีพื้นหลัง */

/* Spacing (4px grid) */
--space-1 .. --space-24

/* Typography */
--font-size-sm / --font-size-base / --font-size-lg / --font-size-xl
--font-size-2xl / --font-size-3xl

/* Effects */
--shadow / --shadow-lg / --shadow-hover
--border-radius / --border-radius-2xl
--transition / --transition-speed
```

### 3.2 ที่ประกาศใน `themes/gcms.css` (ใช้ร่วมทุก theme)

```css
:root {
  --font-family-heading: 'Prompt', sans-serif;
  --widget-border-radius: 8px;
  --line-height-base: 1.8;

  /* Footer (theme override ได้) */
  --footer-bg: var(--color-text);
  --footer-text: var(--color-background);
  --footer-heading: var(--color-background);
  --footer-border: none;

  /* Header (theme override ได้) */
  --header-bg: var(--color-background);
  --header-border: var(--color-border);

  /* Home */
  --home-radius: 0;
  --home-border: var(--card-border);
  --home-bg: var(--card-bg);

  /* Document Widget */
  --doc-bg: #f8fafc;
  --doc-card-bg: #ffffff;
  --doc-border: #e2e8f0;
  --doc-radius: 12px;
  --doc-shadow: 0 2px 12px rgba(0,0,0,.08);
  --doc-shadow-hover: 0 8px 28px rgba(0,0,0,.14);
  --doc-transition: 0.25s ease;

  /* Board */
  --board-accent: var(--color-primary);
  --board-accent-dark: var(--color-primary-dark);
  --board-accent-light: #fff5f5;
  --board-radius: 0;          /* wk override → 14px */
  --board-shadow: var(--shadow);
  --board-shadow-hover: var(--shadow-hover);
  --board-transition: all var(--transition-duration) var(--transition-timing);
  --board-pin-color: #b45309;
  --board-lock-color: #6b7280;
}
```

### 3.3 ที่ theme กำหนดเองใน `styles.css` (override ต่อ theme)

```css
/* ── ต้องประกาศทุก theme ── */
:root {
  /* Color palette ของ theme */
  --color-primary: #2563eb;          /* สีหลัก */
  --color-primary-hover: #1d4ed8;    /* hover */
  --color-primary-main: #1d4ed8;     /* main shade (ใช้กับ logo, auth) */
  --color-primary-dark: #1e40af;     /* dark shade */
  --color-primary-light: #dbeafe;    /* light shade (bg tint) */
  --color-accent: #0ea5e9;           /* accent / highlight */

  /* Surface */
  --color-surface-bg: #f3f4f6;       /* header bg, card bg */
  --color-page-bg: #FDFBF8;          /* body background */
  --color-border: #EAE7E2;           /* border ทั่วไป */

  /* Text */
  --color-text: #2C2825;
  --color-text-secondary: #6B645D;
  --color-text-muted: #6b6560;

  /* Misc */
  --menu-width: 330px;               /* ความกว้าง sidemenu */

  /* Optional: override gcms.css footer/header */
  --footer-bg: #052e16;              /* ถ้าต้องการ custom footer bg */
  --footer-text: #d1fae5;
  --footer-heading: #86efac;
  --footer-border: 1px solid rgba(134,239,172,0.2);
  --header-bg: rgba(240,253,244,0.95);
  --header-border: 1px solid var(--color-border);

  /* Optional: override board/home radius */
  --board-radius: 14px;   /* rounded board items (wk) */
  --home-radius: 24px;    /* rounded home sections (wk) */
}
```

### 3.4 Theme-specific prefix (เฉพาะ home.html เท่านั้น)

ถ้า theme ต้องการ CSS prefix ให้ใช้เฉพาะ **home-page specific components** เท่านั้น:

| Theme   | Prefix | ใช้สำหรับ |
|---------|--------|----------|
| greenz  | `gz-`  | gz-hero, gz-section, gz-btn, gz-badge, gz-stats-*, gz-feature-*, gz-cta-* |
| default | ไม่มี  | ใช้ standard class names ทั้งหมด |
| wk      | ไม่มี  | ใช้ standard class names ทั้งหมด |

**module templates ทุก theme ต้องใช้ standard class names** (hero, container, section-content, board-topic-item ฯลฯ)

---

## 4. โครงสร้างไฟล์มาตรฐาน

```
themes/{theme-name}/
│
├── theme.json                  ← metadata + color config
├── screenshot.svg              ← preview image (SVG แนะนำ)
│
│   ── Layout Shell ──
├── index.html                  ← <html>…header…{MAIN}…footer…</html>
│
│   ── Page Content Templates ──
├── home.html                   ← homepage (แตกต่างกันได้ตาม theme)
├── main.html                   ← inner page generic layout
├── about.html                  ← หน้าเกี่ยวกับ/ติดต่อ
├── 404.html                    ← not found
│
│   ── Auth Templates (เหมือนกันทุก theme) ──
├── login.html
├── register.html
├── forgot.html
├── reset-password.html
├── activate.html
├── profile.html
├── language.html
├── privacy.html
├── terms.html
│
│   ── Assets ──
├── css/
│   └── styles.css              ← theme skin CSS (118–592 บรรทัด)
├── images/                     ← logo, bg, icons ของ theme
├── languages/
│   └── widget.html             ← template แสดง font-size + language switcher
│
│   ── Module Templates (HTML only — ไม่มี style.css) ──
├── board/
│   ├── category.html
│   ├── categoryitem.html
│   ├── list.html
│   ├── listitem.html
│   ├── reply.html
│   ├── replyform.html
│   ├── view.html
│   └── write.html
├── document/
│   ├── category.html
│   ├── categoryitem.html
│   ├── list.html
│   ├── listitem.html
│   ├── tag.html
│   └── view.html
├── gallery/
│   ├── album.html
│   ├── album-item.html
│   └── list.html
└── personnel/
    ├── list.html
    └── person-item.html
```

> **สำคัญ:** ไม่มี `style.css` ในแต่ละ module — CSS ทั้งหมดอยู่ใน `themes/gcms.css` แล้ว
> **หมายเหตุ:** `product/` ไม่อยู่ใน greenz — เพิ่มเฉพาะ theme ที่ต้องการ

---

## 5. Template มาตรฐาน

### 5.1 `index.html` — Shell หลัก

```html
<!DOCTYPE html>
<html lang="{LANGUAGE}" dir="ltr" class="smooth">
<head>
  <title>{TITLE}</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts (preconnect ก่อน) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap">
  <!-- CSS Chain -->
  <link rel="stylesheet" href="{WEBURL}Now/dist/now.core.min.css">
  <link rel="stylesheet" href="{WEBURL}themes/gcms.css">
  <link rel="stylesheet" href="{WEBURL}{SKIN}css/styles.css">
  <script>const WEB_URL = "{WEBURL}";</script>
</head>
<body>
  <div>
    <header class="header">
      <div class="container">
        <div class="header-content">
          <div class="logo">
            <div>
              <a href="{WEBURL}" class="logo-text">{WEBTITLE}</a>
              <p class="logo-subtitle">{WEBDESCRIPTION}</p>
            </div>
          </div>
          <nav class="topmenu responsive-menu" data-component="menu">{MAINMENU}</nav>
          <button class="menu-toggle topmenu-toggle">
            <span class="toggle-icon">
              <span class="toggle-bar"></span>
              <span class="toggle-bar"></span>
              <span class="toggle-bar"></span>
              <span class="toggle-bar"></span>
            </span>
          </button>
        </div>
      </div>
    </header>

    <main id="main" role="main">{MAIN}</main>

    <div class="container textlinks-footer">{WIDGET_TEXTLINKS_footer}</div>
  </div>

  <footer class="footer" id="contact">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h3 class="footer-title" data-i18n>Contact Us</h3>
          <ul class="footer-links">
            <li><span class="icon-office" data-text="company.name"></span></li>
            <li><a class="icon-phone" data-attr="href:'tel:' + company.phone" data-text="company.phone"></a></li>
            <li><a class="icon-email" data-attr="href:'mailto:' + company.email" data-text="company.email"></a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h3 class="footer-title" data-i18n>Service</h3>
          <ul class="footer-links">
            <li data-if="user === null"><a href="{WEBURL}login" data-i18n>{LNG_Log in}</a></li>
            <li data-if="user === null"><a href="{WEBURL}register" data-i18n>{LNG_Register}</a></li>
            <li data-if="user === null"><a href="{WEBURL}forgot" data-i18n>{LNG_Forgot Password}</a></li>
            <li data-if="user !== null"><a href="{WEBURL}profile" data-i18n>{LNG_Edit profile}</a></li>
            <li data-if="user !== null"><a href="{WEBURL}logout" data-on="click:doLogout" data-i18n>{LNG_Logout}</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>
          Copyright &copy; 2569
          <a href="{WEBURL}">{WEBTITLE}</a>
          designed by <a href="https://www.kotchasan.com">Kotchasan.com</a>
          GCMS Version {VERSION} |
          <a href="{WEBURL}terms" target="_blank" data-i18n>Terms of Service</a>
          <a href="{WEBURL}privacy" target="_blank" data-i18n>Privacy Policy</a>
        </p>
      </div>
    </div>
  </footer>

  <!-- Deferred CSS -->
  <link rel="stylesheet" href="{WEBURL}Now/css/fonts.css">
  <link rel="stylesheet" href="{WEBURL}Now/css/carousel.css">
  <link rel="stylesheet" href="{WEBURL}Now/dist/now.syntaxhighlighter.min.css">
  <!-- Scripts -->
  <script src="{WEBURL}Now/dist/now.core.min.js"></script>
  <script src="{WEBURL}Now/dist/now.syntaxhighlighter.min.js"></script>
  <script src="{WEBURL}js/main.js"></script>
  <script src="{WEBURL}js/global.js"></script>
</body>
</html>
```

### 5.2 `main.html` — Inner Page Layout (ใช้ร่วมทุก theme)

```html
<div class="page-content">
  <div class="hero">
    <div class="container">
      <h1 class="hero-title">{TOPIC}</h1>
    </div>
  </div>
  <div class="container">
    <div class="section-content">
      {DETAIL}
    </div>
  </div>
</div>
```

### 5.3 `home.html` — Homepage (theme-specific)

หน้า home เป็นหน้าเดียวที่ออกแบบได้อิสระต่อ theme
ควรใช้ class names จาก gcms.css (`section-bg`, `section-title`, `home-content`, `sidebar`, `container` ฯลฯ) เป็นหลัก

**Pattern Sidebar Layout (default/wk style):**
```html
<div class="homepage">
  {WIDGET_TEXTLINKS_hero}
  <div class="container">
    <div class="sidebar-left gap home-layout">
      <aside class="sidebar home-sidebar">
        <section class="widget__bg">
          <div class="widget__header"><h2>…</h2></div>
          {WIDGET_PERSONNEL cat=1;menu=1}
        </section>
        <!-- more sidebar sections -->
      </aside>
      <div class="content home-content">
        <section class="section-bg">
          <div class="section-title"><div><h2>…</h2></div></div>
          <div class="section-content">{WIDGET_DOCUMENT module=news}</div>
        </section>
        <!-- more content sections -->
      </div>
    </div>
  </div>
</div>
```

**Pattern Full-Section Layout (greenz/modern style):**
```html
<section class="gz-hero"><div class="gz-section-content">…</div></section>

<section class="gz-section">
  <div class="container">
    <div class="gz-section-header">
      <h2 class="gz-section-title">{LNG_Featured}</h2>
      <p class="gz-section-subtitle">…</p>
    </div>
    {WIDGET_DOCUMENT}
  </div>
</section>

<section class="gz-section gz-section-alt">
  <div class="container">…</div>
</section>

<section class="gz-section gz-stats-section">
  <div class="container"><div class="gz-stats-grid">…</div></div>
</section>
<section class="gz-section gz-cta-section">…CTA…</section>
```

### 5.4 `languages/widget.html` — Language/FontSize Widget

```html
<div class="widget table fullwidth collapse">
  <div class="td">{FONTSIZE}</div>
  <div class="td right">{LANGUAGES}</div>
</div>
```

---

## 6. Module Template Patterns มาตรฐาน

> **กฎ:** Template ใช้ standard class names กลาง — CSS อยู่ใน `gcms.css` แล้ว
> ไม่ต้องมี `style.css` ในแต่ละ module folder อีกต่อไป

### 6.1 List Page Pattern (document/list, gallery/list, board/list)

```html
<div class="lists-page">
  <div class="hero">
    <div class="container">
      <h1 class="hero-title">{TOPIC}</h1>
      <p class="hero-subtitle">{DESCRIPTION}</p>
    </div>
  </div>
  <div class="container">
    <div class="section-content">
      <nav class="category-buttons">{CATEGORY_LINKS}</nav>
      <section class="ggrid thumbview">{LIST}</section>
      <nav class="pagination-wrapper">{PAGINATION}</nav>
    </div>
  </div>
</div>
```

### 6.2 View Page Pattern (document/view)

```html
<article class="view-page">
  <header class="view-header">
    <div class="container">
      <nav class="breadcrumbs one_line"><ul>{BREADCRUMBS}</ul></nav>
      <h1>{TOPIC}</h1>
      <p class="description">{DESCRIPTION}</p>
      <div class="header-meta">
        <a href="{WEBURL}{MODULE}/{CATEGORY_ID}" class="badge category">{CATEGORY}</a>
        <time class="date icon-calendar" datetime="{DATE}">{DATE {DATE} d M Y}</time>
        <span class="icon-visited">{VISITED}</span>
      </div>
    </div>
  </header>
  <div class="container">
    <div class="section-content">
      <section class="page-content">
        {IMAGE}
        {DETAIL}
      </section>
      <footer>{WIDGET_SHARE}</footer>
      <section class="related-articles">
        <h2>{LNG_Related Articles}</h2>
        {WIDGET_RELATED module={MODULE};id={ID};tags={TAGS}}
      </section>
    </div>
  </div>
</article>
```

### 6.3 Document Card Item (document/listitem)

```html
<article class="widget-item block{COLS}">
  <a href="{URL}" class="figure">
    <img src="{IMAGE}" alt="{TOPIC}" loading="lazy">
    <span class="badge category">{CATEGORY}</span>
  </a>
  <div class="widget-body">
    <h3><a class="two_line" href="{URL}">{TOPIC}</a></h3>
    <div class="description">{DESCRIPTION}</div>
    <footer>
      <time class="date icon-calendar" datetime="{DATE}">{DATE {DATE} d M Y}</time>
      <a href="{URL}" class="next">{LNG_Read} →</a>
    </footer>
  </div>
</article>
```

### 6.4 Category List Page (document/category)

```html
<div class="categories-page">
  <div class="hero">
    <div class="container">
      <h1 class="hero-title">{TOPIC}</h1>
      <p class="hero-subtitle">{DESCRIPTION}</p>
    </div>
  </div>
  <div class="container">
    <div class="section-content">
      <section class="ggrid thumbview">{LIST}</section>
    </div>
  </div>
</div>
```

### 6.5 Board Topic List Item (board/listitem)

```html
<div class="board-topic-item{PIN}{LOCKED}" data-id="{ID}">
  <div class="board-topic-badges"><span class="{ICON}"></span></div>
  <div class="board-topic-main">
    <a href="{URL}" class="board-topic-title">{TOPIC}</a>
    <div class="board-meta">
      <span class="board-meta-author icon-user">{SENDER}</span>
      <time class="date icon-calendar" datetime="{DATE}">{DATE {DATE} d M Y H:i}</time>
      <a href="{WEBURL}{MODULE}?cat={CATEGORY_ID}" class="badge category">{CATEGORY}</a>
    </div>
  </div>
  <div class="board-topic-stats">
    <div class="board-stat">
      <span class="board-stat-num">{COMMENTS}</span>
      <span class="board-stat-label">{LNG_Replies}</span>
    </div>
    <div class="board-stat">
      <span class="board-stat-num">{VISITED}</span>
      <span class="board-stat-label">{LNG_Views}</span>
    </div>
  </div>
  <div class="board-topic-last">
    <span class="board-last-label">{LNG_Last reply}</span>
    <span class="board-last-by">{LAST_REPLY_BY}</span>
    <span class="date">{DATE {LAST_REPLY} d M Y H:i}</span>
  </div>
</div>
```

### 6.6 Gallery Album Item (gallery/album-item)

```html
<a class="widget-item block{COLS}" href="{URL}">
  <div class="figure">
    <img src="{COVER}" alt="{TOPIC}" loading="lazy">
    <div class="album-overlay">
      <span class="album-view-icon icon-image"></span>
    </div>
  </div>
  <div class="widget-body">
    <div>
      <h3>{TOPIC}</h3>
      <p class="description two_line">{DESCRIPTION}</p>
    </div>
    <time class="date icon-event" datetime="{DATE}">{DATE {DATE} d M Y}</time>
  </div>
</a>
```

### 6.7 Personnel Person Card (personnel/person-item)

```html
<article class="person-card level-{LEVEL}"
         data-id="{ID}" data-level="{LEVEL}"
         data-photo="{IMAGE}" data-name="{NAME}"
         data-position="{POSITION}" data-department="{DEPARTMENT}"
         data-phone="{PHONE}" data-email="{EMAIL}" data-detail="{DETAIL}">
  <div class="person-photo-wrap">
    <img class="person-photo" src="{IMAGE}" alt="{NAME}" loading="lazy">
    <div class="person-overlay">
      <button class="person-view-btn icon-user" data-modal="personnel-modal"
              data-modal-bind="name:name,position:position,department:department,photo:photo,phone:phone,email:email,detail:detail">
        {LNG_View details}
      </button>
    </div>
  </div>
  <div class="person-info">
    <h3 class="person-name">{NAME}</h3>
    <p class="person-position">{POSITION}</p>
    <p class="person-department icon-home">{DEPARTMENT}</p>
  </div>
</article>
```

---

## 7. `styles.css` มาตรฐาน (Theme Skin)

โครงสร้างของ `{SKIN}css/styles.css` แต่ละ theme — เขียนน้อย override มาก:

```css
/* =====================================================
   {THEME_NAME} Theme
   ===================================================== */

/* ── 1. Design Tokens (สิ่งเดียวที่ต้องเปลี่ยนระหว่าง theme) ── */
:root {
  --color-primary: #2563eb;
  --color-primary-hover: #1d4ed8;
  --color-primary-main: #1d4ed8;
  --color-primary-dark: #1e40af;
  --color-primary-light: #dbeafe;
  --color-primary-rgb: 37, 99, 235;
  --color-accent: #0ea5e9;
  --color-surface-bg: #f3f4f6;
  --color-page-bg: #FDFBF8;
  --color-border: #EAE7E2;
  --color-text: #2C2825;
  --color-text-secondary: #6B645D;
  --color-text-muted: #6b6560;
  --menu-width: 330px;
}

/* ── 2. Body ── */
body {
  background: var(--color-page-bg);
}

/* ── 3. Header ── */
.header {
  background: var(--color-surface-bg);
  box-shadow: var(--shadow);
  position: sticky;
  top: 0;
  z-index: 1001;
}
.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4) 0;
}
.header-content .logo {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  text-decoration: none;
  line-height: 1.4;
}
.header-content .logo::before {
  content: '';
  background-image: var(--logo);
  height: 3.6rem;
  min-width: 3.6rem;
  background-size: cover;
  background-position: center;
}
.logo-text { font-size: 1.5rem; }
.logo-subtitle { color: var(--color-text-secondary); margin: 0; }

/* ── 4. Navigation ── */
.topmenu > ul > li > a,
.topmenu > ul > li > button {
  border-radius: var(--border-radius);
}
.topmenu li:hover > a:not(.active),
.topmenu li:hover > button:not(.active) {
  color: var(--color-accent);
  background-color: transparent;
}
.sidemenu li > a {
  border-left: 5px solid var(--color-accent);
}

/* ── 5. Hero ── */
.hero {
  background-image: linear-gradient(
    135deg,
    var(--color-primary-light) 0%,
    var(--color-primary-main) 50%,
    var(--color-primary-dark) 100%
  );
  color: white;
  padding: var(--space-16) 0;
  text-align: center;
}
.hero-title { font-weight: 700; margin: 0 0 .5rem; }
.hero-subtitle { font-size: 1rem; opacity: .85; margin: 0; }

/* ── 6. Homepage Layout (ปรับตาม home.html ของ theme) ── */
.homepage .hero {
  height: 60vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background-size: cover;
  background-position: center;
}
.section-bg,
.page-content section {
  background-color: var(--card-bg);
  padding: var(--space-8);
  box-shadow: var(--card-shadow);
  border-radius: 24px;
}

/* ── เพิ่มเติมตามความต้องการของ theme ── */
```

---

## 8. Module `style.css` มาตรฐาน

แต่ละ module มี `style.css` สำหรับตกแต่งเฉพาะ theme — ควรสั้น ไม่ซ้ำกับ gcms.css

### 8.1 Pattern: `board/style.css`

```css
/* Board Module — Theme decoration only */
:root {
  --board-accent: var(--color-primary);
  --board-accent-dark: var(--color-primary-dark);
  --board-bg: var(--color-page-bg);
  --board-surface: #ffffff;
  --board-surface-tint: var(--color-surface-bg);
  --color-border: var(--color-border);
  --board-radius: var(--border-radius, 8px);
  --board-radius: 14px;
  --board-shadow: var(--shadow, 0 4px 12px rgba(0,0,0,.08));
  --board-shadow-hover: var(--shadow-hover, 0 8px 24px rgba(0,0,0,.12));
  --board-transition: var(--transition, all 0.3s ease);
}

/* topic list item */
.board-new-topic-btn { margin-left: auto; }
.board-topic-list { display: flex; flex-direction: column; gap: 10px; }
.board-topic-item {
  display: grid;
  grid-template-columns: auto 1fr auto auto;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  background: var(--board-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--board-radius);
  box-shadow: var(--board-shadow);
  transition: var(--board-transition);
}
/* ... ส่วนที่เหลือของ board styles */
```

### 8.2 Pattern: `gallery/style.css`

```css
/* Gallery Module — Theme decoration only */
/* view mode toggle, masonry grid, lightbox ฯลฯ */
.btn-view-mode.active {
  background: var(--color-primary, #2563eb);
  border-color: var(--color-primary, #2563eb);
  color: #fff;
}
```

### 8.3 Pattern: `personnel/style.css`

```css
/* Personnel Module — Theme decoration only */
.person-card {
  border-top: 4px solid transparent;
}
/* level colors — อาจ override ด้วย --color-primary ของ theme */
.personnel-level.level-1 .level-title { color: var(--color-primary); }
```

---

## 9. Template Variables Reference

### 9.1 System Variables

| Variable | ใช้ใน | ความหมาย |
|----------|--------|----------|
| `{SKIN}` | index.html | Path theme เช่น `themes/default/` |
| `{WEBURL}` | ทุกที่ | Base URL เช่น `https://example.com/` |
| `{LANGUAGE}` | `<html lang>` | รหัสภาษา: `th`, `en` |
| `{VERSION}` | footer | GCMS version |
| `{MAIN}` | index.html | เนื้อหาหลัก (render จาก module/template) |

### 9.2 Page Variables

| Variable | ความหมาย |
|----------|----------|
| `{TITLE}` | `<title>` ของหน้า |
| `{WEBTITLE}` | ชื่อเว็บไซต์ |
| `{WEBDESCRIPTION}` | คำอธิบายเว็บ |
| `{TOPIC}` | หัวข้อของหน้า/รายการ |
| `{DESCRIPTION}` | คำอธิบายสั้น |
| `{DETAIL}` | เนื้อหาหลัก (HTML) |
| `{IMAGE}` | รูปภาพหลัก (`<img>` tag) |
| `{DATE}` | วันที่ (raw) |
| `{DATE {DATE} d M Y}` | วันที่ format ไทย |
| `{BREADCRUMBS}` | `<li>` สำหรับ breadcrumb nav |
| `{PAGINATION}` | pagination component |
| `{MAINMENU}` | `<li>` สำหรับ nav menu |

### 9.3 Module Variables

| Variable | ความหมาย |
|----------|----------|
| `{LIST}` | รายการ items (render จาก `listitem.html`) |
| `{CATEGORY_LINKS}` | links กรองตาม category |
| `{CATEGORY}` | ชื่อ category |
| `{CATEGORY_ID}` | ID ของ category |
| `{MODULE}` | ชื่อ module (news, gallery, forum ฯลฯ) |
| `{ID}` | ID ของ record |
| `{URL}` | URL ของ item |
| `{VISITED}` | จำนวนผู้เข้าชม |
| `{COLS}` | จำนวนคอลัมน์ (สำหรับ grid, เช่น `4` → `block4`) |
| `{TAGS}` | tags (คั่นด้วย comma) |

### 9.4 Personnel-specific Variables

| Variable | ความหมาย |
|----------|----------|
| `{NAME}` | ชื่อบุคลากร |
| `{POSITION}` | ตำแหน่ง |
| `{DEPARTMENT}` | แผนก/ฝ่าย |
| `{LEVEL}` | ระดับ hierarchy (1, 2, 3) |
| `{PHONE}`, `{EMAIL}` | ข้อมูลติดต่อ |
| `{DEPT_TABS}` | tab กรองแผนก |
| `{PERSONNEL_LIST}` | รายการ person-item (render) |

### 9.5 Widget Variables

```
{WIDGET_TEXTLINKS_hero}               ← textlinks area ชื่อ "hero"
{WIDGET_TEXTLINKS_footer}             ← textlinks area ชื่อ "footer"
{WIDGET_TEXTLINKS_banner}             ← textlinks area ชื่อ "banner"
{WIDGET_TEXTLINKS_online}             ← textlinks area ชื่อ "online"
{WIDGET_DOCUMENT module=news}         ← widget บทความ
{WIDGET_DOCUMENT module=news;layout=carousel;limit=6;mobile=1;tablet=2;desktop=3}
{WIDGET_BOARD module=forum;limit=5}   ← widget กระดาน
{WIDGET_GALLERY module=gallery;count=4}
{WIDGET_PERSONNEL cat=1;menu=1}
{WIDGET_FACEBOOK}
{WIDGET_TAGS}
{WIDGET_SHARE}
{WIDGET_RELATED module={MODULE};id={ID};tags={TAGS}}
```

### 9.6 Conditional/Data Binding (Now.js)

```html
data-if="user === null"          ← แสดงเฉพาะเมื่อไม่ login
data-if="user !== null"          ← แสดงเมื่อ login แล้ว
data-if="data.user_register === 1"
data-text="company.name"         ← bind ข้อความจาก API data
data-attr="href:'tel:' + company.phone"  ← bind attribute
data-i18n                        ← ใช้ระบบ i18n
data-on="click:doLogout"         ← event handler
```

---

## 10. `theme.json` มาตรฐาน

```json
{
  "name": "Theme name",
  "version": "1.0.0",
  "author": "Author Name",
  "author_url": "https://example.com",
  "description": "คำอธิบาย theme",
  "screenshot": "screenshot.svg",
  "license": "MIT",
  "layouts": [
    { "id": "main", "name": "Main Layout", "template": "index.html" },
    { "id": "auth", "name": "Auth Layout", "template": "login.html" },
    { "id": "blank", "name": "Blank", "template": "main.html" }
  ],
  "components": [
    { "id": "header", "name": "Header", "template": "index.html#header" },
    { "id": "footer", "name": "Footer", "template": "index.html#footer" },
    { "id": "navbar", "name": "Navigation", "template": "index.html#nav" }
  ],
  "colors": {
    "primary": "#2563eb",
    "secondary": "#64748b",
    "accent": "#0ea5e9",
    "background": "#FDFBF8",
    "surface": "#f3f4f6",
    "text": "#2C2825",
    "text_secondary": "#6B645D",
    "border": "#EAE7E2"
  },
  "settings": {
    "show_sidebar": false,
    "sidebar_position": "left",
    "footer_columns": 3,
    "sticky_header": true,
    "hero_style": "gradient"
  }
}
```

---

## 11. สิ่งที่อยู่ใน `gcms.css` (ไม่ต้องเขียนใน theme)

สิ่งเหล่านี้ถูก handle โดย `gcms.css` แล้ว — theme ไม่ต้องเขียนซ้ำ:

| Component | Class ที่ใช้ |
|-----------|------------|
| Container | `.container` |
| Footer layout | `.footer`, `.footer-content`, `.footer-links`, `.footer-bottom` |
| Widget cards | `.widget-item`, `.widget-body`, `.figure`, `.badge` |
| Widget views | `.thumbview`, `.listview`, `.iconview`, `.ggrid` |
| Home sections | `.section-title`, `.section-bg`, `.widget__bg` |
| Page content | `.page-content` (h2, h3, img, p styles) |
| Hero shell | `.hero`, `.hero > .container` |
| View header | `.view-header`, `.header-meta` |
| Breadcrumbs | `.breadcrumbs` |
| Pagination | `.pagination-wrapper` |
| Category buttons | `.category-buttons`, `.cat-link` |
| Auth pages | `.auth-container`, `.auth-card`, `.auth-form`, `.social-login-buttons` |
| 404 page | `.error-container`, `.error-card`, `.error-code-number` |
| Empty state | `.list-empty`, `.list-empty-icon` |
| Textlinks footer | `.textlinks-footer` |
| Profile | `.profile` |
| Board view actions | `.board-view-actions` |

---

## 12. Checklist สร้าง Theme ใหม่

```
[ ] สร้าง theme.json ที่มี name, colors, settings ครบ
[ ] ประกาศ CSS variables ใน styles.css ครบตาม section 3.3
[ ] index.html ใช้ CSS loading chain มาตรฐาน (section 5.1)
[ ] main.html ใช้ .hero + .container + .section-content pattern
[ ] home.html ออกแบบตาม theme (sidebar หรือ full-section)
[ ] auth pages (login, register ฯลฯ) ใช้ template กลาง — ไม่แก้ class
[ ] module templates (board, document, gallery, personnel) ใช้ class กลาง
[ ] board/style.css ประกาศ --board-* variables อ้างอิง --color-primary
[ ] gallery/style.css style view-toggle, masonry
[ ] personnel/style.css style person-card, level-title
[ ] screenshot.svg สำหรับ preview
[ ] languages/widget.html มี {FONTSIZE} + {LANGUAGES}
[ ] ตรวจสอบ: ไม่มี prefix เฉพาะ theme ใน class names
[ ] ตรวจสอบ: ไม่มี hard-coded color ใน template HTML
[ ] ตรวจสอบ: ไม่เขียน CSS ซ้ำกับที่มีใน gcms.css แล้ว
```

---

## 13. ความแตกต่างหลักระหว่าง Themes ปัจจุบัน

| | `default` | `wk` | `greenz` |
|-|-----------|------|----------|
| **Primary** | warm neutral/dark | `#FF0000` | `#16a34a` |
| **Homepage** | Sidebar 2-col | Sidebar 2-col | Full-section (gz-) |
| **Hero style** | Gradient | Gradient (60vh) | gz-hero (dark green + deco) |
| **Layout** | sidebar | sidebar | single-column |
| **Font** | Prompt | Prompt | Prompt + Noto Sans Thai |
| **styles.css** | 118 lines | 130 lines | 592 lines |
| **Module CSS** | gcms.css | gcms.css | gcms.css |
| **Theme JS** | ✗ | ✗ | ✗ |
| **Designer support** | ✗ | ✗ | ✗ (ไม่มี designer block) |
| **product/ module** | ✓ | ✓ | ✗ |
| **footer bg** | gcms.css default | gcms.css default | `#052e16` (dark green) |
| **Home prefix** | ไม่มี | ไม่มี | `gz-` (home เท่านั้น) |

> **flavor theme ถูกลบออกแล้ว** — แทนที่ด้วย `greenz`
> `greenz` ใช้ standard `--color-*` variables + `gz-` prefix เฉพาะ home-page components

---

*ไฟล์นี้เป็นส่วนหนึ่งของ GCMS Theme Documentation — อัปเดต 11 กรกฎาคม 2568*
