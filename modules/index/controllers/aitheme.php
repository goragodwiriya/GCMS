<?php
/**
 * @filesource modules/index/controllers/aitheme.php
 *
 * AI Theme Generator Controller
 *
 * Generates a GCMS theme from a natural-language brief using the configured AI
 * provider: design tokens (:root custom properties), Thai fonts and, optionally,
 * a homepage composed from the blocks this site actually has content for.
 *
 * Endpoints:
 *   POST /api/index/aitheme/generate  — generate 1-3 design variants (+ homepage layout), no disk write
 *   POST /api/index/aitheme/refine    — adjust one generated design with a follow-up instruction
 *   POST /api/index/aitheme/preview   — build the design into the admin's preview theme (DATA_FOLDER.themes/)
 *   POST /api/index/aitheme/save      — save a generated design as a theme in themes/<slug>/
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Aitheme;

use Gcms\Ai;
use Gcms\Api as ApiController;
use Index\Aithemecolor\Model as Aithemecolor;
use Index\Aithemefont\Model as Aithemefont;
use Index\Aithemelayout\Model as Aithemelayout;
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
    // System prompts
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * One design variant: theme name, palette, fonts and the :root token block
     */
    private const PALETTE_PROMPT = <<<'PROMPT'
You are a professional web theme designer for the GCMS CMS platform.
You restyle an existing GCMS theme by choosing its design tokens (CSS custom properties) and its fonts.
Return ONLY a valid JSON object — no prose, no markdown fences — with exactly these keys:
{
  "name": "<short human readable theme name>",
  "description": "<one sentence describing the look>",
  "colors": {
    "primary": "<hex>", "primary_hover": "<hex>", "secondary": "<hex>", "accent": "<hex>",
    "background": "<hex>", "surface": "<hex>", "text": "<hex>", "text_secondary": "<hex>", "border": "<hex>"
  },
  "fonts": { "heading": "<font from the list>", "body": "<font from the list>" },
  "css": ":root { --token: value; ... }"
}

"css" — ONE :root block of custom property declarations. It is appended after the base theme CSS, so the
theme's own rules stay in place and only the tokens change.
Declare only custom properties from the list in the request. No other selectors, no @media, @import,
@font-face or keyframes, no url(), no comments.
It MUST define all of these:
  --color-primary, --color-primary-hover, --color-primary-main,
  --color-primary-dark, --color-primary-light, --color-primary-rgb,
  --color-accent, --color-accent-hover,
  --color-surface-bg, --color-page-bg, --color-border,
  --color-text, --color-text-secondary, --color-text-muted,
  --home-radius, --board-radius,
  --home-shadow, --card-shadow,
  --footer-bg, --footer-text, --footer-heading, --footer-border,
  --header-bg, --header-border
Also define these so the header, menus and widget cards match the palette:
  --header-text, --color-primary-text,
  --topmenu-mobile-bg, --topmenu-mobile-text,
  --menu-highlight-bg, --menu-highlight-text,
  --menu-select-bg, --menu-select-text,
  --menu-button-border-color,
  --home-bg, --home-border-color
--color-primary-rgb is the primary color as three numbers "R, G, B".
--color-primary-text is the text color used on top of the primary color.
Every text color must stay readable on its background (WCAG AA, at least 4.5:1).

"fonts" — a heading font and a body font, ONLY from the font list in the request (all support Thai).
Match the mood of the brief; the body font must be comfortable to read. Using one font for both is fine.

"colors" — the same palette as plain hex values (shown on the theme card).
For a dark color scheme use dark backgrounds with light text; for a light scheme keep backgrounds light and text dark.
Match the personality described in the brief. Output raw JSON only.
PROMPT;

    /**
     * Homepage plan: blocks chosen from the site's catalog (the system renders the HTML)
     */
    private const LAYOUT_PROMPT = <<<'PROMPT'
You are a GCMS homepage layout planner.
You do NOT write HTML. You choose and order blocks from the catalog in the request; the system renders them
with the theme's own markup. Return ONLY a valid JSON object — no prose, no markdown fences:
{
  "position": "right|left|both|none",
  "top": [ ...blocks shown full width above the columns... ],
  "main": [ ...blocks of the wide main column... ],
  "sidebar": [ ...blocks of the sidebar (the right one when position is both)... ],
  "sidebar_left": [ ...blocks of the left sidebar, only when position is both... ]
}
A block is {"block": "<id from the catalog>"} plus options:
  layout     document/product blocks: highlight | thumb | list | icon | carousel
  rows, cols thumb/list/icon: rows 1-20, cols 1-4 (always cols 1 in a sidebar)
  limit      carousel and the other module blocks: number of items, 1-12
  title      optional heading text — omit it to keep the module's standard translated title
  map        contact block only: true shows the map beside the contact form
The hero block: {"block": "hero", "heading": "...", "text": "...", "button": "...", "link": "<module block id, e.g. document:news>"}

Rules:
  - "detail" (the homepage's own editable content) MUST appear exactly once, in "main".
  - "top" holds only "hero" and/or "textlinks:slideshow" — at most 2 blocks.
  - Use only ids from the catalog. Prefer blocks that have items; never use EMPTY ones.
  - Most important content first. Usually 4-8 blocks in main and 3-6 in a sidebar.
  - Narrow widgets (search, tags, counter, facebook, banner, short lists) go in a sidebar;
    contact, map and stats only in main.
  - position must be one of the allowed positions in the request.
  - Write hero texts and any titles in the language of the brief.
Output raw JSON only.
PROMPT;

    /**
     * Rank the bundled themes whose structure fits the brief
     */
    private const BASE_PROMPT = <<<'PROMPT'
You pick starting themes for a website design brief.
The themes' colors and fonts will be redesigned afterwards, so judge the column layout, the header/menu style and
the kind of site each theme was made for, not its current colors.
Return ONLY a JSON object listing the requested number of DIFFERENT themes, best fit first:
{"choices": [{"slug": "<slug from the list>", "reason": "<one short sentence in the language of the brief>"}]}
PROMPT;

    /**
     * Design directions given to the variants when more than one is requested,
     * so the designs differ in more than the shade of one palette.
     * [label shown in the admin, instruction for the AI]
     */
    private const DIRECTIONS = [
        ['Classic style', "Design direction: CLASSIC & CLEAN.
- Header light (white or a very light tint) with dark header text; footer a deep, dark shade of the primary.
- Small corner radius (--home-radius and --board-radius 4px-8px) and soft, subtle shadows.
- Accent: a darker or lighter shade near the primary hue.
- Fonts: one calm sans-serif for both heading and body (Sarabun, Noto Sans Thai, IBM Plex Sans Thai or Anuphan)."],
        ['Bold style', "Design direction: BOLD & VIBRANT.
- Header filled with the primary color (or a very deep shade of it) with white header text and menu; footer a different dark tone, not the header color.
- Saturated primary; the accent is a COMPLEMENTARY hue (150-180 degrees around the color wheel from the primary), used for buttons and highlights.
- Large corner radius (--home-radius and --board-radius 14px-20px) and pronounced shadows.
- Fonts: a strong display heading font (Kanit, Prompt, Mitr or Chakra Petch) with a readable body font (Sarabun, Anuphan or Noto Sans Thai)."],
        ['Soft style', "Design direction: SOFT & MINIMAL.
- Page background a very light tint of the primary (not pure white); cards white with thin 1px borders; little or no shadow.
- Header white or the same tint as the page; footer a pale tint with dark text — no heavy dark blocks.
- Muted, desaturated primary; the accent is an ANALOGOUS hue (30-60 degrees away), pastel rather than strong.
- Corners either square (0-2px) or very round (22px+); pick one and use it everywhere.
- Fonts: an elegant heading font (Noto Serif Thai, Taviraj, Trirong, Pridi or Athiti) with a clean body font (Sarabun, K2D or Krub)."]
    ];

    /**
     * Homepage directions, one per variant, so the variants also differ in arrangement
     */
    private const LAYOUT_DIRECTIONS = [
        'Homepage direction: NEWS-FIRST PORTAL. If there is a slideshow put it in "top"; open the main column with the busiest document module as highlight, then more news-style lists and grids.',
        'Homepage direction: HERO-LED LANDING PAGE. Open with a hero in "top" (heading, short intro, button to the most important module); then few, large blocks — wide thumb grids with cols 3-4 and carousels; contact with map near the end of main.',
        'Homepage direction: COMPACT INFORMATION HUB. No hero; many short one-column lists (layout list) so a lot is visible at once; utility widgets (search, counter, tags, banners) in the sidebar.'
    ];

    /**
     * Largest accepted hero image (bytes)
     */
    private const MAX_HERO_IMAGE = 4194304;

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/generate
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate design variants (each with its own homepage when asked) from a brief.
     * Does NOT write to disk — returns the designs for preview.
     *
     * With 2-3 variants every variant follows its own design direction (colors,
     * header/footer treatment, shapes, fonts) and homepage direction; with
     * base_theme "auto" the variants also start from different bundled themes.
     *
     * Request body (JSON):
     *   prompt            string  required  Natural-language design brief
     *   color_scheme      string  optional  "light"|"dark" (default "light")
     *   name              string  optional  Theme name hint
     *   base_theme        string  optional  theme slug, "auto" (AI picks) or "" (active theme)
     *   variants          int     optional  1-3 designs requested in parallel (default 1)
     *   use_logo_colors   bool    optional  build the palette around the site logo colors
     *   include_home_html bool    optional  also plan the homepage from the site's blocks
     *   sidebar_position  string  optional  right|left|both|none, "" = AI decides
     *
     * @param Request $request
     */
    public function generate(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $this->validateCsrfToken($request);
            if (empty(self::$cfg->ai_enabled)) {
                return $this->errorResponse('AI connector is disabled. Enable it in Settings → AI.', 503);
            }

            $body = $this->jsonBody($request);
            $prompt = Text::topic($body['prompt'] ?? '');
            if ($prompt === '') {
                return $this->errorResponse('prompt is required', 400);
            }
            $colorScheme = ($body['color_scheme'] ?? '') === 'dark' ? 'dark' : 'light';
            $nameHint = Text::topic($body['name'] ?? '');
            $variantCount = max(1, min(count(self::DIRECTIONS), (int) ($body['variants'] ?? 1)));
            $includeHome = $this->toBoolean($body['include_home_html'] ?? false);
            $position = strtolower(trim((string) ($body['sidebar_position'] ?? '')));

            // หลายคำขอไปยัง AI (ส่งพร้อมกัน) — reasoning model ใช้เวลาคำขอละหลายสิบวินาที
            @set_time_limit(300);
            $driver = Ai::driver();
            $tokens = 0;

            // ── ธีมต้นแบบของแต่ละแบบ: ธีมที่ใช้อยู่, ธีมที่เลือก หรือให้ AI จัดอันดับ (แบบละธีม) ──
            $baseChoice = strtolower(trim((string) ($body['base_theme'] ?? '')));
            if ($baseChoice === 'auto') {
                list($choices, $used) = $this->chooseBaseThemes($driver, $prompt, $variantCount);
                $tokens += $used;
            } else {
                $choices = [[$this->baseThemeSlug($baseChoice), '']];
            }
            $bases = [];
            $variantBase = [];
            for ($i = 0; $i < $variantCount; ++$i) {
                list($slug, $reason) = $choices[$i % count($choices)];
                $variantBase[$i] = $slug;
                if (!isset($bases[$slug])) {
                    $dir = ROOT_PATH."themes/{$slug}/";
                    $json = $this->loadThemeJson($slug);
                    $bases[$slug] = [
                        'slug' => $slug,
                        'label' => is_array($json) && !empty($json['name']) ? $json['name'] : $slug,
                        'reason' => $reason,
                        'json' => $json,
                        'css' => $this->loadThemeStylesheet($slug),
                        'home' => $this->loadThemeHomeHtml($slug),
                        'frame' => Aithemelayout::frame($dir),
                        'font' => Aithemefont::themeFont($dir),
                        'catalog' => $includeHome ? Aithemelayout::catalog($dir) : []
                    ];
                }
            }
            $logoColors = $this->toBoolean($body['use_logo_colors'] ?? false) ? Aithemecolor::logoPalette() : [];

            // ── ชุดสีของแต่ละแบบ + หน้าแรกของแต่ละแบบ ส่งไปพร้อมกันทั้งหมด ──
            $calls = [];
            for ($i = 0; $i < $variantCount; ++$i) {
                $base = $bases[$variantBase[$i]];
                $message = $this->paletteMessage($prompt, $colorScheme, $nameHint, $base['slug'], $base['label'], $base['css'], $logoColors);
                if ($variantCount > 1) {
                    $message .= self::DIRECTIONS[$i][1]."\nKeep the brand colors and anything the brief explicitly asks for; apply the direction to everything else."
                        .($colorScheme === 'dark' ? ' This is a dark color scheme: read light/dark in the direction the other way round (dark tinted backgrounds, light text).' : '')
                        ."\n";
                }
                $calls['palette'.$i] = $this->paletteCall($message."Return only the JSON object as specified.", $variantCount > 1 ? 0.9 : 0.8);
                if ($includeHome) {
                    $message = $this->layoutMessage($prompt, $base['catalog'], $base['frame'], $position, $base['home']);
                    if ($variantCount > 1) {
                        $message = str_replace('Return only the JSON object as specified.', self::LAYOUT_DIRECTIONS[$i]."\nReturn only the JSON object as specified.", $message);
                    }
                    $calls['layout'.$i] = [
                        'messages' => [['role' => 'user', 'content' => $message]],
                        'options' => [
                            'system' => self::LAYOUT_PROMPT,
                            'max_tokens' => 8192,
                            'temperature' => 0.7
                        ]
                    ];
                }
            }
            $result = $this->runCalls($driver, $calls);
            $tokens += $result['tokens'];

            $variants = [];
            $slugs = [];
            for ($i = 0; $i < $variantCount; ++$i) {
                if (!isset($result['parsed']['palette'.$i])) {
                    continue;
                }
                $base = $bases[$variantBase[$i]];

                // หน้าแรก: AI วางแผน ระบบประกอบ HTML (แผนใช้ไม่ได้ = แผนมาตรฐานจากคลังบล็อก)
                $homeHtml = null;
                $layout = null;
                if ($includeHome) {
                    $plan = $result['parsed']['layout'.$i] ?? null;
                    $source = 'ai';
                    if (!is_array($plan)) {
                        $plan = Aithemelayout::defaultPlan($base['catalog'], $base['frame']);
                        $source = 'default';
                    }
                    $composed = Aithemelayout::compose($plan, $base['catalog'], $base['frame'], $position);
                    $homeHtml = $this->sanitizeHomeHtml($composed['html']);
                    $layout = [
                        'position' => $composed['position'],
                        'blocks' => $composed['blocks'],
                        'source' => $source
                    ];
                }

                $variant = $this->buildVariant($result['parsed']['palette'.$i], $base['json'], $base['css'], $colorScheme, $nameHint, $homeHtml ?? $base['home'], $base['frame'], $layout);
                if ($variant === null) {
                    continue;
                }
                // ชื่อธีมที่ขึ้นต้นด้วยชื่อธีมต้นแบบอยู่แล้ว (MaxSite Navy) ไม่ต้องเติมซ้ำ
                $seed = $variant['theme_json']['name'];
                if (strpos(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($seed)), '-'), $base['slug']) !== 0) {
                    $seed = $base['slug'].' '.$seed;
                }
                $slug = $this->makeSlug($seed);
                $suffix = 2;
                $seed = $slug;
                while (in_array($slug, $slugs, true)) {
                    $slug = $this->makeSlug($seed.'-'.$suffix++);
                }
                $slugs[] = $slug;
                $variants[] = array_merge($variant, [
                    'slug' => $slug,
                    'direction' => $variantCount > 1 ? self::DIRECTIONS[$i][0] : '',
                    'base_theme' => $base['slug'],
                    'base_theme_label' => $base['label'],
                    'base_reason' => $base['reason'],
                    'base_font' => $base['font'],
                    'frame' => $base['frame'],
                    'positions' => Aithemelayout::positions($base['frame']),
                    'home_html' => $homeHtml,
                    'layout' => $layout
                ]);
            }

            if (empty($variants)) {
                return $this->aiFailure($result);
            }

            return $this->successResponse([
                'logo_colors' => $logoColors,
                'font_options' => Aithemefont::options(),
                'color_scheme' => $colorScheme,
                'variants' => $variants,
                'css_mode' => self::CSS_MODE,
                'model' => $result['model'],
                'tokens' => $tokens
            ], 'Theme generated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/refine
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Adjust one generated design with a follow-up instruction
     * ("darker header", "softer colors", "use Kanit for headings").
     *
     * Request body (JSON):
     *   instruction  string  required  What to change
     *   base_theme   string  required  Base theme slug of the design
     *   theme_json   object  required  The design's theme metadata
     *   css          string  required  The design's :root token block
     *   fonts        object  optional  {heading, body}
     *   home_html    string  optional  Generated homepage (for the mockup)
     *
     * @param Request $request
     */
    public function refine(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $this->validateCsrfToken($request);
            if (empty(self::$cfg->ai_enabled)) {
                return $this->errorResponse('AI connector is disabled. Enable it in Settings → AI.', 503);
            }

            $body = $this->jsonBody($request);
            $instruction = Text::topic($body['instruction'] ?? '');
            $css = is_string($body['css'] ?? null) ? $body['css'] : '';
            $themeJson = is_array($body['theme_json'] ?? null) ? $body['theme_json'] : [];
            if ($instruction === '' || $css === '') {
                return $this->errorResponse('instruction and css are required', 400);
            }
            @set_time_limit(180);
            $baseSlug = $this->baseThemeSlug($body['base_theme'] ?? '');
            $baseDir = ROOT_PATH."themes/{$baseSlug}/";
            $baseThemeJson = $this->loadThemeJson($baseSlug);
            $baseThemeLabel = is_array($baseThemeJson) && !empty($baseThemeJson['name']) ? $baseThemeJson['name'] : $baseSlug;
            $baseCss = $this->loadThemeStylesheet($baseSlug);
            $colorScheme = ($themeJson['settings']['color_scheme'] ?? '') === 'dark' ? 'dark' : 'light';
            $fonts = Aithemefont::normalize($body['fonts'] ?? null);
            $current = $this->renderCssRule([':root'], $this->sanitizedDeclarations($css, $baseCss));

            $message = $this->paletteMessage(
                Text::topic($themeJson['description'] ?? ''),
                $colorScheme,
                Text::topic($themeJson['name'] ?? ''),
                $baseSlug,
                $baseThemeLabel,
                $baseCss,
                []
            );
            $message .= "The design below already exists. Apply this change request to it: {$instruction}\n";
            $message .= 'Current fonts: '.($fonts === null ? 'theme default' : "heading {$fonts['heading']}, body {$fonts['body']}")."\n";
            $message .= "Current tokens:\n{$current}\n";
            $message .= "Keep everything the change request does not mention. Return the complete JSON object as specified, including every required variable.";

            $result = $this->runCalls(Ai::driver(), ['palette0' => $this->paletteCall($message, 0.6)]);
            $homeHtml = is_string($body['home_html'] ?? null) && $body['home_html'] !== ''
                ? $this->sanitizeHomeHtml($body['home_html'])
                : $this->loadThemeHomeHtml($baseSlug);
            $layout = isset($themeJson['settings']['sidebar_position']) ? ['position' => (string) $themeJson['settings']['sidebar_position']] : null;
            $variant = isset($result['parsed']['palette0'])
                ? $this->buildVariant($result['parsed']['palette0'], $baseThemeJson, $baseCss, $colorScheme, '', $homeHtml, Aithemelayout::frame($baseDir), $layout)
                : null;
            if ($variant === null) {
                return $this->aiFailure($result);
            }

            return $this->successResponse([
                'variant' => $variant,
                'model' => $result['model'],
                'tokens' => $result['tokens']
            ], 'Theme updated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/preview
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a design into the admin's preview theme (DATA_FOLDER.themes/ai-preview-<id>/)
     * and return the homepage URL that renders it. The live site keeps its theme;
     * only an administrator opening the URL sees the preview.
     *
     * Request body: the same fields as save() except slug/overwrite.
     *
     * @param Request $request
     */
    public function preview(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $this->validateCsrfToken($request);

            $body = $this->jsonBody($request);
            $baseTheme = $this->baseThemeSlug($body['base_theme'] ?? '');
            $baseCss = $this->loadThemeStylesheet($baseTheme);
            $design = $this->prepareDesign($body, $baseCss, $this->loadThemeJson($baseTheme));
            if (is_string($design)) {
                return $this->errorResponse($design, 400);
            }

            $slug = \Gcms\Theme::previewSlug((int) $login->id);
            $dir = \Gcms\Theme::previewDir($slug);
            $this->removeDirectory($dir);
            $this->writeThemeFiles($dir, ROOT_PATH."themes/{$baseTheme}/", true, $baseCss, $design);

            return $this->successResponse([
                'url' => WEB_URL.'index.php?theme_preview='.$slug.'&t='.time()
            ], 'Preview ready');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/index/aitheme/save
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Save a generated design as a theme.
     * Copies all template files from the base theme, then writes theme.json,
     * css/styles.css (base CSS + managed token block), fonts, home.html and
     * the optional hero image.
     *
     * Request body (JSON):
     *   slug        string  required  Directory name (a-z0-9-)
     *   base_theme  string  optional  Base theme captured at generate time
     *   theme_json  object  required  Theme metadata
     *   css         string  required  :root token block
     *   fonts       object  optional  {heading, body} from the font list
     *   home_html   string  optional  Generated homepage
     *   hero_image  object  optional  {data: base64, mime: image/jpeg|png|webp}
     *   prompt      string  optional  The brief (kept in theme.json)
     *   overwrite   bool    optional  true to update an existing AI theme in place
     *
     * @param Request $request
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $login = $this->authenticateRequest($request);
            if (!$login || $login->status != 1) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $this->validateCsrfToken($request);

            $body = $this->jsonBody($request);
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($body['slug'] ?? '')));
            if ($slug === '') {
                return $this->errorResponse('slug is required', 400);
            }
            if (in_array($slug, ['default', 'empty'], true) || \Gcms\Theme::isPreview($slug)) {
                return $this->errorResponse("Reserved theme name: {$slug}", 400);
            }
            $overwrite = $this->toBoolean($body['overwrite'] ?? false);

            $destDir = ROOT_PATH."themes/{$slug}/";
            $themeExists = is_dir($destDir);
            $existingThemeJson = $themeExists ? $this->loadThemeJson($slug) : null;
            $existingCss = $themeExists ? $this->loadThemeStylesheet($slug) : '';
            if ($themeExists && !$overwrite) {
                return $this->errorResponse("Theme already exists: {$slug}", 409);
            }
            if ($themeExists) {
                $hasManagedOverrides = preg_match('#/\* GCMS AI Theme Overrides: start \*/.*?/\* GCMS AI Theme Overrides: end \*/#s', $existingCss) === 1;
                // ธีมที่สร้างจาก AI จะมีคีย์ ai.generated_at ใน theme.json
                if (empty($existingThemeJson['ai']['generated_at']) && !$hasManagedOverrides) {
                    return $this->errorResponse("Theme exists and is not marked as AI-generated: {$slug}", 409);
                }
            }

            // ธีมเดิมที่สร้างจาก AI จำธีมต้นแบบไว้ — อัปเดตต่อจากต้นแบบเดิม
            $baseTheme = $this->baseThemeSlug($themeExists ? ($existingThemeJson['ai']['base_theme'] ?? $body['base_theme'] ?? '') : ($body['base_theme'] ?? ''));
            $baseCss = $this->loadThemeStylesheet($baseTheme);
            $sanitizeBaseCss = $themeExists && $existingCss !== '' ? $existingCss : $baseCss;
            $design = $this->prepareDesign($body, $sanitizeBaseCss, $themeExists && is_array($existingThemeJson) ? $existingThemeJson : $this->loadThemeJson($baseTheme));
            if (is_string($design)) {
                return $this->errorResponse($design, 400);
            }
            $design['theme_json']['ai'] = array_merge(
                is_array($existingThemeJson['ai'] ?? null) ? $existingThemeJson['ai'] : [],
                [
                    'base_theme' => $baseTheme,
                    'generated_at' => date('c'),
                    'generated_by' => (int) $login->id,
                    'home_html_generated' => $design['home_html'] !== null,
                    'fonts' => $design['fonts'],
                    'hero_image' => $design['hero'] !== null,
                    'prompt' => Text::topic($body['prompt'] ?? '')
                ]
            );

            $this->writeThemeFiles($destDir, ROOT_PATH."themes/{$baseTheme}/", !$themeExists, $sanitizeBaseCss, $design);
            // บันทึกแล้ว ธีมตัวอย่างของแอดมินคนนี้ไม่ต้องเก็บไว้
            $this->removeDirectory(\Gcms\Theme::previewDir(\Gcms\Theme::previewSlug((int) $login->id)));

            \Index\Log\Model::add(0, 'index', 'Index', $themeExists ? "AI Theme updated: {$slug}" : "AI Theme created: {$slug}", $login->id);

            return $this->successResponse([
                'slug' => $slug,
                'base_theme' => $baseTheme,
                'overwritten' => $themeExists,
                'home_html_saved' => $design['home_html'] !== null,
                'theme_url' => WEB_URL."themes/{$slug}/"
            ], $themeExists ? 'Theme updated' : 'Theme saved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Chat call (for runCalls) that asks for one design variant.
     *
     * @param string $message
     * @param float  $temperature
     *
     * @return array
     */
    private function paletteCall(string $message, float $temperature): array
    {
        return [
            'messages' => [['role' => 'user', 'content' => $message]],
            'options' => [
                'system' => self::PALETTE_PROMPT,
                // คำตอบจริงใช้ราว 1.5-2K tokens แต่ reasoning model (openrouter/auto มักเลือก)
                // นับ token ที่ใช้คิดรวมใน max_tokens ด้วย — 4096 จึงถูกตัดกลาง JSON เป็นบางครั้ง
                'max_tokens' => 8192,
                'temperature' => $temperature
            ]
        ];
    }

    /**
     * User message shared by generate() and refine(): brief, base theme, fonts and tokens.
     *
     * @param string $prompt
     * @param string $colorScheme
     * @param string $nameHint
     * @param string $baseSlug
     * @param string $baseLabel
     * @param string $baseCss
     * @param array  $logoColors
     *
     * @return string
     */
    private function paletteMessage(string $prompt, string $colorScheme, string $nameHint, string $baseSlug, string $baseLabel, string $baseCss, array $logoColors): string
    {
        $message = "Restyle the GCMS theme \"{$baseLabel}\" ({$baseSlug}).\n";
        $message .= "Brief: {$prompt}\n";
        $message .= "Color scheme: {$colorScheme}\n";
        if ($nameHint !== '') {
            $message .= "Theme name: {$nameHint}\n";
        }
        if (!empty($logoColors)) {
            $message .= 'Brand colors taken from the site logo — build the palette around them: '.implode(', ', $logoColors)."\n";
        }
        $fonts = [];
        foreach (Aithemefont::options() as $font) {
            $fonts[] = "{$font['name']} ({$font['style']})";
        }
        $message .= 'Fonts you may use: '.implode(', ', $fonts)."\n";
        $message .= 'CSS custom properties you may declare: '.implode(', ', $this->promptVariableNames($baseCss))."\n";
        $vars = $this->baseVariables($baseCss);
        $current = [];
        foreach (self::REQUIRED_CSS_VARS as $name) {
            $value = Aithemecolor::resolve($name, $vars);
            if ($value !== null && $value !== '') {
                $current[] = "{$name}: {$value}";
            }
        }
        if (!empty($current)) {
            $message .= 'Current values in the base theme: '.implode('; ', $current)."\n";
        }
        // สีของเมนู/แถบต่าง ๆ ที่ธีมต้นแบบกำหนดเอง — ถ้าไม่ตั้งค่าใหม่ สีของธีมเดิมจะค้างอยู่
        $own = [];
        foreach ($this->ownColorTokens($baseCss) as $name => $value) {
            if (!in_array($name, self::REQUIRED_CSS_VARS, true)) {
                $own[] = "{$name} (now {$value})";
            }
        }
        if (!empty($own)) {
            $message .= 'This base theme also colors its menus and bars with its own tokens. Declare EVERY one of them with a color from the new palette: '.implode(', ', $own)."\n";
        }

        return $message;
    }

    /**
     * User message for the homepage plan.
     *
     * @param string $prompt
     * @param array  $catalog
     * @param string $frame
     * @param string $position  user's choice ('' = AI decides)
     * @param string $baseHomeHtml
     *
     * @return string
     */
    private function layoutMessage(string $prompt, array $catalog, string $frame, string $position, string $baseHomeHtml): string
    {
        $positions = Aithemelayout::positions($frame);
        $message = "Plan the homepage of a GCMS website.\nBrief: {$prompt}\n";
        $message .= 'Allowed positions: '.implode(', ', $positions);
        if ($frame === 'framed') {
            $message .= ' (the page layout of this theme already shows a left sidebar with the site menu)';
        }
        $message .= "\n";
        if (in_array($position, $positions, true)) {
            $message .= "Use position: {$position}\n";
        }
        $message .= "Catalog of blocks:\n".Aithemelayout::describe($catalog)."\n";
        if (preg_match_all('/\{(?:WIDGET_[A-Z]+(?:\s[^{}]*)?|DETAIL)\}/', $baseHomeHtml, $macros)) {
            $message .= 'The current homepage shows, in order: '.implode(', ', array_unique($macros[0]))."\n";
        }
        $message .= 'Return only the JSON object as specified.';

        return $message;
    }

    /**
     * Ask the AI which bundled themes fit the brief, best first — one per variant,
     * so the variants also differ in header, menu and footer.
     *
     * @param \Gcms\Ai\Driver $driver
     * @param string          $prompt
     * @param int             $count  number of different themes wanted
     *
     * @return array [[[slug, reason], ...], tokens] — at least one choice (the active theme as fallback)
     */
    private function chooseBaseThemes($driver, string $prompt, int $count): array
    {
        $active = $this->getActiveThemeSlug();
        $themes = [];
        foreach (glob(ROOT_PATH.'themes/*/theme.json') ?: [] as $file) {
            $slug = basename(dirname($file));
            $json = $this->loadThemeJson($slug);
            // ธีมที่ AI สร้างเป็นรูปแบบของธีมอื่นอยู่แล้ว เลือกจากธีมต้นฉบับเท่านั้น
            if (!\Gcms\Theme::exists($slug) || !is_array($json) || !empty($json['ai']['generated_at'])) {
                continue;
            }
            $frame = Aithemelayout::frame(ROOT_PATH."themes/{$slug}/");
            $themes[$slug] = "- {$slug}: ".($json['name'] ?? $slug).' — '.($json['description'] ?? '')
                .' (homepage sidebar: '.($json['settings']['sidebar_position'] ?? 'right')
                .($frame === 'framed' ? ', every page has a left menu column' : '').')';
        }
        if (count($themes) < 2) {
            return [[[$active, '']], 0];
        }
        $count = max(1, min($count, count($themes)));
        $result = $this->runCalls($driver, ['base' => [
            'messages' => [['role' => 'user', 'content' => "Brief: {$prompt}\nNumber of themes to choose: {$count}\nThemes:\n".implode("\n", $themes)]],
            'options' => [
                'system' => self::BASE_PROMPT,
                'max_tokens' => 4096,
                'temperature' => 0.2
            ]
        ]]);
        $answer = $result['parsed']['base'] ?? [];
        // คำตอบแบบเดิม {"slug", "reason"} ก็รับ
        $list = is_array($answer['choices'] ?? null) ? $answer['choices'] : [$answer];
        $choices = [];
        foreach ($list as $choice) {
            $slug = is_array($choice) ? preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($choice['slug'] ?? ''))) : '';
            if (isset($themes[$slug]) && !isset($choices[$slug]) && count($choices) < $count) {
                $choices[$slug] = [$slug, Text::topic($choice['reason'] ?? '')];
            }
        }
        if (empty($choices)) {
            $choices[$active] = [$active, ''];
        }

        return [array_values($choices), $result['tokens']];
    }

    /**
     * Send chat calls in parallel and parse their JSON answers.
     * Broken or cut-off answers are mostly random, so failed calls are retried once.
     *
     * @param \Gcms\Ai\Driver $driver
     * @param array           $calls key => ['messages' => array, 'options' => array]
     *
     * @return array ['parsed' => key => array, 'tokens' => int, 'model' => string, 'error' => string, 'cut_off' => bool]
     */
    private function runCalls($driver, array $calls): array
    {
        $result = ['parsed' => [], 'tokens' => 0, 'model' => '', 'error' => '', 'cut_off' => false];
        $pending = $calls;
        for ($attempt = 0; $attempt < 2 && !empty($pending); ++$attempt) {
            $failed = [];
            // ส่งทุกคำขอพร้อมกัน (ชุดสี + หน้าแรกของทุกแบบ สูงสุด 6 คำขอ)
            foreach ($driver->chatBatch($pending, max(4, count($pending))) as $key => $response) {
                $result['tokens'] += (int) $response->inputTokens + (int) $response->outputTokens;
                if ($response->success && $result['model'] === '') {
                    $result['model'] = (string) $response->model;
                }
                $data = $response->success ? $this->parseJsonObject((string) $response->content) : null;
                if ($data !== null && strpos((string) $key, 'palette') === 0) {
                    $data = $this->normalizePalette($data);
                }
                if ($data !== null) {
                    $result['parsed'][$key] = $data;
                    continue;
                }
                $failed[$key] = $pending[$key];
                if (!$response->success) {
                    $result['error'] = (string) $response->error;
                } else {
                    $result['cut_off'] = $result['cut_off'] || $response->finishReason === 'length';
                    error_log("AI theme: unparseable {$key} response (model ".$response->model.', finish '.$response->finishReason
                        .', '.$response->outputTokens.' output tokens): '.mb_substr((string) $response->content, 0, 300));
                }
            }
            $pending = $failed;
        }

        return $result;
    }

    /**
     * Error response when no usable design came back.
     *
     * @param array $result from runCalls()
     */
    private function aiFailure(array $result)
    {
        if ($result['error'] !== '') {
            return $this->errorResponse('AI error: '.$result['error'], 502);
        }
        if ($result['cut_off']) {
            return $this->errorResponse('AI response was cut off before it finished. Try again or use a shorter prompt.', 422);
        }

        return $this->errorResponse('AI returned an invalid response. Try again or rephrase your prompt.', 422);
    }

    /**
     * Turn one parsed AI answer into a design variant: sanitized tokens with
     * contrast fixes, fonts from the whitelist, theme metadata and a mockup.
     *
     * @param array      $data          from normalizePalette()
     * @param array|null $baseThemeJson
     * @param string     $baseCss
     * @param string     $colorScheme
     * @param string     $nameHint
     * @param string     $homeHtml      homepage the variant will use (for the mockup)
     * @param string     $frame
     * @param array|null $layout        ['position' => ...] when the homepage was generated
     *
     * @return array|null null when required tokens are missing
     */
    private function buildVariant(array $data, ?array $baseThemeJson, string $baseCss, string $colorScheme, string $nameHint, string $homeHtml, string $frame, ?array $layout): ?array
    {
        $declarations = $this->sanitizedDeclarations($data['css'], $baseCss);
        if (!empty(array_diff(self::REQUIRED_CSS_VARS, array_keys($declarations)))) {
            error_log('AI theme: variant dropped, missing '.implode(', ', array_diff(self::REQUIRED_CSS_VARS, array_keys($declarations))));

            return null;
        }
        $declarations = $this->carryOverThemeColors($declarations, $baseCss, array_keys(Aithemecolor::rootVariables($data['css'])));
        $baseVars = $this->baseVariables($baseCss);
        $audit = Aithemecolor::audit($declarations, $baseVars);
        $declarations = $audit['declarations'];
        ksort($declarations);

        $themeJson = $this->normalizeGeneratedThemeJson($data, $baseThemeJson, $colorScheme, $nameHint);
        $vars = array_merge($baseVars, $declarations);
        $tokens = [
            'primary' => '--color-primary',
            'primary_hover' => '--color-primary-hover',
            'accent' => '--color-accent',
            'background' => '--color-page-bg',
            'surface' => '--color-surface-bg',
            'text' => '--color-text',
            'text_secondary' => '--color-text-secondary',
            'border' => '--color-border'
        ];
        // สีบนการ์ดธีมต้องตรงกับสีจริงที่หน้าเว็บใช้ (หลังปรับ contrast แล้ว)
        foreach ($tokens as $key => $name) {
            $color = Aithemecolor::resolveColor($name, $vars);
            if ($color !== null && $color[3] > 0) {
                $themeJson['colors'][$key] = Aithemecolor::toHex($color);
            }
        }
        $themeJson['settings']['color_scheme'] = $colorScheme;
        if (!empty($layout['position'])) {
            $themeJson['settings']['sidebar_position'] = $layout['position'];
            $themeJson['settings']['show_sidebar'] = $layout['position'] !== 'none';
        }
        $fonts = Aithemefont::normalize($data['fonts'] ?? null);

        return [
            'theme_json' => $themeJson,
            'css' => $this->renderCssRule([':root'], $declarations),
            'fonts' => $fonts,
            'contrast' => $audit['report'],
            'mockup' => $this->mockupSvg($vars, $homeHtml, $frame, $themeJson['name'], $fonts)
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Writing themes (preview + save)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate a design posted by the admin page.
     *
     * @param array      $body
     * @param string     $baseCss       CSS the token block is checked against
     * @param array|null $baseThemeJson theme.json the metadata is normalized against
     *
     * @return array|string design, or an error message
     */
    private function prepareDesign(array $body, string $baseCss, ?array $baseThemeJson)
    {
        $themeJson = $body['theme_json'] ?? null;
        $css = $body['css'] ?? null;
        if (!is_array($themeJson) || !is_string($css) || $css === '') {
            return 'theme_json and css are required';
        }
        $declarations = $this->sanitizedDeclarations($css, $baseCss);
        $missing = array_diff(self::REQUIRED_CSS_VARS, array_keys($declarations));
        if (!empty($missing)) {
            return 'CSS is missing required theme variables: '.implode(', ', $missing);
        }
        $settings = is_array($themeJson['settings'] ?? null) ? $themeJson['settings'] : [];
        $normalized = $this->normalizeGeneratedThemeJson($themeJson, $baseThemeJson, (string) ($settings['color_scheme'] ?? 'light'), '');
        $hero = $this->decodeHeroImage($body['hero_image'] ?? null);
        if (is_string($hero)) {
            return $hero;
        }

        return [
            'theme_json' => $normalized,
            'declarations' => $declarations,
            'fonts' => Aithemefont::normalize($body['fonts'] ?? null),
            'home_html' => is_string($body['home_html'] ?? null) && trim($body['home_html']) !== '' ? $this->sanitizeHomeHtml($body['home_html']) : null,
            'hero' => $hero
        ];
    }

    /**
     * Write a prepared design into a theme folder.
     *
     * @param string $destDir         theme folder (trailing slash)
     * @param string $srcDir          base theme folder (trailing slash)
     * @param bool   $copy            copy the base theme's files first (new theme / preview)
     * @param string $composeBaseCss  stylesheet the managed block is appended to
     * @param array  $design          from prepareDesign()
     */
    private function writeThemeFiles(string $destDir, string $srcDir, bool $copy, string $composeBaseCss, array $design): void
    {
        if ($copy) {
            $this->copyThemeFiles($srcDir, $destDir);
        }
        if (!is_dir($destDir.'css/')) {
            mkdir($destDir.'css/', 0755, true);
        }
        if ($design['home_html'] !== null) {
            file_put_contents($destDir.'home.html', $design['home_html']);
        }
        $homeHtml = is_file($destDir.'home.html') ? (string) file_get_contents($destDir.'home.html') : '';

        // รูป hero เดิมของธีมนี้ถูกแทนที่หรือลบตามที่ส่งมาทุกครั้ง
        foreach (glob($destDir.'images/ai-hero.*') ?: [] as $file) {
            @unlink($file);
        }
        $heroFile = '';
        if ($design['hero'] !== null) {
            if (!is_dir($destDir.'images/')) {
                mkdir($destDir.'images/', 0755, true);
            }
            $heroFile = 'ai-hero.'.$design['hero']['ext'];
            file_put_contents($destDir.'images/'.$heroFile, $design['hero']['data']);
        }

        $declarations = $design['declarations'];
        if ($design['fonts'] !== null) {
            $declarations = array_merge($declarations, Aithemefont::declarations($design['fonts']));
        }
        $managed = $this->renderCssRule([':root'], $declarations);
        // สีที่ธีมต้นแบบเขียนตายตัวใน rule (ไม่ผ่าน token) เปลี่ยนตามชุดสีใหม่ — เทียบจากไฟล์ของธีมต้นแบบเสมอ
        $sourceCss = is_file($srcDir.'css/styles.css') ? (string) file_get_contents($srcDir.'css/styles.css') : '';
        $recolored = $this->recolorRules($sourceCss, array_merge($this->baseVariables($sourceCss), $declarations));
        if ($recolored !== '') {
            $managed .= "\n".$recolored;
        }
        $extra = $this->extraRules($composeBaseCss, $homeHtml, $heroFile);
        if ($extra !== '') {
            $managed .= "\n".$extra;
        }
        file_put_contents($destDir.'css/styles.css', $this->composeThemeStylesheet($composeBaseCss, $managed));
        file_put_contents(
            $destDir.'theme.json',
            json_encode($design['theme_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // <link> ของ Google Fonts: ฟอนต์ที่เลือก หรือฟอนต์เดิมของธีมต้นแบบ (กรณีกลับไปใช้ค่าเดิม)
        $linkFonts = $design['fonts'] ?? Aithemefont::normalize(['body' => Aithemefont::themeFont($srcDir)]);
        if ($linkFonts !== null) {
            Aithemefont::applyToTemplates($destDir, $linkFonts);
        }

        $vars = array_merge($this->baseVariables($composeBaseCss), $declarations);
        file_put_contents($destDir.'screenshot.svg', $this->mockupSvg($vars, $homeHtml, Aithemelayout::frame($destDir), (string) ($design['theme_json']['name'] ?? ''), $design['fonts']));
    }

    /**
     * CSS rules the system adds after the token block:
     *  - a hero style for themes whose CSS has none (the generated homepage uses .hero)
     *  - the hero background image with a dark overlay so white text stays readable
     *
     * @param string $baseCss
     * @param string $homeHtml
     * @param string $heroFile file name in images/ ('' = none)
     *
     * @return string
     */
    private function extraRules(string $baseCss, string $homeHtml, string $heroFile): string
    {
        $rules = [];
        $hasHero = preg_match('/class="hero"/', $homeHtml) === 1;
        $baseCss = preg_replace('#/\* GCMS AI Theme Overrides: start \*/.*?/\* GCMS AI Theme Overrides: end \*/#s', '', $baseCss);
        if ($hasHero && !preg_match('/(?:^|[\s,}])\.hero\s*\{/', $baseCss)) {
            // ธีมแบบ framed วาง hero ในคอลัมน์ ซึ่ง .container ไม่มีระยะขอบ — ใส่ระยะด้านข้างและมุมโค้งเท่าการ์ด
            $rules[] = ".hero {\n  background-image: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary-main) 50%, var(--color-primary-dark) 100%);\n  color: var(--color-primary-text);\n  padding: var(--space-12) var(--space-6);\n  border-radius: var(--home-radius, 0);\n}";
        }
        if ($hasHero && $heroFile !== '') {
            $rules[] = ".hero {\n  background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url(\"../images/{$heroFile}?v=".time()."\");\n  background-size: cover;\n  background-position: center;\n  color: #ffffff;\n}\n.hero h1,\n.hero h2,\n.hero p {\n  color: inherit;\n}";
        }

        return implode("\n", $rules);
    }

    /**
     * Decode and verify an uploaded/generated hero image.
     *
     * @param mixed $image {data: base64, mime: string} or null
     *
     * @return array|string|null ['data' => binary, 'ext' => string], an error message, or null for none
     */
    private function decodeHeroImage($image)
    {
        if (!is_array($image) || empty($image['data']) || !is_string($image['data'])) {
            return null;
        }
        $data = preg_replace('#^data:[^,]*,#', '', $image['data']);
        if (strlen($data) > self::MAX_HERO_IMAGE * 1.4) {
            return 'Hero image is too large';
        }
        $binary = base64_decode($data, true);
        if ($binary === false || strlen($binary) > self::MAX_HERO_IMAGE) {
            return 'Hero image is too large';
        }
        $info = @getimagesizefromstring($binary);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($info === false || !isset($types[$info['mime']])) {
            return 'Hero image must be a JPEG, PNG or WebP picture';
        }

        return ['data' => $binary, 'ext' => $types[$info['mime']]];
    }

    /**
     * Delete a preview theme folder (only folders inside DATA_FOLDER.themes/ai-preview-*).
     *
     * @param string|null $dir
     */
    private function removeDirectory(?string $dir): void
    {
        if ($dir === null || !is_dir($dir) || strpos($dir, ROOT_PATH.DATA_FOLDER.'themes/ai-preview-') !== 0) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
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
     * @return array|null decoded object or null on failure
     */
    private function parseJsonObject(string $content): ?array
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

        return is_array($data) ? $data : null;
    }

    /**
     * One design variant from a parsed AI answer.
     * Also accepts the older answer shape that nested the metadata in "theme_json".
     *
     * @param array $data
     *
     * @return array|null ['name', 'description', 'colors', 'fonts', 'css'] or null when there is no CSS
     */
    private function normalizePalette(array $data): ?array
    {
        $meta = is_array($data['theme_json'] ?? null) ? array_merge($data['theme_json'], $data) : $data;
        if (!isset($meta['css']) || !is_string($meta['css']) || trim($meta['css']) === '') {
            return null;
        }

        return [
            'name' => is_string($meta['name'] ?? null) ? $meta['name'] : '',
            'description' => is_string($meta['description'] ?? null) ? $meta['description'] : '',
            'colors' => is_array($meta['colors'] ?? null) ? $meta['colors'] : [],
            'fonts' => is_array($meta['fonts'] ?? null) ? $meta['fonts'] : null,
            'css' => $meta['css']
        ];
    }

    /**
     * Base theme slug: an installed theme from themes/, else the active theme.
     *
     * @param mixed $choice
     */
    private function baseThemeSlug($choice): string
    {
        $choice = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $choice));

        return $choice !== '' && \Gcms\Theme::exists($choice) ? $choice : $this->getActiveThemeSlug();
    }

    /**
     * :root variables in effect before the managed token block:
     * Now.js core, themes/gcms.css, then the base theme stylesheet.
     *
     * @param string $baseCss
     */
    private function baseVariables(string $baseCss): array
    {
        return array_merge(Aithemecolor::coreVariables(), Aithemecolor::rootVariables($baseCss));
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
     * Restrict AI CSS to strict token overrides: a managed :root block only.
     *
     * We keep the original base :root untouched and append a second :root block
     * that overrides only allowed custom properties.
     */
    private function sanitizeGeneratedCss(string $css, string $baseCss): string
    {
        $declarations = $this->sanitizedDeclarations($css, $baseCss);

        return empty($declarations) ? '' : trim($this->renderCssRule([':root'], $declarations));
    }

    /**
     * The :root declarations sanitizeGeneratedCss() keeps (property => value, sorted).
     *
     * @param string $css
     * @param string $baseCss
     */
    private function sanitizedDeclarations(string $css, string $baseCss): array
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
        ksort($rootDeclarations);

        return $rootDeclarations;
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
        // token ของ Now.js และ themes/gcms.css (ส่วนหัว เมนู การ์ดเอกสาร ร้านค้า ช่องค้นหา)
        // ใช้ได้ทุกธีม แม้ styles.css ของธีมต้นแบบจะไม่ได้อ้างถึง
        $vars = array_merge(self::REQUIRED_CSS_VARS, array_filter(array_keys(Aithemecolor::coreVariables()), [$this, 'isTokenOnlyVariableName']));
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
        // token สีที่ธีมต้นแบบประกาศเอง (--topmenu-bg, --sidemenu-highlight-bg, --body-bg-color, ...)
        // ต้องเปลี่ยนตามชุดสีใหม่ได้ ไม่อย่างนั้นสีของธีมเดิมยังค้างอยู่
        foreach ($this->ownColorTokens($baseCss) as $varName => $value) {
            $vars[] = $varName;
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
        // ระยะเวลา/การเปลี่ยนผ่านไม่ใช่ส่วนของหน้าตาธีม
        if (preg_match('/-(?:transition|duration|timing)$/', $name)) {
            return false;
        }

        return preg_match('/^--(?:color-|home-|footer-|header-|board-|hero-|card-|nav-|section-|menu-|topmenu-|widget-|doc-|shop-|search-)/', $name) === 1
        || in_array($name, ['--shadow', '--border-radius'], true);
    }

    /**
     * Color tokens the base theme declares itself in :root with a literal color value.
     *
     * @param string $baseCss
     *
     * @return array name => value
     */
    private function ownColorTokens(string $baseCss): array
    {
        $tokens = [];
        foreach (Aithemecolor::rootVariables($baseCss) as $name => $value) {
            if (preg_match('/^--[a-z0-9\-_]+$/', $name) && stripos($value, 'var(') === false && Aithemecolor::parse($value) !== null) {
                $tokens[$name] = $value;
            }
        }

        return $tokens;
    }

    /**
     * Give the base theme's own color tokens that the AI left out a color of the
     * new palette (Aithemecolor::recolor()), so no color of the old theme stays behind
     * — e.g. School Red's red --topmenu-bg under a navy design.
     *
     * @param array  $declarations sanitized :root declarations (AI + base)
     * @param string $baseCss
     * @param array  $aiNames      tokens the AI declared itself
     *
     * @return array
     */
    private function carryOverThemeColors(array $declarations, string $baseCss, array $aiNames): array
    {
        $baseVars = $this->baseVariables($baseCss);
        $anchors = Aithemecolor::anchors($baseVars, array_merge($baseVars, $declarations));
        foreach ($this->ownColorTokens($baseCss) as $name => $value) {
            if (in_array($name, $aiNames, true)) {
                continue;
            }
            $color = Aithemecolor::parse($value);
            $new = Aithemecolor::recolor($color, $anchors);
            if ($new !== $color) {
                $declarations[$name] = Aithemecolor::format($new);
            }
        }

        return $declarations;
    }

    /**
     * Rules of the base theme that set a literal (non-token) brand color, recolored
     * for the new palette. Written after the token block with the same selectors,
     * so they win without editing the base theme CSS.
     *
     * @param string $baseCss  the base theme stylesheet
     * @param array  $newVars  :root variables of the new design
     *
     * @return string
     */
    private function recolorRules(string $baseCss, array $newVars): string
    {
        $anchors = Aithemecolor::anchors($this->baseVariables($baseCss), $newVars);
        $css = preg_replace('#/\* GCMS AI Theme Overrides: start \*/.*?/\* GCMS AI Theme Overrides: end \*/#s', '', $baseCss);
        $css = $this->stripCssAtRules((string) preg_replace('#/\*.*?\*/#s', '', (string) $css));
        $rules = [];
        if (empty($anchors) || !preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $blocks, PREG_SET_ORDER)) {
            return '';
        }
        foreach ($blocks as $block) {
            $selector = trim((string) preg_replace('/\s+/', ' ', $block[1]));
            if ($selector === '' || preg_match('/(^|,)\s*:root\s*(,|$)/', $selector)) {
                continue;
            }
            $declarations = [];
            foreach ($this->parseCssDeclarations($block[2]) as list($property, $value)) {
                if (strpos($property, '--') === 0 || !preg_match('/#[0-9a-f]{3,8}\b|(?:rgba?|hsla?)\(/i', $value)) {
                    continue;
                }
                $new = Aithemecolor::recolorValue($value, $anchors);
                if ($new !== $value) {
                    $declarations[$property] = $new;
                }
            }
            if (!empty($declarations)) {
                $rules[] = $this->renderCssRule([$selector], $declarations);
            }
        }

        return implode("\n", $rules);
    }

    /**
     * Custom properties listed in the prompt: the required and supplementary tokens,
     * the base theme's own tokens and the component tokens of themes/gcms.css.
     * (allowedThemeVariableNames() also accepts the Now.js palette, which the prompt leaves out.)
     *
     * @param string $baseCss
     */
    private function promptVariableNames(string $baseCss): array
    {
        $names = array_merge(self::REQUIRED_CSS_VARS, [
            '--header-text', '--color-primary-text',
            '--topmenu-mobile-bg', '--topmenu-mobile-text',
            '--menu-highlight-bg', '--menu-highlight-text',
            '--menu-select-bg', '--menu-select-text',
            '--menu-button-border-color', '--home-bg', '--home-border-color'
        ]);
        $sources = [$baseCss];
        if (is_file(ROOT_PATH.'themes/gcms.css')) {
            $sources[] = implode(":\n", array_keys(Aithemecolor::rootVariables(file_get_contents(ROOT_PATH.'themes/gcms.css')))).':';
        }
        foreach ($sources as $css) {
            if (preg_match_all('/(--[a-z0-9\-_]+)\s*:/i', $css, $matches)) {
                foreach ($matches[1] as $name) {
                    if ($this->isTokenOnlyVariableName($name)) {
                        $names[] = strtolower($name);
                    }
                }
            }
        }
        $names = array_values(array_unique($names));
        sort($names);

        return $names;
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
     * Mockup of the theme as SVG (used for screenshot.svg and the variant cards):
     * header, hero/slideshow, the homepage's real column layout and block count,
     * and the footer — drawn with the theme's resolved token colors.
     *
     * @param array      $vars     effective :root variables (base + design tokens)
     * @param string     $homeHtml the homepage template
     * @param string     $frame    open|framed
     * @param string     $name     theme name
     * @param array|null $fonts    {heading, body}
     *
     * @return string
     */
    private function mockupSvg(array $vars, string $homeHtml, string $frame, string $name, ?array $fonts): string
    {
        $color = function ($names, $fallback, $over = '#ffffff') use ($vars) {
            foreach ((array) $names as $token) {
                $rgb = Aithemecolor::resolveColor($token, $vars);
                if ($rgb !== null && $rgb[3] > 0) {
                    return Aithemecolor::toHex(Aithemecolor::flatten($rgb, Aithemecolor::parse($over) ?: [255, 255, 255, 1.0]));
                }
            }

            return $fallback;
        };
        $page = $color('--color-page-bg', '#ffffff');
        $c = [
            'page' => $page,
            'header' => $color('--header-bg', $page, $page),
            'headerText' => $color(['--header-text', '--color-text'], '#1e293b', $page),
            'primary' => $color('--color-primary', '#2563eb', $page),
            'primaryText' => $color('--color-primary-text', '#ffffff', $page),
            'accent' => $color('--color-accent', '#10b981', $page),
            'card' => $color(['--home-bg', '--color-surface-bg'], '#f8fafc', $page),
            'border' => $color(['--home-border-color', '--color-border'], '#e2e8f0', $page),
            'text' => $color('--color-text', '#1e293b', $page),
            'footer' => $color('--footer-bg', '#1e293b', $page),
            'footerText' => $color(['--footer-text', '--color-text'], '#ffffff', $page)
        ];
        // มุมโค้งของการ์ดตาม --home-radius ของแบบนี้ (ภาพเล็กลง ~1/3 จึงย่อค่าลงและจำกัดไม่เกิน 14)
        $radius = (float) preg_replace('/[^0-9.]/', '', (string) Aithemecolor::resolve('--home-radius', $vars));
        $rx = (string) max(0, min(14, round($radius * 0.6)));
        $c = array_map(function ($value) {
            return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES);
        }, $c);

        // โครงหน้าแรกจริง: hero/slideshow, คอลัมน์ และจำนวนบล็อกในแต่ละคอลัมน์
        $hasHero = preg_match('/class="hero"/', $homeHtml) === 1;
        $hasSlideshow = strpos($homeHtml, 'name=slideshow') !== false;
        $mainCount = 3;
        $asides = [];
        if ($homeHtml !== '') {
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$homeHtml.'</body></html>');
            libxml_clear_errors();
            $xpath = new \DOMXPath($doc);
            $content = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' home-content ')]")->item(0);
            if ($content !== null) {
                $mainCount = $xpath->query(".//section[not(contains(@class, 'hero')) and not(contains(@class, 'slideshow-block'))]", $content)->length;
            }
            foreach ($xpath->query('//aside') as $aside) {
                $asides[] = [
                    'left' => $content !== null && $this->nodePrecedes($aside, $content),
                    'count' => $xpath->query('.//section', $aside)->length
                ];
            }
        }
        $width = 800;
        $margin = 24;
        $gap = 16;
        $top = 64;
        $svg = [];
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="800" height="500">';
        $svg[] = '  <rect width="800" height="500" fill="'.$c['page'].'"/>';
        // Header
        $svg[] = '  <rect width="800" height="64" fill="'.$c['header'].'"/>';
        $svg[] = '  <rect x="24" y="16" width="32" height="32" rx="8" fill="'.$c['primary'].'"/>';
        $svg[] = '  <rect x="66" y="22" width="130" height="10" rx="3" fill="'.$c['headerText'].'" opacity="0.85"/>';
        $svg[] = '  <rect x="66" y="38" width="90" height="6" rx="3" fill="'.$c['headerText'].'" opacity="0.4"/>';
        for ($i = 0; $i < 5; ++$i) {
            $svg[] = '  <rect x="'.(470 + $i * 62).'" y="27" width="46" height="9" rx="3" fill="'.($i === 0 ? $c['primary'] : $c['headerText']).'" opacity="'.($i === 0 ? '1' : '0.55').'"/>';
        }
        $svg[] = '  <rect y="63" width="800" height="1" fill="'.$c['border'].'"/>';
        // Hero / slideshow — เต็มความกว้างในธีมแบบ open, อยู่บนสุดของคอลัมน์หลักในธีมแบบ framed
        $band = function ($x, $y, $w, $h) use ($c, $hasHero, $rx) {
            $out = [];
            if ($hasHero) {
                $out[] = '  <defs><linearGradient id="hero" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="'.$c['primary'].'" stop-opacity="0.75"/><stop offset="1" stop-color="'.$c['primary'].'"/></linearGradient></defs>';
                $out[] = '  <rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="url(#hero)"/>';
                $out[] = '  <rect x="'.round($x + $w * 0.2).'" y="'.round($y + $h * 0.25).'" width="'.round($w * 0.6).'" height="14" rx="4" fill="'.$c['primaryText'].'" opacity="0.95"/>';
                $out[] = '  <rect x="'.round($x + $w * 0.3).'" y="'.round($y + $h * 0.25 + 24).'" width="'.round($w * 0.4).'" height="7" rx="3" fill="'.$c['primaryText'].'" opacity="0.6"/>';
                $out[] = '  <rect x="'.round($x + $w / 2 - 50).'" y="'.round($y + $h * 0.25 + 42).'" width="100" height="20" rx="6" fill="'.$c['accent'].'"/>';
            } else {
                $out[] = '  <rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="'.$rx.'" fill="'.$c['card'].'" stroke="'.$c['border'].'"/>';
                $out[] = '  <polygon points="'.$x.','.($y + $h).' '.round($x + $w * 0.22).','.round($y + $h * 0.35).' '.round($x + $w * 0.4).','.round($y + $h * 0.8).' '.round($x + $w * 0.55).','.round($y + $h * 0.2).' '.round($x + $w * 0.78).','.round($y + $h * 0.85).' '.($x + $w).','.round($y + $h * 0.4).' '.($x + $w).','.($y + $h).'" fill="'.$c['primary'].'" opacity="0.35"/>';
            }

            return $out;
        };
        $hasBand = $hasHero || $hasSlideshow;
        if ($hasBand && $frame === 'open') {
            if ($hasHero) {
                $svg = array_merge($svg, $band(0, 64, 800, 120));
                $top += 120;
            } else {
                $svg = array_merge($svg, $band(24, 80, 752, 110));
                $top += 126;
            }
        }
        // Columns
        $bottom = 428;
        $y = $top + 16;
        $side = 170;
        $columns = [];
        $x = $margin;
        $mainWidth = $width - 2 * $margin;
        if ($frame === 'framed') {
            $columns[] = ['x' => $x, 'w' => $side, 'menu' => true, 'count' => 4];
            $x += $side + $gap;
            $mainWidth -= $side + $gap;
        }
        foreach ($asides as $aside) {
            if ($aside['left'] && $frame !== 'framed') {
                $columns[] = ['x' => $x, 'w' => $side, 'menu' => false, 'count' => $aside['count']];
                $x += $side + $gap;
                $mainWidth -= $side + $gap;
            }
        }
        $rights = array_values(array_filter($asides, function ($aside) use ($frame) {
            return !$aside['left'] || $frame === 'framed';
        }));
        $mainWidth -= count($rights) * ($side + $gap);
        $columns[] = ['x' => $x, 'w' => $mainWidth, 'menu' => false, 'count' => max(1, $mainCount), 'main' => true];
        $x += $mainWidth + $gap;
        foreach ($rights as $aside) {
            $columns[] = ['x' => $x, 'w' => $side, 'menu' => false, 'count' => $aside['count']];
            $x += $side + $gap;
        }
        foreach ($columns as $column) {
            $start = $y;
            if ($hasBand && $frame === 'framed' && !empty($column['main'])) {
                $svg = array_merge($svg, $band($column['x'], $start, $column['w'], 100));
                $start += 112;
            }
            $count = max(1, min(empty($column['main']) ? 5 : ($start > $y ? 3 : 4), $column['count']));
            $height = ($bottom - $start - ($count - 1) * 12) / $count;
            for ($i = 0; $i < $count; ++$i) {
                $cy = round($start + $i * ($height + 12));
                $svg[] = '  <rect x="'.$column['x'].'" y="'.$cy.'" width="'.$column['w'].'" height="'.round($height).'" rx="'.$rx.'" fill="'.$c['card'].'" stroke="'.$c['border'].'"/>';
                if ($column['menu']) {
                    $svg[] = '  <rect x="'.$column['x'].'" y="'.$cy.'" width="4" height="'.round($height).'" fill="'.$c['primary'].'"/>';
                }
                $svg[] = '  <rect x="'.($column['x'] + 12).'" y="'.($cy + 10).'" width="'.round($column['w'] * 0.45).'" height="8" rx="3" fill="'.$c['primary'].'" opacity="0.85"/>';
                if ($height > 34) {
                    $svg[] = '  <rect x="'.($column['x'] + 12).'" y="'.($cy + 26).'" width="'.round($column['w'] * 0.75).'" height="5" rx="2" fill="'.$c['text'].'" opacity="0.25"/>';
                }
                if ($height > 48) {
                    $svg[] = '  <rect x="'.($column['x'] + 12).'" y="'.($cy + 37).'" width="'.round($column['w'] * 0.6).'" height="5" rx="2" fill="'.$c['text'].'" opacity="0.18"/>';
                }
            }
        }
        // Footer
        $svg[] = '  <rect y="440" width="800" height="60" fill="'.$c['footer'].'"/>';
        $svg[] = '  <rect x="24" y="456" width="120" height="7" rx="3" fill="'.$c['footerText'].'" opacity="0.8"/>';
        $svg[] = '  <rect x="24" y="472" width="200" height="5" rx="2" fill="'.$c['footerText'].'" opacity="0.45"/>';
        $label = htmlspecialchars(trim($name.($fonts === null ? '' : '  ·  '.$fonts['heading'].($fonts['body'] !== $fonts['heading'] ? ' / '.$fonts['body'] : ''))), ENT_XML1 | ENT_QUOTES);
        $svg[] = '  <text x="776" y="476" text-anchor="end" font-family="sans-serif" font-size="13" fill="'.$c['footerText'].'" opacity="0.9">'.$label.'</text>';
        $svg[] = '  <rect y="496" width="800" height="4" fill="'.$c['accent'].'"/>';
        $svg[] = '</svg>';

        return implode("\n", $svg)."\n";
    }

    /**
     * Does $node come before $other in the document?
     *
     * @param \DOMNode $node
     * @param \DOMNode $other
     */
    private function nodePrecedes(\DOMNode $node, \DOMNode $other): bool
    {
        $xpath = new \DOMXPath($node->ownerDocument);
        foreach ($xpath->query('following::*', $node) as $following) {
            if ($following->isSameNode($other)) {
                return true;
            }
        }

        return false;
    }
}
