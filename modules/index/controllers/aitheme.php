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
You are a professional web-theme designer for the GCMS CMS platform.
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
You are a GCMS homepage template designer.
Output ONLY the inner HTML fragment for the homepage body — no DOCTYPE, no <html>, <head>, or <body> tags.
The output is a PHP template fragment with GCMS widget macros; it will be written directly to themes/<slug>/home.html.

Structure:
- Outer wrapper: <div class="homepage" data-gcms-page="home"> ... </div>
    The data-gcms-page="home" attribute is REQUIRED — the visual editor activates on it.
- Hero: a full-width hero placed BEFORE the main container. It MUST sit in its own
    container so the editor can manage it (never place a block outside a container):
      <div data-editor-container="hero">
        <section class="hero" data-block-type="hero" data-editor-id="block-hero" data-editor-label="Hero">
          <div class="container">
            <div class="hero__content"> ... heading + intro + CTA ... </div>
          </div>
        </section>
      </div>
- Container: <div class="container">
- Inside the container, use the layout class for the given sidebar_position:
    left  → <div class="sidebar-left gap home-layout">
    right → <div class="sidebar-right gap home-layout">
    both  → <div class="three-columns gap">
    none  → omit sidebar, just use <div class="content">
- Sidebar: <aside class="sidebar home-sidebar" data-editor-container="sidebar">
- Main content: <div class="content home-content" data-editor-container="content">

Section containers (use these ONLY — do not invent other classes):
  <section class="section-bg">
    <div class="section__header"><div><h2>Title</h2></div></div>
    <div class="section__body"> ... one widget macro or content ... </div>
  </section>
  Class roles — never mix them:
    section__header / section__body  the section's title bar and its ONE content slot (layout only)
    page-detail                      prose typography for CMS HTML; ALWAYS a child inside the slot:
                                     <div class="section__body"><div class="page-detail">{DETAIL}</div></div>
    hero__content                    the text stack inside the hero (eyebrow, h1, p, buttons)

Available GCMS widget macros:
  {WIDGET_DOCUMENT module=news}                         — news article list
  {WIDGET_DOCUMENT module=news;layout=highlight;limit=5} — highlighted news
  {WIDGET_DOCUMENT module=news;layout=carousel;limit=6}  — news carousel
  {WIDGET_DOCUMENT module=news;layout=list;limit=5}       — compact news list (sidebar)
  {WIDGET_DOCUMENT module=news;layout=category}           — category list (sidebar)
  {WIDGET_DOCUMENT module=news;category=1,2,3}            — any layout, restricted to these category_id
  {WIDGET_PERSONNEL cat=1;menu=1}                       — personnel/staff directory
  {WIDGET_PERSONNEL layout=fade}                        — personnel stacked, fading one person at a time (sidebar)
  {WIDGET_BOARD module=forum;limit=5}                   — message board / recent topics
  {WIDGET_GALLERY module=gallery;count=4}               — photo gallery
  {WIDGET_SEARCH}                                       — site search box
  {WIDGET_STATS}                                        — site statistics counters
  {WIDGET_FACEBOOK}                                     — Facebook page widget
  {WIDGET_TAGS}                                         — popular tags cloud
  {DETAIL}                                              — main homepage editable content (always include in the main area) as:
      <section class="section-bg" data-block-type="home-cms" data-editor-id="block-detail" data-editor-label="Page Content">
        <div class="section__body"><div class="page-detail">{DETAIL}</div></div>
      </section>

Designer compatibility — REQUIRED for every generated template:
  - The main content container MUST have: data-editor-container="content"
  - The sidebar container (if any) MUST have: data-editor-container="sidebar"
  - The hero MUST be in its own data-editor-container="hero" (see Structure).
  - EVERY block (panel/section) MUST be a direct or nested child of a
    data-editor-container. NEVER place a block outside a container — the editor
    cannot select, move or delete an orphan block.
  - Every block element MUST have ALL THREE:
      data-block-type="<type>"     — type slug (e.g. widget-document, home-cms, hero, banner,
                                      widget-gallery, widget-board, widget-search, widget-stats,
                                      widget-facebook, widget-tags, widget-personnel)
      data-editor-id="block-<name>" — unique ID within the template (e.g. block-news, block-detail)
      data-editor-label="<label>"  — display name shown in the Layers panel (Thai or English)
  - Wrap each logical content group in one block element carrying these three attributes.

Editable content — so the user can edit text/images in the Designer:
  - Add data-editable="text" AND a UNIQUE data-editor-field-name="<snake_case>" to every
    element that holds visible editable text: headings (h1-h4), paragraphs, hero subtitle,
    button/CTA links, and link labels. Example:
      <h1 data-editable="text" data-editor-field-name="hero_title">...</h1>
      <a class="btn" href="#" data-editable="text" data-editor-field-name="hero_cta">...</a>
  - Add data-editable="image" + a unique data-editor-field-name to every <img> the user
    should be able to replace.
  - data-editor-field-name values MUST be unique across the whole template.
  - Do NOT mark widget macros, {DETAIL}, or elements that only contain a macro/other blocks.
  - For a list of repeated links/cards, put data-repeat-container on the parent and
    data-repeat-item on each item so the user can add/remove rows.

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

        // Accept live designer CSS vars from the frontend (unsaved/unpublished designer state).
        // Appending them after the on-disk base CSS means sanitizeGeneratedCss will see them
        // as the effective current values (later :root declarations win the merge).
        $sanitizedLiveVars = [];
        $baseCssVarsOverride = is_array($body['base_css_vars'] ?? null) ? $body['base_css_vars'] : [];
        foreach ($baseCssVarsOverride as $name => $value) {
            if (!is_string($name) || !preg_match('/^--[a-z0-9\-_]+$/i', $name)) {
                continue;
            }
            if (!is_string($value) || strlen($value) > 200) {
                continue;
            }
            $value = preg_replace('/[^a-zA-Z0-9,\.\s#%\(\)\-_\/]/', '', $value);
            if ($value !== '') {
                $sanitizedLiveVars[$name] = $value;
            }
        }
        if (!empty($sanitizedLiveVars)) {
            $liveLines = [];
            foreach ($sanitizedLiveVars as $n => $v) {
                $liveLines[] = "  {$n}: {$v};";
            }
            $baseCss .= "\n\n:root {\n".implode("\n", $liveLines)."\n}";
            $baseThemeVars = $this->allowedThemeVariableNames($baseCss);
        }

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
        if (!empty($sanitizedLiveVars)) {
            $varList = [];
            foreach ($sanitizedLiveVars as $n => $v) {
                $varList[] = "{$n}: {$v}";
            }
            $userMsg .= "User's current active CSS variable overrides (treat as effective base, adjust to match the prompt): ".implode(', ', $varList)."\n";
        }
        $userMsg .= "Return only the JSON object as specified.";

        $response = Ai::driver()->chat(
            [['role' => 'user', 'content' => $userMsg]],
            [
                'system' => self::SYSTEM_PROMPT,
                'max_tokens' => 4096,
                'temperature' => 0.8
            ]
        );

        if (!$response->success) {
            return $this->errorResponse('AI error: '.$response->error, 502);
        }

        $parsed = $this->parseAiResponse($response->content);
        if (!$parsed) {
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
                    'max_tokens' => 2048,
                    'temperature' => 0.7
                ]
            );

            if ($homeHtmlResponse->success) {
                $rawHtml = trim($homeHtmlResponse->content);
                // Strip any accidental markdown code fences
                $rawHtml = (string) preg_replace('/^```(?:html)?\s*/m', '', $rawHtml);
                $rawHtml = (string) preg_replace('/\s*```\s*$/m', '', $rawHtml);
                $homeHtml = $this->uniquifyEditorAttrs($this->sanitizeHomeHtml(trim($rawHtml)));
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
            'tokens' => $response->inputTokens + $response->outputTokens
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
            $isAiTheme = (bool) ($existingThemeJson['designer']['generated_by_ai'] ?? false);
            if (!$isAiTheme && !$hasManagedOverrides) {
                return $this->errorResponse("Theme exists and is not marked as AI-generated: {$slug}", 409);
            }
        }

        $baseTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($body['base_theme'] ?? $this->getActiveThemeSlug())));
        $baseTheme = $baseTheme !== '' ? $baseTheme : $this->getActiveThemeSlug();

        if ($themeExists && $overwrite) {
            $existingSourceTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($existingThemeJson['designer']['source_theme'] ?? $existingThemeJson['ai']['base_theme'] ?? '')));
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
            $sourceTheme = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($existingThemeJson['designer']['source_theme'] ?? $existingThemeJson['ai']['base_theme'] ?? $baseTheme)));
            if ($sourceTheme === '') {
                $sourceTheme = $baseTheme;
            }
        }
        $themeJson['designer'] = array_merge(
            is_array($themeJson['designer'] ?? null) ? $themeJson['designer'] : [],
            [
                'compatible' => true,
                'editable' => true,
                'source_theme' => $sourceTheme,
                'generated_by_ai' => true
            ]
        );
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
            'settings' => $settings,
            // Mark AI-generated themes as Designer-compatible so the visual
            // editor activates and treats the homepage as editable.
            'designer' => ['compatible' => true, 'editable' => true]
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
     * Make every data-editor-id and data-editor-field-name unique by suffixing
     * duplicates (_2, _3, ...). Duplicate names collide in the Designer's
     * save/load (it matches editables and blocks by these keys), so this keeps
     * AI output robust even if the model reuses a name.
     *
     * @param string $html
     * @return string
     */
    private function uniquifyEditorAttrs(string $html): string
    {
        foreach (['data-editor-id', 'data-editor-field-name'] as $attr) {
            $seen = [];
            $html = (string) preg_replace_callback(
                '/'.preg_quote($attr, '/').'="([^"]*)"/',
                static function (array $m) use (&$seen, $attr): string {
                    $val = $m[1];
                    if (!isset($seen[$val])) {
                        $seen[$val] = 1;
                        return $m[0];
                    }
                    $seen[$val]++;
                    return $attr.'="'.$val.'_'.$seen[$val].'"';
                },
                $html
            );
        }
        return $html;
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

        // Attributes allowed on every element
        /**
         * @var array
         */
        static $globalAttrs = [
            'class', 'id',
            'data-block-type', 'data-editor-container', 'data-editor-template',
            'data-gcms-custom', 'data-editor-id', 'data-editor-label',
            'data-editable', 'data-editor-field-name',
            'data-gcms-page', 'data-drop-zone',
            'data-repeat-container', 'data-repeat-item', 'data-component',
            'data-gcms-hidden', 'data-block-locked'
        ];

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

            $this->dedupeEditorIds($body);
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
     * Guarantee every data-editor-id is unique within the template.
     *
     * Designer keys blocks by data-editor-id; duplicates make it edit/move the wrong
     * block. Models occasionally repeat an id, so any collision is suffixed (-2, -3, …).
     */
    private function dedupeEditorIds(\DOMElement $body): void
    {
        $seen = [];
        foreach ($body->getElementsByTagName('*') as $el) {
            if (!$el->hasAttribute('data-editor-id')) {
                continue;
            }
            $id = trim($el->getAttribute('data-editor-id'));
            if ($id === '') {
                continue;
            }
            if (!isset($seen[$id])) {
                $seen[$id] = 1;
                continue;
            }
            do {
                $candidate = $id.'-'.(++$seen[$id]);
            } while (isset($seen[$candidate]));
            $seen[$candidate] = 1;
            $el->setAttribute('data-editor-id', $candidate);
        }
    }

    /**
     * Append a Designer-compatible {DETAIL} block when the homepage has none.
     *
     * Prefers the main content container (data-editor-container="content"); falls
     * back to the .homepage wrapper. $detailPlaceholder is the macro placeholder
     * that Step 4 restores back to the literal {DETAIL} token.
     */
    private function injectDetailBlock(\DOMDocument $doc, \DOMElement $body, string $detailPlaceholder): void
    {
        $target = null;
        foreach ($body->getElementsByTagName('*') as $el) {
            if ($el->getAttribute('data-editor-container') === 'content') {
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

        // Same shape as the bundled themes / Designer palette: the section's
        // slot (.section__body) holding the prose element (.page-detail).
        // home-cms is the type the Designer tracks for {DETAIL} restore.
        $section = $doc->createElement('section');
        $section->setAttribute('class', 'section-bg');
        $section->setAttribute('data-block-type', 'home-cms');
        $section->setAttribute('data-editor-id', 'block-detail');
        $section->setAttribute('data-editor-label', 'Page Content');
        $slot = $doc->createElement('div');
        $slot->setAttribute('class', 'section__body');
        $prose = $doc->createElement('div');
        $prose->setAttribute('class', 'page-detail');
        $prose->appendChild($doc->createTextNode($detailPlaceholder));
        $slot->appendChild($prose);
        $section->appendChild($slot);
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
