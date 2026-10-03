<?php
/**
 * @filesource modules/index/controllers/aitheme.php
 *
 * AI Theme Generator Controller
 *
 * Generates a complete GCMS theme (theme.json + css/styles.css) from a
 * natural-language prompt using the configured AI provider.
 *
 * Endpoints:
 *   POST /api/index/aitheme/generate  — generate theme JSON+CSS from prompt (no disk write)
 *   POST /api/index/aitheme/save      — save a previously generated theme to disk
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Aitheme;

use Gcms\Ai;
use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Text;

class Controller extends ApiController
{
    private const CSS_MODE = 'token-only';

    private const REQUIRED_CSS_VARS = [
        '--color-primary',
        '--color-primary-hover',
        '--color-primary-main',
        '--color-primary-dark',
        '--color-primary-light',
        '--color-primary-rgb',
        '--color-accent',
        '--color-accent-hover',
        '--color-surface-bg',
        '--color-page-bg',
        '--color-border',
        '--color-text',
        '--color-text-secondary',
        '--color-text-muted',
        '--home-radius',
        '--board-radius',
        '--home-shadow',
        '--card-shadow',
        '--footer-bg',
        '--footer-text',
        '--footer-heading',
        '--footer-border',
        '--header-bg',
        '--header-border'
    ];

    private const FALLBACK_LAYOUTS = [
        ['id' => 'main', 'name' => 'Main Layout', 'template' => 'index.html'],
        ['id' => 'auth', 'name' => 'Auth Layout', 'template' => 'login.html'],
        ['id' => 'blank', 'name' => 'Blank', 'template' => 'main.html'],
        ['id' => 'full-width', 'name' => 'Full Width', 'template' => 'main.html']
    ];

    private const FALLBACK_COMPONENTS = [
        ['id' => 'header', 'name' => 'Header', 'template' => 'index.html#header'],
        ['id' => 'footer', 'name' => 'Footer', 'template' => 'index.html#footer'],
        ['id' => 'navbar', 'name' => 'Navigation', 'template' => 'index.html#nav'],
        ['id' => 'sidebar', 'name' => 'Sidebar', 'template' => 'index.html#sidebar']
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // System prompt sent to the AI for every generation request
    // ─────────────────────────────────────────────────────────────────────────
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a professional web theme author for the GCMS CMS platform.
When given a theme description, you must return ONLY a valid JSON object — no prose, no markdown fences.

The JSON must have exactly two keys:

1. "theme_json" — a JSON object that stays compatible with the provided base theme JSON.
Preserve the layouts/components structure style from the base theme. If the base theme uses arrays of objects, keep that pattern. If it uses string identifiers, keep that pattern.
Preserve compatible settings keys from the base theme unless the prompt explicitly changes them.
At minimum include this information:
{
  "name": "<Human readable theme name>",
  "version": "1.0.0",
  "author": "AI Generated",
  "author_url": "",
  "description": "<One sentence description>",
  "screenshot": "screenshot.svg",
  "license": "MIT",
  "layouts": [
    { "id": "main",       "name": "Main Layout",  "template": "index.html" },
    { "id": "auth",       "name": "Auth Layout",  "template": "login.html" },
    { "id": "blank",      "name": "Blank",        "template": "main.html" },
    { "id": "full-width", "name": "Full Width",   "template": "main.html" }
  ],
  "components": [
    { "id": "header",  "name": "Header",     "template": "index.html#header" },
    { "id": "footer",  "name": "Footer",     "template": "index.html#footer" },
    { "id": "navbar",  "name": "Navigation", "template": "index.html#nav" },
    { "id": "sidebar", "name": "Sidebar",    "template": "index.html#sidebar" }
  ],
  "colors": {
    "primary":        "<hex>",
    "primary_hover":  "<hex>",
    "secondary":      "<hex>",
    "accent":         "<hex>",
    "background":     "<hex>",
    "surface":        "<hex>",
    "text":           "<hex>",
    "text_secondary": "<hex>",
    "border":         "<hex>"
  },
  "settings": {
    "show_sidebar":     true,
    "sidebar_position": "left|right|both|none",
    "footer_columns":   3,
    "show_breadcrumb":  true,
    "show_search":      true,
    "color_scheme":     "light"
  }
}

If the base theme includes extra compatible settings such as products_per_page, sticky_header, hero_style, or columns, keep them unless the prompt asks to change them.

2. "css" — a token override string that will be appended as a managed :root block after the base theme CSS in themes/<slug>/css/styles.css.
Assume the base theme CSS remains in place. Do NOT rewrite the whole theme from scratch.
This runs in strict token-only mode: emit only custom property declarations for :root.
Return one :root block only. Do NOT output any selectors other than :root.
Do NOT emit @media, @import, @font-face, keyframes, IDs, attribute selectors, universal selectors, or arbitrary new selectors.
Do NOT emit any non-token CSS declarations outside :root.
The CSS MUST define all of these :root variables at minimum:
  --color-primary, --color-primary-hover, --color-primary-main,
  --color-primary-dark, --color-primary-light, --color-primary-rgb,
  --color-accent, --color-accent-hover,
  --color-surface-bg, --color-page-bg, --color-border,
  --color-text, --color-text-secondary, --color-text-muted,
  --home-radius, --board-radius,
  --home-shadow, --card-shadow,
  --footer-bg, --footer-text, --footer-heading, --footer-border,
  --header-bg, --header-border

Also define these supplementary :root variables so the menu and widget/card blocks stay
visually consistent with the palette you generate (do not skip these — they are shown to the
user as editable color swatches):
  --topmenu-mobile-bg, --topmenu-mobile-text,
  --menu-highlight-bg, --menu-highlight-text,
  --menu-select-bg, --menu-select-text,
  --menu-button-border-color,
  --home-bg, --home-border-color

For dark themes set body color variables to dark values.
For light themes keep backgrounds light and text dark.
Match the visual personality described in the prompt.
Output raw JSON only. No explanations.
PROMPT;

    // ─────────────────────────────────────────────────────────────────────────
    // System prompt for home.html layout generation (used only when include_home_html is true)
    // ─────────────────────────────────────────────────────────────────────────
    private const HOME_HTML_SYSTEM_PROMPT = <<<'PROMPT'
You are a GCMS homepage template author.
Output ONLY the inner HTML fragment for the homepage body — no DOCTYPE, no <html>, <head>, or <body> tags.
The output is a plain GCMS template fragment with widget macros; it is written directly to themes/<slug>/home.html
and rendered inside the theme's page layout (index.html), which already provides the header, main menu and footer.
Use the base home.html in the request as the structural reference: keep its widgets and section patterns,
change the arrangement, order and section styling to match the description.

Structure:
- Outer wrapper: <div class="homepage"> ... </div>
- Optional full-width slideshow and/or hero before the columns:
    <div class="container home-banner-wrap">{WIDGET_TEXTLINKS name=slideshow;layout=slideshow}</div>
    <section class="hero"><div class="container"><div class="hero__content">
      <h1>Heading</h1><p>Intro text</p><a class="btn btn-primary" href="{WEBURL}index.php?module=news">Call to action</a>
    </div></div></section>
- Columns: <div class="container"> containing ONE grid wrapper chosen by sidebar_position:
    right → <div class="sidebar-right gap"> main content first, then the sidebar </div>
    left  → <div class="sidebar-left gap"> the sidebar first, then main content </div>
    both  → <div class="three-columns gap"> left sidebar, main content, right sidebar </div>
    none  → no grid wrapper and no sidebar — only the main content element
- Main content: <div class="content home-content"> ... sections ... </div>
- Sidebar:      <aside class="sidebar home-sidebar"> ... sections ... </aside>

Section markup (use these classes ONLY — do not invent other classes):
  <section class="section-bg">
    <div class="container">
      <div class="section__header">
        <h2 class="icon-news">Title</h2>
        <a href="{WEBURL}index.php?module=news" class="view-more">{LNG_View All}...</a>
      </div>
      <div class="section__body">{WIDGET_DOCUMENT module=news;layout=thumb;rows=1;cols=3}</div>
    </div>
  </section>
  - section__header is optional (omit it for widgets that need no title, e.g. {WIDGET_STATS}, banners);
    the "view more" link is optional and only for widgets that have a listing page.
  - section__body is the ONE content slot of a section and holds one widget macro. Two widgets side by side:
      <div class="section__body"><div class="ggrid"><div class="block6 tablet12">{WIDGET_CONTACT}</div><div class="block6 tablet12">{WIDGET_MAP}</div></div></div>
  - Reuse the icon-* classes and {LNG_...} titles that the base home.html already uses; write any other title as plain text.
  - The main homepage content {DETAIL} MUST appear exactly once, in the main content, as:
      <section class="section-bg home"><div class="container"><div class="section__body"><div class="page-detail">{DETAIL}</div></div></div></section>

Widget macros — syntax {WIDGET_NAME key=value;key=value}. Use ONLY these widgets and parameters, and only module names
that the base home.html already uses (a module that is not installed renders nothing):
  {WIDGET_DOCUMENT module=news;layout=list|icon|thumb|highlight|carousel;rows=N;cols=N;category=1,2}
      articles of a document module (news, knowledge, announce, ...). module is required; layout defaults to icon;
      card is an alias of thumb. list/icon/thumb show rows x cols items (rows 1-20, cols 1-4, default 1x3).
      highlight is fixed at 5 items. carousel uses limit=N (default 6) plus optional mobile=N;tablet=N;desktop=N
      (items per view) and autoplay=0. category (comma-separated category ids) works with every layout.
      e.g. {WIDGET_DOCUMENT module=news;layout=highlight}
           {WIDGET_DOCUMENT module=announce;layout=list;rows=5;cols=1}
           {WIDGET_DOCUMENT module=knowledge;layout=carousel;limit=6;mobile=1;tablet=2;desktop=3}
  {WIDGET_PRODUCT module=product;layout=list|icon|thumb|card|highlight|carousel;rows=N;cols=N;limit=N;category=1,2;mobile=N;tablet=N;desktop=N}
      store products; same rules as WIDGET_DOCUMENT but layout defaults to thumb.
      e.g. {WIDGET_PRODUCT module=product;layout=thumb;rows=2;cols=4}
           {WIDGET_PRODUCT module=product;layout=carousel;limit=8;mobile=1;tablet=2;desktop=4}
  {WIDGET_TEXTLINKS name=slideshow;layout=slideshow}   image slideshow (full width or top of the main content)
  {WIDGET_TEXTLINKS name=banner;layout=banner}         rotating banner image
  {WIDGET_TEXTLINKS name=imagemenu}                    image link menu
  {WIDGET_GALLERY module=gallery;count=N}              latest photo albums (default 6)
  {WIDGET_VIDEO module=video;count=N}                  latest videos (default 6)
  {WIDGET_PORTFOLIO module=portfolio;limit=N}          latest portfolio items (default 6)
  {WIDGET_BOARD module=forum;limit=N}                  latest forum topics (default 5)
  {WIDGET_EVENT module=event;count=N}                  upcoming events (default 5)
  {WIDGET_DOWNLOAD module=download;limit=N}            latest downloads (default 5)
  {WIDGET_PERSONNEL module=personnel;level=1;layout=fade;menu=1}
      staff cards; optional cat=<department id>, level=N (default 1), layout=fade (one person at a time,
      suits the sidebar), menu=1 (adds the department menu)
  {WIDGET_STATS}                                       animated statistics counters
  {WIDGET_SEARCH}                                      site search box
  {WIDGET_TAGS}                                        popular tags cloud
  {WIDGET_COUNTER}                                     visitor counter
  {WIDGET_CONTACT}                                     contact form
  {WIDGET_MAP}                                         map
  {WIDGET_FACEBOOK}                                    Facebook page box
There is no WIDGET_MENU. Menus ({MAINMENU}, {SIDEMENU}, {BOTTOMMENU}) belong to the page layout (index.html) —
never output them in home.html. Apart from the widget macros above, the only macros allowed are
{DETAIL}, {WEBURL} and {LNG_...}.

Plain template rules:
  - Allowed attributes: class and id on any element, href/target/rel on <a>, src/alt/width/height on <img>.
    Do NOT add data-* attributes, ARIA attributes or any other attribute — they are removed.

Security — ABSOLUTELY FORBIDDEN:
  - <script>, <style>, <iframe>, <frame>, <object>, <embed>, <form>, <input>, <button>, <select>, <textarea>
  - on* event attributes (onclick, onload, onmouseover, etc.)
  - javascript: URLs
  - inline CSS styles
  - HTML comments

Output only the HTML fragment. No JSON. No code fences. No explanations.
PROMPT;

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/generate
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate theme.json + CSS from a text prompt.
     * Does NOT write to disk — returns the generated content for preview.
     *
     * Request body (JSON or form):
     *   prompt       string  required  Natural-language theme description
     *   color_scheme string  optional  "light"|"dark"  (default: "light")
     *   name         string  optional  Override theme display name
     *
     * Uses the currently active theme as the structural/style baseline.
     *
     * @param Request $request
     */
    public function generate(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login || $login->status != 1) {
            return $this->errorResponse('Unauthorized', 401);
        }

        if (empty(self::$cfg->ai_enabled)) {
            return $this->errorResponse('AI connector is disabled. Enable it in Settings → AI.', 503);
        }

        $body = $this->jsonBody($request);
        $prompt = Text::topic($body['prompt'] ?? '');
        if ($prompt === '') {
            return $this->errorResponse('prompt is required', 400);
        }

        $colorScheme = in_array($body['color_scheme'] ?? 'light', ['light', 'dark'], true)
            ? ($body['color_scheme'] ?? 'light')
            : 'light';

        $nameHint = Text::topic($body['name'] ?? '');
        $baseSlug = $this->getActiveThemeSlug();
        $baseThemeJson = $this->loadThemeJson($baseSlug);
        $baseThemeLabel = is_array($baseThemeJson) && !empty($baseThemeJson['name']) ? $baseThemeJson['name'] : $baseSlug;
        $baseCss = $this->loadThemeStylesheet($baseSlug);
        $baseHomeHtml = $this->loadThemeHomeHtml($baseSlug);
        $baseThemeVars = $this->allowedThemeVariableNames($baseCss);

        // Build user message
        $userMsg = "Design a GCMS theme by creating a variation of the current active theme.\n";
        $userMsg .= "Description: {$prompt}\n";
        $userMsg .= "Color scheme: {$colorScheme}\n";
        if ($nameHint !== '') {
            $userMsg .= "Theme name: {$nameHint}\n";
        }
        $userMsg .= "Base theme slug: {$baseSlug}\n";
        $userMsg .= "Base theme name: {$baseThemeLabel}\n";
        if ($baseThemeJson !== null) {
            $userMsg .= "Base theme JSON (use this as the starting point and keep the structure compatible unless the prompt requires a change):\n";
            $userMsg .= json_encode($baseThemeJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
        }
        if (!empty($baseThemeVars)) {
            $userMsg .= "Base theme CSS custom properties you may override: ".implode(', ', $baseThemeVars)."\n";
        }
        $userMsg .= "Return only the JSON object as specified.";

        // คำตอบจริงใช้ราว 1.5-2K tokens แต่ reasoning model (openrouter/auto มักเลือก) นับ token
        // ที่ใช้คิดรวมใน max_tokens ด้วย — 4096 จึงถูกตัดกลาง JSON เป็นบางครั้ง และลองใหม่ 1 ครั้ง
        // เพราะคำตอบที่เสีย/ถูกตัดมักเป็นแบบสุ่ม
        $parsed = null;
        $tokens = 0;
        for ($attempt = 1; $attempt <= 2 && $parsed === null; ++$attempt) {
            $response = Ai::driver()->chat(
                [['role' => 'user', 'content' => $userMsg]],
                [
                    'system' => self::SYSTEM_PROMPT,
                    'max_tokens' => 8192,
                    'temperature' => 0.8
                ]
            );

            if (!$response->success) {
                return $this->errorResponse('AI error: '.$response->error, 502);
            }

            $tokens += $response->inputTokens + $response->outputTokens;
            $parsed = $this->parseAiResponse($response->content);
        }

        if (!$parsed) {
            error_log('AI theme: unparseable response (model '.$response->model.', finish '.$response->finishReason
                .', '.$response->outputTokens.' output tokens): '.mb_substr($response->content, 0, 300));
            if ($response->finishReason === 'length') {
                return $this->errorResponse('AI response was cut off before it finished. Try again or use a shorter prompt.', 422);
            }
            return $this->errorResponse('AI returned an invalid response. Try again or rephrase your prompt.', 422);
        }

        $sanitizedCss = $this->sanitizeGeneratedCss($parsed['css'], $baseCss);

        $missingVars = $this->missingRequiredCssVars($sanitizedCss);
        if ($missingVars) {
            return $this->errorResponse('AI response is missing required CSS variables: '.implode(', ', $missingVars), 422);
        }

        $themeJson = $this->normalizeGeneratedThemeJson($parsed['theme_json'], $baseThemeJson, $colorScheme, $nameHint);

        // Derive a slug from the base theme + generated theme name
        $slugSeed = trim($baseSlug.' '.($themeJson['name'] ?? $nameHint ?: 'variant'));
        $slug = $this->makeSlug($slugSeed);

        // ── Optional: generate home.html layout via a second AI call ──
        $homeHtml = null;
        if ($this->toBoolean($body['include_home_html'] ?? false)) {
            $sidebarPos = $themeJson['settings']['sidebar_position'] ?? 'right';
            $homeHtmlMsg = "Generate the homepage layout for a GCMS theme.\n";
            $homeHtmlMsg .= "Description: {$prompt}\n";
            $homeHtmlMsg .= "sidebar_position: {$sidebarPos}\n";
            $homeHtmlMsg .= "color_scheme: {$colorScheme}\n";
            if ($baseHomeHtml !== '') {
                $homeHtmlMsg .= "Base home.html (use as structural reference — keep compatible macros and section patterns, update layout and style to match the description):\n";
                $homeHtmlMsg .= $baseHomeHtml."\n";
            }
            $homeHtmlMsg .= "Output only the HTML fragment as specified.";

            $homeHtmlResponse = Ai::driver()->chat(
                [['role' => 'user', 'content' => $homeHtmlMsg]],
                [
                    'system' => self::HOME_HTML_SYSTEM_PROMPT,
                    // home.html แบบ section เต็ม (เหมือนธีม rw/gts) ยาวราว 5-6KB — 2048 ไม่พอ
                    // และ reasoning model กิน token เพิ่มอีก
                    'max_tokens' => 8192,
                    'temperature' => 0.7
                ]
            );

            // HTML ที่ถูกตัดกลางทางใช้ไม่ได้ — ปล่อย null ให้ใช้ home.html ของธีมต้นแบบแทน
            if ($homeHtmlResponse->success && $homeHtmlResponse->finishReason !== 'length') {
                $rawHtml = trim($homeHtmlResponse->content);
                // Strip any accidental markdown code fences
                $rawHtml = (string) preg_replace('/^```(?:html)?\s*/m', '', $rawHtml);
                $rawHtml = (string) preg_replace('/\s*```\s*$/m', '', $rawHtml);
                $homeHtml = $this->sanitizeHomeHtml(trim($rawHtml));
            }
        }

        return $this->successResponse([
            'slug' => $slug,
            'base_theme' => $baseSlug,
            'base_theme_label' => $baseThemeLabel,
            'theme_json' => $themeJson,
            'css' => $sanitizedCss,
            'css_mode' => self::CSS_MODE,
            'home_html' => $homeHtml,
            'model' => $response->model,
            'tokens' => $tokens
        ], 'Theme generated');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/save
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Save a generated theme to disk.
     * Copies all template files from the captured base theme, then writes
     * theme.json and css/styles.css for the new theme.
     *
     * Request body (JSON or form):
     *   slug        string  required  Directory name (a-z0-9-)
     *   base_theme  string  optional  Source theme folder captured at generate time
     *   theme_json  object  required  Parsed theme metadata
     *   css         string  required  CSS file content
     *   overwrite   bool    optional  true to update existing AI theme in-place
     *
     * @param Request $request
     */
    public function save(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login || $login->status != 1) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $body = $this->jsonBody($request);

        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($body['slug'] ?? ''));
        if ($slug === '') {
            return $this->errorResponse('slug is required', 400);
        }
        if (in_array($slug, ['default', 'empty'], true)) {
            return $this->errorResponse("Reserved theme name: {$slug}", 400);
        }

        $themeJson = $body['theme_json'] ?? null;
        $css = $body['css'] ?? null;
        $overwrite = $this->toBoolean($body['overwrite'] ?? false);
        $homeHtml = isset($body['home_html']) && is_string($body['home_html']) && $body['home_html'] !== ''
            ? $body['home_html']
            : null;
        $savedPrompt = Text::topic($body['prompt'] ?? '');
        if (!is_array($themeJson) || !is_string($css) || $css === '') {
            return $this->errorResponse('theme_json and css are required', 400);
        }

        $destDir = ROOT_PATH."themes/{$slug}/";
        $themeExists = is_dir($destDir);
        $existingThemeJson = $themeExists ? $this->loadThemeJson($slug) : null;
        $existingCss = $themeExists ? $this->loadThemeStylesheet($slug) : '';

        if ($themeExists && !$overwrite) {
            return $this->errorResponse("Theme already exists: {$slug}", 409);
        }

        if ($themeExists && $overwrite) {
            $hasManagedOverrides = preg_match('#/\* GCMS AI Theme Overrides: start \*/.*?/\* GCMS AI Theme Overrides: end \*/#s', $existingCss) === 1;
            // ธีมที่สร้างจาก AI จะมีคีย์ ai.generated_at ใน theme.json
            $isAiTheme = !empty($existingThemeJson['ai']['generated_at']);
            if (!$isAiTheme && !$hasManagedOverrides) {
                return $this->errorResponse("Theme exists and is not marked as AI-generated: {$slug}", 409);
            }
        }

        $baseTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($body['base_theme'] ?? $this->getActiveThemeSlug())));
        $baseTheme = $baseTheme !== '' ? $baseTheme : $this->getActiveThemeSlug();

        if ($themeExists && $overwrite) {
            $existingSourceTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($existingThemeJson['ai']['base_theme'] ?? '')));
            if ($existingSourceTheme !== '') {
                $baseTheme = $existingSourceTheme;
            }
        }

        $srcDir = ROOT_PATH."themes/{$baseTheme}/";
        if (!is_dir($srcDir)) {
            $baseTheme = 'default';
            $srcDir = ROOT_PATH.'themes/default/';
        }

        $baseThemeJson = $this->loadThemeJson($baseTheme);
        $baseCss = $this->loadThemeStylesheet($baseTheme);
        $sanitizeBaseCss = $themeExists && $overwrite && $existingCss !== '' ? $existingCss : $baseCss;
        $normalizeBaseThemeJson = $themeExists && $overwrite && is_array($existingThemeJson) ? $existingThemeJson : $baseThemeJson;

        $css = $this->sanitizeGeneratedCss($css, $sanitizeBaseCss);
        $themeSettings = is_array($themeJson['settings'] ?? null) ? $themeJson['settings'] : [];
        $themeJson = $this->normalizeGeneratedThemeJson(
            $themeJson,
            $normalizeBaseThemeJson,
            (string) ($themeSettings['color_scheme'] ?? 'light'),
            ''
        );
        $sourceTheme = $baseTheme;
        if ($themeExists && $overwrite) {
            $sourceTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($existingThemeJson['ai']['base_theme'] ?? $baseTheme)));
            if ($sourceTheme === '') {
                $sourceTheme = $baseTheme;
            }
        }
        $themeJson['ai'] = array_merge(
            is_array($themeJson['ai'] ?? null) ? $themeJson['ai'] : [],
            [
                'base_theme' => $sourceTheme,
                'generated_at' => date('c'),
                'generated_by' => (int) $login->id,
                'home_html_generated' => $homeHtml !== null,
                'prompt' => $savedPrompt
            ]
        );

        $missingVars = $this->missingRequiredCssVars($css);
        if ($missingVars) {
            return $this->errorResponse('CSS is missing required theme variables: '.implode(', ', $missingVars), 400);
        }

        if (!$themeExists && !is_dir($srcDir)) {
            return $this->errorResponse('Base theme not found — cannot copy template files.', 500);
        }

        if (!$themeExists) {
            // ── Copy template files from the base theme ──
            $this->copyThemeFiles($srcDir, $destDir);
        }

        // ── Write theme.json ──
        file_put_contents(
            $destDir.'theme.json',
            json_encode($themeJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // ── Write css/styles.css by layering AI overrides after the base theme CSS ──
        if (!is_dir($destDir.'css/')) {
            mkdir($destDir.'css/', 0755, true);
        }
        $composeBaseCss = $themeExists && $overwrite && $existingCss !== '' ? $existingCss : $baseCss;
        file_put_contents($destDir.'css/styles.css', $this->composeThemeStylesheet($composeBaseCss, $css));

        // ── Write home.html layout if provided ──
        if ($homeHtml !== null) {
            file_put_contents($destDir.'home.html', $this->sanitizeHomeHtml($homeHtml));
        }

        // ── Generate an SVG screenshot placeholder ──
        $this->writeSvgScreenshot($destDir, $themeJson);

        // Log
        $isOverwriteUpdate = $themeExists && $overwrite;
        \Index\Log\Model::add(0, 'index', 'Index', $isOverwriteUpdate ? "AI Theme updated: {$slug}" : "AI Theme created: {$slug}", $login->id);

        return $this->successResponse([
            'slug' => $slug,
            'base_theme' => $baseTheme,
            'overwritten' => $isOverwriteUpdate,
            'home_html_saved' => $homeHtml !== null,
            'theme_url' => WEB_URL."themes/{$slug}/"
        ], $isOverwriteUpdate ? 'Theme updated' : 'Theme saved');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Decode a JSON body from the request (supports both JSON and form POST).
     */
    private function jsonBody(Request $request): array
    {
        $raw = $request->getParsedBody();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }
        $content = (string) $request->getBody();
        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Parse mixed incoming values into a strict boolean.
     *
     * Accepts: true, 1, "1", "true", "yes", "on".
     */
    private function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * Extract the JSON object from the AI response text.
     * Handles fenced blocks (```json ... ```) and bare JSON objects.
     *
     * @return array|null ['theme_json' => array, 'css' => string] or null on failure
     */
    private function parseAiResponse(string $content): ?array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        $text = trim($text);

        // Find the outermost JSON object
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $jsonStr = substr($text, $start, $end - $start + 1);
        $data = json_decode($jsonStr, true);
        if (!is_array($data)) {
            // Models sometimes put raw line breaks inside the "css" string or leave a
            // trailing comma — invalid JSON, but safe to repair for this payload.
            $repaired = str_replace(["\r", "\n", "\t"], ' ', $jsonStr);
            $repaired = preg_replace('/,\s*([}\]])/', '$1', $repaired);
            $data = json_decode($repaired, true);
        }
        if (!is_array($data)) {
            return null;
        }

        if (!isset($data['theme_json']) || !is_array($data['theme_json'])) {
            return null;
        }
        if (!isset($data['css']) || !is_string($data['css']) || trim($data['css']) === '') {
            return null;
        }

        return $data;
    }

    /**
     * Normalize generated theme JSON against the captured base theme so AI output
     * keeps compatible structure and does not drop useful settings.
     */
    private function normalizeGeneratedThemeJson(array $generated, ?array $baseThemeJson, string $colorScheme, string $nameHint): array
    {
        $defaults = $this->loadThemeJson('default') ?? [];
        $base = is_array($baseThemeJson) ? $baseThemeJson : [];

        $name = Text::topic($generated['name'] ?? '');
        if ($name === '') {
            $name = Text::topic($nameHint);
        }
        if ($name === '') {
            $name = Text::topic($base['name'] ?? '');
        }
        $name = $name !== '' ? $name : 'AI Theme';

        $description = trim((string) ($generated['description'] ?? $base['description'] ?? $defaults['description'] ?? ''));
        if ($description === '') {
            $description = 'AI generated GCMS theme.';
        }

        $layouts = $this->normalizeThemeCollection(
            $generated['layouts'] ?? null,
            $base['layouts'] ?? null,
            self::FALLBACK_LAYOUTS
        );
        $components = $this->normalizeThemeCollection(
            $generated['components'] ?? null,
            $base['components'] ?? null,
            self::FALLBACK_COMPONENTS
        );

        $generatedSettings = is_array($generated['settings'] ?? null) ? $generated['settings'] : [];
        $baseSettings = is_array($base['settings'] ?? null) ? $base['settings'] : [];
        $defaultSettings = is_array($defaults['settings'] ?? null) ? $defaults['settings'] : [];
        $settings = array_merge($defaultSettings, $baseSettings, $generatedSettings);
        $settings['show_sidebar'] = array_key_exists('show_sidebar', $settings) ? (bool) $settings['show_sidebar'] : true;

        $sidebarPosition = strtolower((string) ($settings['sidebar_position'] ?? 'right'));
        if (!in_array($sidebarPosition, ['left', 'right', 'both', 'none'], true)) {
            $sidebarPosition = strtolower((string) ($baseSettings['sidebar_position'] ?? $defaultSettings['sidebar_position'] ?? 'right'));
        }
        if (!in_array($sidebarPosition, ['left', 'right', 'both', 'none'], true)) {
            $sidebarPosition = 'right';
        }
        $settings['sidebar_position'] = $sidebarPosition;
        $settings['footer_columns'] = max(1, min(4, (int) ($settings['footer_columns'] ?? 3)));
        $settings['show_breadcrumb'] = array_key_exists('show_breadcrumb', $settings) ? (bool) $settings['show_breadcrumb'] : true;
        $settings['show_search'] = array_key_exists('show_search', $settings) ? (bool) $settings['show_search'] : true;
        if (isset($settings['products_per_page'])) {
            $settings['products_per_page'] = max(1, (int) $settings['products_per_page']);
        }
        $settings['color_scheme'] = in_array(($settings['color_scheme'] ?? $colorScheme), ['light', 'dark'], true)
            ? ($settings['color_scheme'] ?? $colorScheme)
            : (in_array($colorScheme, ['light', 'dark'], true) ? $colorScheme : 'light');

        $generatedColors = is_array($generated['colors'] ?? null) ? $generated['colors'] : [];
        $baseColors = is_array($base['colors'] ?? null) ? $base['colors'] : [];
        $defaultColors = is_array($defaults['colors'] ?? null) ? $defaults['colors'] : [];
        $colors = array_merge($defaultColors, $baseColors, $generatedColors);
        $isDark = $settings['color_scheme'] === 'dark';
        $background = (string) ($colors['background'] ?? ($isDark ? '#0f172a' : '#ffffff'));
        $text = (string) ($colors['text'] ?? ($isDark ? '#f8fafc' : '#1e293b'));
        $colors['primary'] = (string) ($colors['primary'] ?? '#2563eb');
        $colors['primary_hover'] = (string) ($colors['primary_hover'] ?? $colors['primary']);
        $colors['secondary'] = (string) ($colors['secondary'] ?? '#6b7280');
        $colors['accent'] = (string) ($colors['accent'] ?? '#10b981');
        $colors['background'] = $background;
        $colors['surface'] = (string) ($colors['surface'] ?? $background);
        $colors['text'] = $text;
        $colors['text_secondary'] = (string) ($colors['text_secondary'] ?? $text);
        $colors['border'] = (string) ($colors['border'] ?? ($isDark ? '#334155' : '#e2e8f0'));

        return [
            'name' => $name,
            'version' => trim((string) ($generated['version'] ?? $base['version'] ?? $defaults['version'] ?? '1.0.0')),
            'author' => trim((string) ($generated['author'] ?? $base['author'] ?? 'AI Generated')),
            'author_url' => trim((string) ($generated['author_url'] ?? $base['author_url'] ?? '')),
            'description' => $description,
            'screenshot' => 'screenshot.svg',
            'license' => trim((string) ($generated['license'] ?? $base['license'] ?? $defaults['license'] ?? 'MIT')),
            'layouts' => $layouts,
            'components' => $components,
            'colors' => $colors,
            'settings' => $settings
        ];
    }

    /**
     * Keep generated/base arrays if present, otherwise fall back to canonical defaults.
     */
    private function normalizeThemeCollection($generated, $base, array $fallback): array
    {
        $baseItems = is_array($base) ? array_values($base) : [];
        $fallbackItems = array_values($fallback);
        $preferredItems = !empty($baseItems) ? $baseItems : $fallbackItems;
        $generatedItems = is_array($generated) ? array_values($generated) : [];

        if (empty($generatedItems)) {
            return !empty($preferredItems) ? $preferredItems : $fallbackItems;
        }

        if ($this->themeCollectionUsesObjects($preferredItems ?: $generatedItems)) {
            $normalized = $this->normalizeObjectThemeCollection($generatedItems, $preferredItems, $fallbackItems);
        } else {
            $normalized = $this->normalizeStringThemeCollection($generatedItems, $preferredItems, $fallbackItems);
        }

        if (!empty($normalized)) {
            return $normalized;
        }

        return !empty($preferredItems) ? $preferredItems : $fallbackItems;
    }

    /**
     * @param array $items
     */
    private function themeCollectionUsesObjects(array $items): bool
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                return true;
            }
            if (is_string($item) && trim($item) !== '') {
                return false;
            }
        }

        return false;
    }

    /**
     * @param mixed $item
     */
    private function themeCollectionItemId($item): string
    {
        if (is_string($item)) {
            return trim($item);
        }
        if (is_array($item)) {
            return trim((string) ($item['id'] ?? ''));
        }

        return '';
    }

    /**
     * @param array $generatedItems
     * @param array $preferredItems
     * @param array $fallbackItems
     */
    private function normalizeStringThemeCollection(array $generatedItems, array $preferredItems, array $fallbackItems): array
    {
        $preferredIds = [];
        foreach (!empty($preferredItems) ? $preferredItems : $fallbackItems as $item) {
            $id = $this->themeCollectionItemId($item);
            if ($id !== '') {
                $preferredIds[] = $id;
            }
        }

        $generatedIds = [];
        foreach ($generatedItems as $item) {
            $id = $this->themeCollectionItemId($item);
            if ($id !== '') {
                $generatedIds[] = $id;
            }
        }

        $result = [];
        foreach (array_merge($preferredIds, $generatedIds) as $id) {
            if ($id === '' || in_array($id, $result, true)) {
                continue;
            }
            $result[] = $id;
        }

        return $result;
    }

    /**
     * @param array $generatedItems
     * @param array $preferredItems
     * @param array $fallbackItems
     */
    private function normalizeObjectThemeCollection(array $generatedItems, array $preferredItems, array $fallbackItems): array
    {
        $lookup = [];
        foreach (array_merge($fallbackItems, $preferredItems) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = $this->themeCollectionItemId($item);
            if ($id !== '') {
                $lookup[$id] = $item;
            }
        }

        $generatedLookup = [];
        foreach ($generatedItems as $item) {
            $id = $this->themeCollectionItemId($item);
            if ($id === '') {
                continue;
            }

            if (is_array($item)) {
                $generatedLookup[$id] = array_merge($lookup[$id] ?? ['id' => $id], $item);
            } else {
                $generatedLookup[$id] = $lookup[$id] ?? ['id' => $id];
            }
        }

        $result = [];
        $seen = [];
        foreach (!empty($preferredItems) ? $preferredItems : $fallbackItems as $item) {
            $id = $this->themeCollectionItemId($item);
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $result[] = $generatedLookup[$id] ?? ($lookup[$id] ?? ['id' => $id]);
            $seen[$id] = true;
        }

        foreach ($generatedLookup as $id => $item) {
            if (isset($seen[$id])) {
                continue;
            }
            $result[] = $item;
            $seen[$id] = true;
        }

        return $result;
    }

    /**
     * Return the list of required CSS custom properties missing from the AI CSS.
     */
    private function missingRequiredCssVars(string $css): array
    {
        $missing = [];
        foreach (self::REQUIRED_CSS_VARS as $var) {
            if (!preg_match('/'.preg_quote($var, '/').'\s*:/', $css)) {
                $missing[] = $var;
            }
        }
        return $missing;
    }

    /**
     * Restrict AI CSS to strict token overrides: a managed :root block only.
     *
     * We keep the original base :root untouched and append a second :root block
     * that overrides only allowed custom properties.
     */
    private function sanitizeGeneratedCss(string $css, string $baseCss): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $css = $this->stripCssAtRules($css);

        $baseCss = preg_replace('#/\*.*?\*/#s', '', $baseCss);
        $baseCss = $this->stripCssAtRules($baseCss);

        $allowedVars = $this->allowedThemeVariableNames($baseCss);
        $rootDeclarations = $this->extractRootVariableDeclarations($baseCss, $allowedVars);
        $generatedRootDeclarations = $this->extractRootVariableDeclarations($css, $allowedVars);

        foreach ($generatedRootDeclarations as $property => $value) {
            $rootDeclarations[$property] = $value;
        }

        if (empty($rootDeclarations)) {
            return '';
        }

        ksort($rootDeclarations);

        return trim($this->renderCssRule([':root'], $rootDeclarations));
    }

    /**
     * Parse :root custom property declarations from CSS.
     */
    private function extractRootVariableDeclarations(string $css, array $allowedVars): array
    {
        $rootDeclarations = [];

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $selectors = array_filter(array_map(static function ($selector): string {
                return preg_replace('/\s+/', ' ', trim($selector));
            }, explode(',', $match[1])));

            if (empty($selectors) || !in_array(':root', $selectors, true)) {
                continue;
            }

            foreach ($this->parseCssDeclarations($match[2]) as [$property, $value]) {
                if (!$this->isAllowedThemeVariable($property, $allowedVars) || !$this->isSafeCssValue($value)) {
                    continue;
                }
                $rootDeclarations[$property] = $value;
            }
        }

        return $rootDeclarations;
    }

    /**
     * @param string $css
     * @return mixed
     */
    private function stripCssAtRules(string $css): string
    {
        $output = '';
        $length = strlen($css);
        $depth = 0;

        for ($index = 0; $index < $length; ++$index) {
            $char = $css[$index];

            if ($char === '@' && $depth === 0) {
                $bracePos = strpos($css, '{', $index);
                $semicolonPos = strpos($css, ';', $index);

                if ($semicolonPos !== false && ($bracePos === false || $semicolonPos < $bracePos)) {
                    $index = $semicolonPos;
                    continue;
                }

                if ($bracePos !== false) {
                    $nestedDepth = 1;
                    $index = $bracePos;
                    while (++$index < $length && $nestedDepth > 0) {
                        if ($css[$index] === '{') {
                            ++$nestedDepth;
                        } elseif ($css[$index] === '}') {
                            --$nestedDepth;
                        }
                    }
                    --$index;
                    continue;
                }
            }

            $output .= $char;

            if ($char === '{') {
                ++$depth;
            } elseif ($char === '}' && $depth > 0) {
                --$depth;
            }
        }

        return $output;
    }

    /**
     * @param string $block
     * @return mixed
     */
    private function parseCssDeclarations(string $block): array
    {
        $parts = [];
        $buffer = '';
        $parenDepth = 0;
        $quote = '';
        $length = strlen($block);

        for ($index = 0; $index < $length; ++$index) {
            $char = $block[$index];
            if ($quote !== '') {
                if ($char === $quote && ($index === 0 || $block[$index - 1] !== '\\')) {
                    $quote = '';
                }
                $buffer .= $char;
                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '(') {
                ++$parenDepth;
            } elseif ($char === ')' && $parenDepth > 0) {
                --$parenDepth;
            }

            if ($char === ';' && $parenDepth === 0) {
                $parts[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $parts[] = $buffer;
        }

        $declarations = [];
        foreach ($parts as $part) {
            $pair = explode(':', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }
            $property = strtolower(trim($pair[0]));
            $value = trim($pair[1]);
            if ($property === '' || $value === '') {
                continue;
            }
            $declarations[] = [$property, $value];
        }

        return $declarations;
    }

    /**
     * @param string $property
     * @param array $allowedVars
     */
    private function isAllowedThemeVariable(string $property, array $allowedVars): bool
    {
        return str_starts_with($property, '--') && in_array($property, $allowedVars, true);
    }

    /**
     * @param string $value
     */
    private function isSafeCssValue(string $value): bool
    {
        if ($value === '' || preg_match('/[{}<>]/', $value)) {
            return false;
        }
        if (preg_match('/(?:@import|expression\s*\(|javascript\s*:|url\s*\()/i', $value)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $selectors
     * @param array $declarations
     */
    private function renderCssRule(array $selectors, array $declarations): string
    {
        $lines = [];
        foreach ($declarations as $property => $value) {
            $lines[] = '  '.$property.': '.$value.';';
        }

        return implode(', ', $selectors)."\n{"."\n".implode("\n", $lines)."\n}";
    }

    /**
     * @param string $baseCss
     * @return mixed
     */
    private function allowedThemeVariableNames(string $baseCss): array
    {
        $vars = self::REQUIRED_CSS_VARS;
        preg_match_all('/(--[a-z0-9\-_]+)\s*:/i', $baseCss, $matches);
        foreach ($matches[1] ?? [] as $varName) {
            if ($this->isTokenOnlyVariableName((string) $varName)) {
                $vars[] = strtolower((string) $varName);
            }
        }
        preg_match_all('/var\(\s*(--[a-z0-9\-_]+)/i', $baseCss, $varReferences);
        foreach ($varReferences[1] ?? [] as $varName) {
            if ($this->isTokenOnlyVariableName((string) $varName)) {
                $vars[] = strtolower((string) $varName);
            }
        }

        $vars = array_values(array_unique(array_filter($vars, static fn($name) => is_string($name) && $name !== '')));
        sort($vars);

        return $vars;
    }

    /**
     * @param string $name
     */
    private function isTokenOnlyVariableName(string $name): bool
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return false;
        }
        if (in_array($name, self::REQUIRED_CSS_VARS, true)) {
            return true;
        }

        return preg_match('/^--(?:color-|home-|footer-|header-|board-|hero-|card-|nav-|section-|menu-|topmenu-|widget-)/', $name) === 1
        || in_array($name, ['--shadow', '--border-radius'], true);
    }

    /**
     * Sanitize AI-generated home.html.
     * Preserves GCMS widget macros ({WIDGET_*}, {DETAIL}) while enforcing a
     * strict tag and attribute allowlist using DOMDocument.
     */
    private function sanitizeHomeHtml(string $html): string
    {
        // Tags whose output is permitted; anything else is unwrapped
        /**
         * @var array
         */
        static $allowedTags = [
            'div', 'section', 'aside', 'nav', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'p', 'a', 'img', 'span', 'strong', 'em',
            'blockquote', 'figure', 'figcaption', 'header', 'footer', 'article'
        ];

        // Tags whose content must also be dropped entirely
        /**
         * @var array
         */
        static $blockedTags = [
            'script', 'style', 'iframe', 'frame', 'frameset',
            'object', 'embed', 'applet', 'noscript'
        ];

        // Attributes allowed on every element — home.html เป็นเทมเพลตธรรมดา
        // ไม่มี data-* ใดๆ
        /**
         * @var array
         */
        static $globalAttrs = ['class', 'id'];

        // Extra attributes per tag
        /**
         * @var array
         */
        static $tagAttrs = [
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'alt', 'width', 'height']
        ];

        // ── Step 1: Preserve GCMS macros ──
        // The pattern MUST cover every macro the renderer accepts, otherwise an
        // un-captured macro is left as a raw text node and DOMDocument may mangle
        // its value (e.g. an ampersand in a parameter becomes &amp;, corrupting
        // the macro). The widget grammar mirrors the renderer in Web/View.php:
        //   /{WIDGET_([A-Z]+)([_\s]+([^}]+))?}/   — name is A-Z, params are [^}]
        // so parameters may contain lower-case text such as "module=news;limit=5".
        // The second alternative matches plain upper-case macros like {DETAIL}.
        $macros = [];
        $html = (string) preg_replace_callback(
            '/\{WIDGET_[A-Z]+(?:[_\s][^{}<>]*)?\}|\{[A-Z_][A-Z0-9_]*\}/',
            static function (array $m) use (&$macros): string {
                $key = '___MACRO_'.count($macros).'___';
                $macros[$key] = $m[0];
                return $key;
            },
            $html
        );

        // ── Step 2: Parse fragment via DOMDocument ──
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>');
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body !== null) {
            $this->sanitizeDomNode($body, $allowedTags, $blockedTags, $globalAttrs, $tagAttrs);
            $this->ensureHomepageWrapper($doc, $body);

            // The {DETAIL} macro is the CMS-editable main content area and must appear
            // exactly once. Models sometimes drop it, leaving the homepage with no
            // editable body, so inject a detail block when it is missing.
            if (!in_array('{DETAIL}', $macros, true)) {
                $placeholder = '___MACRO_'.count($macros).'___';
                $macros[$placeholder] = '{DETAIL}';
                $this->injectDetailBlock($doc, $body, $placeholder);
            }
        }

        // ── Step 3: Serialize body children ──
        $result = '';
        if ($body !== null) {
            foreach ($body->childNodes as $child) {
                $result .= $doc->saveHTML($child);
            }
        }

        // ── Step 4: Restore macros ──
        return str_replace(array_keys($macros), array_values($macros), $result);
    }

    /**
     * Recursive DOM walker: enforce tag allowlist, strip blocked tags with
     * their content, and remove disallowed attributes.
     *
     * @param array<string> $allowedTags
     * @param array<string> $blockedTags
     * @param array<string> $globalAttrs
     * @param array<string,array<string>> $tagAttrs
     */
    private function sanitizeDomNode(\DOMNode $node, array $allowedTags, array $blockedTags, array $globalAttrs, array $tagAttrs): void
    {
        $toRemove = []; // drop with content
        $toUnwrap = []; // drop tag, keep children

        foreach ($node->childNodes as $child) {
            // Drop HTML comments and PHP/XML processing instructions such as opening
            // php/short-echo tags. The HTML system prompt forbids both; strip them as
            // defense-in-depth so no raw PHP tag can ever reach a theme file.
            if ($child instanceof \DOMComment  || $child instanceof \DOMProcessingInstruction) {
                $toRemove[] = $child;
                continue;
            }

            if (!($child instanceof \DOMElement)) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, $blockedTags, true)) {
                $toRemove[] = $child;
                continue;
            }

            if (!in_array($tag, $allowedTags, true)) {
                $toUnwrap[] = $child;
                continue;
            }

            // Sanitize attributes on allowed elements
            $allowed = array_merge($globalAttrs, $tagAttrs[$tag] ?? []);
            $attrToRemove = [];
            foreach ($child->attributes as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed, true)) {
                    $attrToRemove[] = $attr->name;
                } elseif (in_array($name, ['href', 'src'], true)) {
                    if (preg_match('/^\s*(javascript|vbscript|data:text\/html)/i', $attr->value)) {
                        $attrToRemove[] = $attr->name;
                    }
                }
            }
            foreach ($attrToRemove as $a) {
                $child->removeAttribute($a);
            }

            // Recurse into allowed element
            $this->sanitizeDomNode($child, $allowedTags, $blockedTags, $globalAttrs, $tagAttrs);
        }

        // Drop blocked elements (with content)
        foreach ($toRemove as $el) {
            $node->removeChild($el);
        }

        // Unwrap unknown-but-safe elements (keep children, drop tag)
        foreach ($toUnwrap as $el) {
            while ($el->hasChildNodes()) {
                $node->insertBefore($el->firstChild, $el);
            }
            $node->removeChild($el);
        }
    }

    /**
     * Guarantee the homepage fragment is wrapped in a single <div class="homepage">.
     *
     * The home.html system prompt asks the model to emit this wrapper, but models
     * frequently omit it (observed with smaller models), which breaks any CSS scoped
     * to .homepage. If the body is not already a single .homepage div, move all of
     * its children into a fresh wrapper.
     */
    private function ensureHomepageWrapper(\DOMDocument $doc, \DOMElement $body): void
    {
        $elementChildren = [];
        foreach ($body->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $elementChildren[] = $child;
            }
        }

        // Already wrapped: exactly one top-level <div> carrying the "homepage" class.
        if (count($elementChildren) === 1 && strtolower($elementChildren[0]->nodeName) === 'div') {
            $classes = preg_split('/\s+/', trim($elementChildren[0]->getAttribute('class')));
            if (in_array('homepage', $classes, true)) {
                return;
            }
        }

        $wrapper = $doc->createElement('div');
        $wrapper->setAttribute('class', 'homepage');
        while ($body->firstChild !== null) {
            $wrapper->appendChild($body->firstChild);
        }
        $body->appendChild($wrapper);
    }

    /**
     * Append a {DETAIL} block when the homepage has none.
     *
     * Prefers the main content column (.home-content or .content); falls back
     * to the .homepage wrapper. $detailPlaceholder is the macro placeholder
     * that Step 4 restores back to the literal {DETAIL} token.
     */
    private function injectDetailBlock(\DOMDocument $doc, \DOMElement $body, string $detailPlaceholder): void
    {
        $target = null;
        foreach ($body->getElementsByTagName('*') as $el) {
            $classes = preg_split('/\s+/', trim($el->getAttribute('class')));
            if (in_array('home-content', $classes, true) || in_array('content', $classes, true)) {
                $target = $el;
                break;
            }
        }
        if ($target === null) {
            foreach ($body->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $target = $child;
                    break;
                }
            }
        }
        if ($target === null) {
            $target = $body;
        }

        // รูปแบบเดียวกับธีมที่มากับระบบ (themes/rw, gts, shop):
        // section.section-bg.home > .container > .section__body > .page-detail
        $section = $doc->createElement('section');
        $section->setAttribute('class', 'section-bg home');
        $container = $doc->createElement('div');
        $container->setAttribute('class', 'container');
        $slot = $doc->createElement('div');
        $slot->setAttribute('class', 'section__body');
        $prose = $doc->createElement('div');
        $prose->setAttribute('class', 'page-detail');
        $prose->appendChild($doc->createTextNode($detailPlaceholder));
        $slot->appendChild($prose);
        $container->appendChild($slot);
        $section->appendChild($container);
        $target->appendChild($section);
    }

    /**
     * Keep the base theme stylesheet intact and append a managed AI override block.
     */
    private function composeThemeStylesheet(string $baseCss, string $aiCss): string
    {
        $managedStart = '/* GCMS AI Theme Overrides: start */';
        $managedEnd = '/* GCMS AI Theme Overrides: end */';

        $baseCss = preg_replace('#/\* GCMS AI Theme Overrides: start \*/.*?/\* GCMS AI Theme Overrides: end \*/\s*#s', '', $baseCss);
        $baseCss = rtrim((string) $baseCss);

        $aiCss = preg_replace('/^\xEF\xBB\xBF/', '', $aiCss);
        $aiCss = preg_replace('/^\s*@charset\s+[^;]+;\s*/i', '', $aiCss);
        $aiCss = trim($aiCss);

        if ($aiCss === '') {
            return $baseCss;
        }

        $overrideBlock = $managedStart."\n".$aiCss."\n".$managedEnd;

        if ($baseCss === '') {
            return $overrideBlock."\n";
        }

        return $baseCss."\n\n".$overrideBlock."\n";
    }

    /**
     * Convert a display name to a filesystem-safe slug.
     * e.g. "Coffee Shop Theme" → "coffee-shop-theme"
     */
    private function makeSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 40) ?: 'ai-theme';

        // Ensure uniqueness
        $base = $slug;
        $i = 2;
        while (is_dir(ROOT_PATH."themes/{$slug}/")) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * Return the currently active theme slug with a safe default.
     */
    private function getActiveThemeSlug(): string
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) (self::$cfg->skin ?? 'default')));

        if ($slug === '' || !is_dir(ROOT_PATH."themes/{$slug}/")) {
            return 'default';
        }

        return $slug;
    }

    /**
     * @param string $slug
     */
    private function loadThemeStylesheet(string $slug): string
    {
        $file = ROOT_PATH."themes/{$slug}/css/styles.css";

        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /**
     * Load themes/<slug>/home.html to provide AI context for layout generation.
     * Returns empty string if the file does not exist.
     */
    private function loadThemeHomeHtml(string $slug): string
    {
        $file = ROOT_PATH."themes/{$slug}/home.html";

        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /**
     * Load and decode themes/<slug>/theme.json.
     */
    private function loadThemeJson(string $slug): ?array
    {
        $file = ROOT_PATH."themes/{$slug}/theme.json";
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Recursively copy template files from $src to $dest.
     * Skips theme.json and screenshot files; css assets are copied and styles.css is rewritten later.
     */
    private function copyThemeFiles(string $src, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $skip = ['theme.json', 'screenshot.svg', 'screenshot.png', 'screenshot.jpg', 'screenshot.webp'];

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $srcPath = $src.$item;
            $destPath = $dest.$item;

            if (is_dir($srcPath)) {
                $this->copyThemeFiles($srcPath.'/', $destPath.'/');
            } elseif (!in_array($item, $skip, true)) {
                copy($srcPath, $destPath);
            }
        }
    }

    /**
     * Write a simple SVG screenshot placeholder using the theme's primary color.
     */
    private function writeSvgScreenshot(string $destDir, array $themeJson): void
    {
        $primary = $themeJson['colors']['primary'] ?? '#6366f1';
        $background = $themeJson['colors']['background'] ?? '#ffffff';
        $accent = $themeJson['colors']['accent'] ?? '#22d3ee';
        $text = $themeJson['colors']['text'] ?? '#1e293b';
        $name = htmlspecialchars($themeJson['name'] ?? 'Theme', ENT_XML1);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="800" height="500">
  <!-- Background -->
  <rect width="800" height="500" fill="{$background}"/>
  <!-- Header bar -->
  <rect width="800" height="56" fill="{$primary}"/>
  <rect x="20" y="18" width="120" height="20" rx="4" fill="white" opacity="0.9"/>
  <rect x="600" y="18" width="60" height="20" rx="10" fill="{$accent}"/>
  <rect x="680" y="18" width="60" height="20" rx="10" fill="white" opacity="0.5"/>
  <!-- Hero -->
  <rect y="56" width="800" height="140" fill="{$primary}" opacity="0.15"/>
  <rect x="200" y="90" width="400" height="28" rx="6" fill="{$primary}" opacity="0.7"/>
  <rect x="280" y="130" width="240" height="16" rx="4" fill="{$primary}" opacity="0.4"/>
  <!-- Cards row -->
  <rect x="20" y="220" width="240" height="130" rx="8" fill="white" stroke="{$primary}" stroke-width="1.5" opacity="0.9"/>
  <rect x="280" y="220" width="240" height="130" rx="8" fill="white" stroke="{$primary}" stroke-width="1.5" opacity="0.9"/>
  <rect x="540" y="220" width="240" height="130" rx="8" fill="white" stroke="{$primary}" stroke-width="1.5" opacity="0.9"/>
  <rect x="36" y="238" width="120" height="12" rx="3" fill="{$primary}" opacity="0.6"/>
  <rect x="36" y="260" width="180" height="8" rx="2" fill="{$text}" opacity="0.2"/>
  <rect x="36" y="276" width="160" height="8" rx="2" fill="{$text}" opacity="0.15"/>
  <rect x="296" y="238" width="120" height="12" rx="3" fill="{$primary}" opacity="0.6"/>
  <rect x="296" y="260" width="180" height="8" rx="2" fill="{$text}" opacity="0.2"/>
  <rect x="556" y="238" width="120" height="12" rx="3" fill="{$primary}" opacity="0.6"/>
  <!-- Footer -->
  <rect y="430" width="800" height="70" fill="{$primary}"/>
  <text x="400" y="472" text-anchor="middle" font-family="sans-serif" font-size="13" fill="white" opacity="0.85">{$name}</text>
  <!-- Accent bottom line -->
  <rect y="496" width="800" height="4" fill="{$accent}"/>
</svg>
SVG;

        file_put_contents($destDir.'screenshot.svg', $svg);
    }
}
