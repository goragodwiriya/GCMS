<?php

use Index\Aithemecolor\Model as Color;
use Index\Aithemefont\Model as Font;
use Index\Aithemelayout\Model as Layout;
use PHPUnit\Framework\TestCase;

/**
 * AI Theme Generator: sanitizers (security), contrast fixing, fonts and the
 * homepage composer. No database and no AI calls — the controller's private
 * helpers are reached through reflection, the models are plain static classes.
 */
class AiThemeTest extends TestCase
{
    /** @var object */
    private static $controller;
    /** @var ReflectionClass */
    private static $rc;

    public static function setUpBeforeClass(): void
    {
        self::$rc = new ReflectionClass('Index\\Aitheme\\Controller');
        self::$controller = self::$rc->newInstanceWithoutConstructor();
    }

    private function call(string $method, array $args)
    {
        $m = self::$rc->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke(self::$controller, ...$args);
    }

    private function html(string $fragment): string
    {
        return $this->call('sanitizeHomeHtml', [$fragment]);
    }

    // ── home.html sanitizer ─────────────────────────────────────────────────

    public function testStripsScriptIframeStyleAndComments(): void
    {
        $out = $this->html('<div class="homepage"><script>alert(1)</script><iframe src="x"></iframe><style>a{}</style><!-- note --><p>ok</p></div>');
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('<iframe', $out);
        $this->assertStringNotContainsString('<style', $out);
        $this->assertStringNotContainsString('<!--', $out);
        $this->assertStringContainsString('<p>ok</p>', $out);
    }

    public function testStripsEventHandlersInlineStyleAndDataAttributes(): void
    {
        $out = $this->html('<div class="homepage"><a href="javascript:alert(1)" onclick="x()" style="color:red" data-x="1">t</a></div>');
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('style=', $out);
        $this->assertStringNotContainsString('data-x', $out);
    }

    public function testStripsPhpProcessingInstruction(): void
    {
        $out = $this->html('<div class="homepage"><?php echo 1; ?><p>a</p></div>');
        $this->assertStringNotContainsString('<?php', $out);
        $this->assertStringNotContainsString('echo 1', $out);
    }

    public function testPreservesWidgetMacrosAndAddsWrapperAndDetail(): void
    {
        $out = $this->html('<section class="section-bg"><div class="section__body">{WIDGET_DOCUMENT module=news;layout=list;rows=5;cols=1}</div></section>');
        $this->assertStringContainsString('{WIDGET_DOCUMENT module=news;layout=list;rows=5;cols=1}', $out);
        $this->assertStringStartsWith('<div class="homepage">', $out);
        $this->assertSame(1, substr_count($out, '{DETAIL}'));
    }

    // ── :root token sanitizer ───────────────────────────────────────────────

    public function testCssKeepsOnlyAllowedRootTokens(): void
    {
        $ai = ":root{--color-primary:#123456;--header-text:#ffffff;--shop-price:#c00;--evil:url(http://x/y.png);--color-accent:url(x)}\nbody{color:red}\n@import url(x.css);\n@media (max-width:1px){:root{--color-text:#000}}";
        $css = $this->call('sanitizeGeneratedCss', [$ai, ':root{--color-text:#111}']);
        $this->assertStringContainsString('--color-primary: #123456;', $css);
        // gcms.css component tokens are allowed even when the base theme does not use them
        $this->assertStringContainsString('--header-text: #ffffff;', $css);
        $this->assertStringContainsString('--shop-price: #c00;', $css);
        $this->assertStringNotContainsString('--evil', $css);
        $this->assertStringNotContainsString('url(', $css);
        $this->assertStringNotContainsString('body', $css);
        $this->assertStringNotContainsString('@', $css);
        $this->assertStringContainsString('--color-text: #111;', $css);
    }

    public function testFontAndTransitionTokensAreNotAcceptedFromAi(): void
    {
        $css = $this->call('sanitizeGeneratedCss', [':root{--font-family-base:Comic Sans;--board-transition:all 9s;--color-primary:#111}', '']);
        $this->assertStringNotContainsString('--font-family-base', $css);
        $this->assertStringNotContainsString('--board-transition', $css);
    }

    // ── AI answer parsing ───────────────────────────────────────────────────

    public function testParsesFencedJsonWithTrailingComma(): void
    {
        $data = $this->call('parseJsonObject', ["```json\n{\"name\": \"A\", \"css\": \":root {\n--x: 1;\n}\",}\n```"]);
        $this->assertSame('A', $data['name']);
    }

    public function testNormalizePaletteAcceptsOldAndNewShapes(): void
    {
        $new = $this->call('normalizePalette', [['name' => 'N', 'colors' => ['primary' => '#111'], 'fonts' => ['heading' => 'Kanit'], 'css' => ':root{}']]);
        $this->assertSame('N', $new['name']);
        $this->assertSame(['heading' => 'Kanit'], $new['fonts']);
        $old = $this->call('normalizePalette', [['theme_json' => ['name' => 'O', 'colors' => ['primary' => '#222']], 'css' => ':root{}']]);
        $this->assertSame('O', $old['name']);
        $this->assertSame('#222', $old['colors']['primary']);
        $this->assertNull($this->call('normalizePalette', [['name' => 'x']]));
    }

    // ── colors & contrast ───────────────────────────────────────────────────

    public function testParsesCssColorFormats(): void
    {
        $this->assertSame([255, 0, 0, 1.0], Color::parse('#f00'));
        $this->assertSame([37, 99, 235, 0.5], Color::parse('rgb(37 99 235 / 50%)'));
        $this->assertSame([51, 102, 153, 1.0], Color::parse('hsl(210, 50%, 40%)'));
        $this->assertNull(Color::parse('linear-gradient(red, blue)'));
        $this->assertEqualsWithDelta(21.0, Color::contrast([0, 0, 0], [255, 255, 255]), 0.01);
    }

    public function testResolvesVarChainsAndFallbacks(): void
    {
        $vars = ['--a' => 'var(--b)', '--b' => 'var(--missing, #abcdef)'];
        $this->assertSame('#abcdef', Color::resolve('--a', $vars));
    }

    public function testRootVariablesIgnoreMediaBlocks(): void
    {
        $vars = Color::rootVariables(':root{--x:#fff}@media (prefers-color-scheme: dark){:root{--x:#000}}html,:root{--y:1px}');
        $this->assertSame('#fff', $vars['--x']);
        $this->assertSame('1px', $vars['--y']);
    }

    public function testAuditFixesUnreadableTextAndWarnsOnBrandColors(): void
    {
        $result = Color::audit([
            '--color-page-bg' => '#ffffff',
            '--color-text' => '#bbbbbb',
            '--header-bg' => '#0f172a',
            '--header-text' => 'var(--color-text)',
            '--color-primary' => '#ffeb3b',
            '--color-white' => '#ffffff'
        ], []);
        $byLabel = [];
        foreach ($result['report'] as $item) {
            $byLabel[$item['label']] = $item;
        }
        $this->assertSame('fixed', $byLabel['Body text']['status']);
        $this->assertGreaterThanOrEqual(4.5, $byLabel['Body text']['ratio']);
        $this->assertSame($byLabel['Body text']['to'], $result['declarations']['--color-text']);
        // the dark header got its own light text instead of inheriting the darkened body text
        $this->assertGreaterThanOrEqual(4.5, Color::contrast(Color::parse($result['declarations']['--header-text']), Color::parse('#0f172a')));
        // brand colors are reported, never changed
        $this->assertSame('warn', $byLabel['Button label']['status']);
        $this->assertSame('#ffeb3b', $result['declarations']['--color-primary']);
    }

    // ── carrying the base theme's own colors over to the new palette ────────

    private function redToNavyAnchors(): array
    {
        return Color::anchors(
            ['--color-primary' => '#EC0007', '--footer-bg' => '#B71C1C'],
            ['--color-primary' => '#1a3a5c', '--footer-bg' => '#e8eef4']
        );
    }

    public function testRecolorFollowsTheBrandColorAndKeepsNeutrals(): void
    {
        $anchors = $this->redToNavyAnchors();
        // a darker red that is also close to the (red) footer still follows the primary — and stays darker
        $highlight = Color::recolor(Color::parse('#C20006'), $anchors);
        list($h, , $l) = Color::rgbToHsl($highlight);
        $this->assertEqualsWithDelta(211, $h, 10);
        $this->assertLessThan(Color::rgbToHsl(Color::parse('#1a3a5c'))[2], $l);
        // white, black and translucent greys are left alone
        $this->assertSame(Color::parse('#ffffff'), Color::recolor(Color::parse('#ffffff'), $anchors));
        $this->assertSame('rgba(0, 0, 0, 0.2)', Color::recolorValue('rgba(0, 0, 0, 0.2)', $anchors));
        $this->assertSame('transparent #11253b transparent', Color::recolorValue('transparent #C20006 transparent', $anchors));
    }

    public function testThemeOwnTokensTheAiSkippedGetTheNewPalette(): void
    {
        $baseCss = ':root{--color-primary:#EC0007;--topmenu-bg:#EC0007;--topmenu-text-color:#EEEEEE;--sidemenu-select-bg:#D1070E}';
        $out = $this->call('carryOverThemeColors', [['--color-primary' => '#1a3a5c', '--sidemenu-select-bg' => '#123456'], $baseCss, ['--color-primary', '--sidemenu-select-bg']]);
        $this->assertSame('#1a3a5c', $out['--topmenu-bg']);
        // neutral text color unchanged, AI's own value kept
        $this->assertArrayNotHasKey('--topmenu-text-color', $out);
        $this->assertSame('#123456', $out['--sidemenu-select-bg']);
        // and those theme tokens pass the sanitizer at save time
        $css = $this->call('sanitizeGeneratedCss', [':root{--sidemenu-select-bg:#123456;--body-bg-color:#fff}', $baseCss.':root{--body-bg-color:#fafafa}']);
        $this->assertStringContainsString('--sidemenu-select-bg: #123456;', $css);
        $this->assertStringContainsString('--body-bg-color: #fff;', $css);
    }

    public function testRulesWithLiteralBrandColorsAreOverridden(): void
    {
        $baseCss = ":root{--color-primary:#EC0007}\n.sidebar h2:before{border-color:transparent #C20006 transparent transparent}\n.logo{background-color:#FFFFFF}\n@media (max-width:1px){.x{color:#EC0007}}";
        $rules = $this->call('recolorRules', [$baseCss, ['--color-primary' => '#1a3a5c']]);
        $this->assertStringContainsString('.sidebar h2:before', $rules);
        $this->assertStringNotContainsString('#C20006', $rules);
        $this->assertStringNotContainsString('.logo', $rules);
        $this->assertStringNotContainsString('.x', $rules);
    }

    // ── fonts ───────────────────────────────────────────────────────────────

    public function testFontsAreLimitedToTheWhitelist(): void
    {
        $this->assertNull(Font::normalize(['heading' => 'Comic Sans', 'body' => 'Arial']));
        $this->assertSame(['heading' => 'Kanit', 'body' => 'Kanit'], Font::normalize(['heading' => 'kanit']));
        $fonts = Font::normalize(['heading' => 'Kanit', 'body' => 'Sarabun']);
        $this->assertSame(
            'https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600;700&display=swap',
            Font::stylesheetUrl($fonts)
        );
        $this->assertSame("'Kanit', 'Noto Sans Thai', Tahoma, sans-serif", Font::declarations($fonts)['--font-family-heading']);
    }

    public function testFontLinkIsReplacedInTemplates(): void
    {
        $dir = sys_get_temp_dir().'/aitheme-font-'.uniqid().'/';
        mkdir($dir);
        file_put_contents($dir.'index.html', "<head>\n  <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400&display=swap\">\n</head>");
        file_put_contents($dir.'main.html', '<head></head>');
        $this->assertSame('Noto Sans Thai', Font::themeFont($dir));
        Font::applyToTemplates($dir, ['heading' => 'Kanit', 'body' => 'Sarabun']);
        $this->assertStringContainsString('family=Sarabun', file_get_contents($dir.'index.html'));
        $this->assertStringNotContainsString('Noto+Sans+Thai', file_get_contents($dir.'index.html'));
        $this->assertSame('<head></head>', file_get_contents($dir.'main.html'));
        array_map('unlink', glob($dir.'*'));
        rmdir($dir);
    }

    // ── homepage composer ───────────────────────────────────────────────────

    private function catalog(): array
    {
        return [
            'detail' => ['id' => 'detail', 'kind' => 'detail'],
            'hero' => ['id' => 'hero', 'kind' => 'hero'],
            'search' => ['id' => 'search', 'kind' => 'standalone', 'macro' => '{WIDGET_SEARCH}', 'icon' => 'icon-search', 'lng' => 'Search'],
            'contact' => ['id' => 'contact', 'kind' => 'standalone', 'macro' => '{WIDGET_CONTACT}', 'icon' => 'icon-email', 'lng' => 'Contact Us'],
            'textlinks:slideshow' => ['id' => 'textlinks:slideshow', 'kind' => 'textlinks', 'name' => 'slideshow', 'items' => 3],
            'document:news' => ['id' => 'document:news', 'kind' => 'module', 'owner' => 'document', 'module' => 'news', 'widget' => 'DOCUMENT', 'topic' => 'News', 'icon' => 'icon-news', 'lng' => 'News', 'size' => 'rows', 'default' => 5, 'more' => true, 'items' => 4],
            'gallery:gallery' => ['id' => 'gallery:gallery', 'kind' => 'module', 'owner' => 'gallery', 'module' => 'gallery', 'widget' => 'GALLERY', 'topic' => '', 'icon' => 'icon-gallery', 'lng' => 'Gallery', 'size' => 'count', 'default' => 4, 'more' => true, 'items' => 2]
        ];
    }

    public function testComposeOpenThemeWithSidebarAndHero(): void
    {
        $plan = [
            'position' => 'right',
            'top' => [['block' => 'hero', 'heading' => '<b>Welcome</b> {DETAIL}', 'text' => 'Hi', 'button' => 'News', 'link' => 'document:news'], ['block' => 'search']],
            'main' => [['block' => 'document:news', 'layout' => 'thumb', 'cols' => 9, 'rows' => 99], ['block' => 'unknown'], ['block' => 'contact']],
            'sidebar' => [['block' => 'document:news', 'layout' => 'highlight'], ['block' => 'search'], ['block' => 'contact']]
        ];
        $result = Layout::compose($plan, $this->catalog(), 'open');
        $html = $result['html'];
        $this->assertSame('right', $result['position']);
        $this->assertStringContainsString('<div class="sidebar-right gap">', $html);
        // hero text is plain text: tags removed and macro braces dropped
        $this->assertStringContainsString('<h1>Welcome DETAIL</h1>', $html);
        $this->assertStringContainsString('href="{WEBURL}index.php?module=news"', $html);
        // detail added once, values clamped, unknown block dropped
        $this->assertSame(1, substr_count($html, '{DETAIL}'));
        $this->assertStringContainsString('{WIDGET_DOCUMENT module=news;layout=thumb;rows=20;cols=4}', $html);
        // narrow sidebar: highlight becomes a one-column list; contact is main-only, search only once in top was refused
        $this->assertStringContainsString('{WIDGET_DOCUMENT module=news;layout=list;rows=5;cols=1}', $html);
        $this->assertSame(1, substr_count($html, '{WIDGET_CONTACT}'));
        $this->assertSame(1, substr_count($html, '{WIDGET_SEARCH}'));
    }

    public function testComposeFramedThemeAllowsOnlyRightOrNone(): void
    {
        $plan = ['position' => 'left', 'top' => [['block' => 'textlinks:slideshow']], 'main' => [['block' => 'detail']], 'sidebar' => [['block' => 'gallery:gallery', 'limit' => 50]]];
        $result = Layout::compose($plan, $this->catalog(), 'framed');
        $this->assertSame('right', $result['position']);
        $this->assertStringStartsWith('<div class="homepage sidebar-right gap">', $result['html']);
        $this->assertStringContainsString('<section class="slideshow-block">{WIDGET_TEXTLINKS name=slideshow;layout=slideshow}</section>', $result['html']);
        $this->assertStringContainsString('{WIDGET_GALLERY module=gallery;count=12}', $result['html']);
    }

    public function testComposeWithoutSidebarMovesBlocksToMain(): void
    {
        $plan = ['position' => 'none', 'main' => [['block' => 'detail']], 'sidebar' => [['block' => 'search']]];
        $result = Layout::compose($plan, $this->catalog(), 'open');
        $this->assertSame('none', $result['position']);
        $this->assertStringNotContainsString('<aside', $result['html']);
        $this->assertStringContainsString('{WIDGET_SEARCH}', $result['html']);
    }

    public function testComposedHomepageSurvivesTheSanitizer(): void
    {
        $result = Layout::compose(Layout::defaultPlan($this->catalog(), 'open'), $this->catalog(), 'open');
        $clean = $this->html($result['html']);
        foreach (['{DETAIL}', '{WIDGET_DOCUMENT module=news;layout=highlight}', '{LNG_View All}', '{WEBURL}index.php?module=news'] as $needle) {
            $this->assertStringContainsString($needle, $clean);
        }
    }

    public function testPreviewSlugs(): void
    {
        $this->assertTrue(\Gcms\Theme::isPreview(\Gcms\Theme::previewSlug(7)));
        $this->assertFalse(\Gcms\Theme::isPreview('ai-preview-x'));
        $this->assertFalse(\Gcms\Theme::isPreview('rw'));
        $this->assertNull(\Gcms\Theme::previewDir('../etc'));
    }
}
