<?php
/**
 * @filesource Web/Theme.php
 *
 * Theme Manager Class
 * จัดการ theme และ template โดยใช้วิธี Variable Replacement
 * ไม่ใช้ PHP ปนใน HTML template
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Web;

/**
 * Theme Manager Class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Theme extends \Kotchasan\KBase
{
    /**
     * Singleton instance
     *
     * @var self
     */
    protected static $instance;

    /**
     * Current active theme name
     *
     * @var string
     */
    protected static $theme = 'default';

    /**
     * Theme base path
     *
     * @var string
     */
    protected static $themePath;

    /**
     * Theme base URL
     *
     * @var string
     */
    protected static $themeUrl;

    /**
     * Global variables for replacement
     *
     * @var array
     */
    protected static $globals = [];

    /**
     * Theme configuration from theme.json
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Initialized flag
     *
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize theme system
     *
     * @param string|null $theme Theme name (null = use from config)
     *
     * @return self
     */
    public function init($theme = null)
    {
        if (self::$initialized) {
            return $this;
        }

        // Set theme from parameter or config
        // Handle case where config theme might be array or empty
        $configTheme = self::$cfg->theme ?? null;
        if (is_array($configTheme) || empty($configTheme)) {
            $configTheme = 'default';
        }
        self::$theme = $theme ?? $configTheme;

        // Set theme path and URL
        self::$themePath = ROOT_PATH.'themes/'.self::$theme.'/';
        self::$themeUrl = WEB_URL.'themes/'.self::$theme.'/';

        // Fallback to default theme if not exists
        if (!is_dir(self::$themePath)) {
            self::$theme = 'default';
            self::$themePath = ROOT_PATH.'themes/default/';
            self::$themeUrl = WEB_URL.'themes/default/';
        }

        // Load theme configuration
        $this->loadConfig();

        // Set global variables
        $this->initGlobals();

        self::$initialized = true;

        return $this;
    }

    /**
     * Load theme configuration from theme.json
     */
    protected function loadConfig()
    {
        $configFile = self::$themePath.'theme.json';
        if (is_file($configFile)) {
            self::$config = json_decode(file_get_contents($configFile), true) ?? [];
        }
    }

    /**
     * Initialize global variables
     */
    protected function initGlobals()
    {
        self::$globals = [
            // Site variables
            'SITE_NAME' => self::$cfg->web_title ?? '',
            'SITE_DESCRIPTION' => self::$cfg->web_description ?? '',
            'SITE_KEYWORDS' => self::$cfg->web_keywords ?? '',
            'SITE_URL' => WEB_URL,
            'SITE_LOGO' => WEB_URL.(self::$cfg->logo ?? 'datas/images/logo.png'),
            'BASE_URL' => WEB_URL,
            'THEME_URL' => self::$themeUrl,
            'YEAR' => date('Y'),
            'LANGUAGE' => \Kotchasan\Language::name(),

            // Contact variables
            'CONTACT_ADDRESS' => self::$cfg->address ?? '',
            'CONTACT_PHONE' => self::$cfg->phone ?? '',
            'CONTACT_EMAIL' => self::$cfg->email ?? '',
            'CONTACT_FAX' => self::$cfg->fax ?? '',

            // Social variables
            'FACEBOOK_URL' => Gcms::facebookPageUrl(self::$cfg),
            'LINE_ID' => self::$cfg->line_id ?? '',
            'INSTAGRAM_URL' => self::$cfg->instagram ?? '',
            'TWITTER_URL' => self::$cfg->twitter ?? '',
            'YOUTUBE_URL' => self::$cfg->youtube ?? ''
        ];
    }

    /**
     * Set global variable(s)
     *
     * @param string|array $key Variable name or array of key-value pairs
     * @param mixed $value Variable value (if $key is string)
     *
     * @return self
     */
    public function setGlobalVars($key, $value = null)
    {
        if (is_array($key)) {
            self::$globals = array_merge(self::$globals, $key);
        } else {
            self::$globals[$key] = $value;
        }
        return $this;
    }

    /**
     * Alias for setGlobalVars (static compatibility)
     *
     * @param string|array $key
     * @param mixed $value
     */
    public static function setGlobal($key, $value = null)
    {
        if (is_array($key)) {
            self::$globals = array_merge(self::$globals, $key);
        } else {
            self::$globals[$key] = $value;
        }
    }

    /**
     * Get global variable
     *
     * @param string|null $key Variable name (null = get all)
     * @param mixed $default Default value if key not found
     *
     * @return mixed
     */
    public function getGlobal($key = null, $default = '')
    {
        if ($key === null) {
            return self::$globals;
        }
        return self::$globals[$key] ?? $default;
    }

    /**
     * Get current theme name
     *
     * @return string
     */
    public function getTheme()
    {
        return self::$theme;
    }

    /**
     * Get theme path
     *
     * @return string
     */
    public function getThemePath()
    {
        return self::$themePath;
    }

    /**
     * Get theme URL
     *
     * @return string
     */
    public function getThemeUrl()
    {
        return self::$themeUrl;
    }

    /**
     * Get asset URL for current theme
     *
     * @param string $path Asset path (e.g., 'css/theme.css', 'images/logo.png')
     *
     * @return string Full URL to asset
     */
    public function getAssetUrl($path)
    {
        return self::$themeUrl.'assets/'.$path;
    }

    /**
     * Get all available themes
     *
     * @return array
     */
    public function getThemes()
    {
        $themes = [];
        $themesDir = ROOT_PATH.'themes/';

        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) as $folder) {
                // Skip hidden folders and _shared
                if ($folder[0] === '.' || $folder[0] === '_') {
                    continue;
                }

                $themePath = $themesDir.$folder.'/';
                if (is_dir($themePath)) {
                    $configFile = $themePath.'theme.json';
                    if (is_file($configFile)) {
                        $config = json_decode(file_get_contents($configFile), true);
                        $themes[$folder] = [
                            'name' => $config['name'] ?? $folder,
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? '',
                            'description' => $config['description'] ?? '',
                            'screenshot' => is_file($themePath.'screenshot.png')
                            ? WEB_URL.'themes/'.$folder.'/screenshot.png'
                            : '',
                            'path' => $folder
                        ];
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Check if theme exists
     *
     * @param string $theme Theme name
     *
     * @return bool
     */
    public function exists($theme)
    {
        return is_dir(ROOT_PATH.'themes/'.$theme.'/');
    }

    /**
     * Get theme configuration
     *
     * @param string|null $key Config key (null = all config)
     * @param mixed $default Default value if key not found
     *
     * @return mixed
     */
    public function config($key = null, $default = null)
    {
        if ($key === null) {
            return self::$config;
        }

        // Support dot notation: settings.show_sidebar
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get asset URL for current theme (static alias)
     *
     * @param string $type Asset type (css, js, images)
     * @param string $file File name (optional)
     *
     * @return string Full URL to asset
     */
    public static function asset($type, $file = '')
    {
        $url = self::$themeUrl.'assets/'.$type.'/';
        return $file ? $url.$file : $url;
    }

    /**
     * Load and render a layout with variable replacement
     *
     * @param string $name Layout name (main, auth, blank, full-width)
     * @param array $data Variables to replace
     *
     * @return string Rendered HTML
     */
    public function loadLayout($name, $data = [])
    {
        // Ensure initialized
        if (!self::$initialized) {
            $this->init();
        }

        // Try current theme first, then fallback to default
        $file = self::$themePath.'layouts/'.$name.'.html';
        if (!is_file($file)) {
            $file = ROOT_PATH.'themes/default/layouts/'.$name.'.html';
        }

        if (!is_file($file)) {
            return '<!-- Layout "'.$name.'" not found -->';
        }

        $html = file_get_contents($file);

        // Load and replace components
        $html = $this->processComponents($html, $data);

        // Render with data
        return $this->render($html, $data);
    }

    /**
     * Load a component with variable replacement
     *
     * @param string $name Component name
     * @param array $data Variables to replace
     *
     * @return string Rendered HTML
     */
    public function loadComponent($name, $data = [])
    {
        // Ensure initialized
        if (!self::$initialized) {
            $this->init();
        }

        // Try current theme first
        $file = self::$themePath.'components/'.$name.'.html';

        // Fallback to default theme
        if (!is_file($file)) {
            $file = ROOT_PATH.'themes/default/components/'.$name.'.html';
        }

        // Fallback to shared components
        if (!is_file($file)) {
            $file = ROOT_PATH.'themes/_shared/'.$name.'.html';
        }

        if (!is_file($file)) {
            return '<!-- Component "'.$name.'" not found -->';
        }

        return $this->render(file_get_contents($file), $data);
    }

    /**
     * Load a template with variable replacement
     *
     * Template lookup order:
     * 1. themes/{current}/templates/{name}.html
     * 2. themes/default/templates/{name}.html
     * 3. templates/{name}.html (root templates folder)
     *
     * @param string $name Template name (can include path like 'product/detail')
     * @param array $data Variables to replace
     *
     * @return string Rendered HTML
     */
    public function loadTemplate($name, $data = [])
    {
        // Ensure initialized
        if (!self::$initialized) {
            $this->init();
        }

        $file = null;

        // 1. Try current theme
        $tryFile = self::$themePath.'templates/'.$name.'.html';
        if (is_file($tryFile)) {
            $file = $tryFile;
        }

        // 2. Try default theme
        if (!$file) {
            $tryFile = ROOT_PATH.'themes/default/templates/'.$name.'.html';
            if (is_file($tryFile)) {
                $file = $tryFile;
            }
        }

        // 3. Try root templates folder
        if (!$file) {
            $tryFile = ROOT_PATH.'templates/'.$name.'.html';
            if (is_file($tryFile)) {
                $file = $tryFile;
            }
        }

        if (!$file) {
            return '';
        }

        return $this->render(file_get_contents($file), $data);
    }

    /**
     * Load raw template file content without rendering
     *
     * @param string $name Template name
     *
     * @return string Raw HTML content
     */
    public function loadRawTemplate($name)
    {
        // Ensure initialized
        if (!self::$initialized) {
            $this->init();
        }

        // Try current theme
        $file = self::$themePath.'templates/'.$name.'.html';
        if (is_file($file)) {
            return file_get_contents($file);
        }

        // Try default theme
        $file = ROOT_PATH.'themes/default/templates/'.$name.'.html';
        if (is_file($file)) {
            return file_get_contents($file);
        }

        // Try root templates folder
        $file = ROOT_PATH.'templates/'.$name.'.html';
        if (is_file($file)) {
            return file_get_contents($file);
        }

        return '';
    }

    /**
     * Process component placeholders in HTML
     *
     * @param string $html HTML content
     * @param array $data Data for components
     *
     * @return string HTML with components loaded
     */
    protected function processComponents($html, $data = [])
    {
        // Components to auto-load
        $components = ['HEADER', 'NAVBAR', 'SIDEBAR', 'FOOTER', 'BREADCRUMB'];

        foreach ($components as $comp) {
            $placeholder = '<!--'.$comp.'-->';
            if (strpos($html, $placeholder) !== false) {
                // Check if component is provided in data
                if (isset($data[$comp])) {
                    $html = str_replace($placeholder, $data[$comp], $html);
                } else {
                    // Auto-load component
                    $componentName = strtolower($comp);
                    $componentHtml = $this->loadComponent($componentName, $data);
                    $html = str_replace($placeholder, $componentHtml, $html);
                }
            }
        }

        return $html;
    }

    /**
     * Replace variables in HTML template
     *
     * รองรับรูปแบบ:
     * - <!--VARIABLE--> : ตัวแปรหลัก
     * - {VARIABLE} : ตัวแปรใน loops
     *
     * @param string $html HTML template
     * @param array $data Variables to replace
     *
     * @return string Rendered HTML
     */
    public function render($html, $data = [])
    {
        // Merge global variables with provided data
        $variables = array_merge(self::$globals, $data);

        // Replace <!--VARIABLE--> format
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace('<!--'.$key.'-->', $value, $html);
            }
        }

        // Replace {VARIABLE} format
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace('{'.$key.'}', $value, $html);
            }
        }

        return $html;
    }

    /**
     * Process loop blocks
     *
     * Format: <!--BEGIN:blockName-->...<!--END:blockName-->
     *
     * @param string $html HTML with loop blocks
     * @param string $blockName Block name
     * @param array $items Array of items (each item is an array of variables)
     *
     * @return string Rendered HTML
     */
    public function renderLoop($html, $blockName, $items)
    {
        // Find block pattern
        $pattern = '/<!--BEGIN:'.$blockName.'-->(.*)<!--END:'.$blockName.'-->/s';

        if (!preg_match($pattern, $html, $matches)) {
            return $html;
        }

        $blockTemplate = $matches[1];
        $output = '';

        foreach ($items as $index => $item) {
            // Add index and helper variables
            $item['INDEX'] = $index;
            $item['NUMBER'] = $index + 1;
            $item['CLASS'] = ($index % 2 === 0) ? 'even' : 'odd';
            $item['FIRST'] = ($index === 0) ? 'first' : '';
            $item['LAST'] = ($index === count($items) - 1) ? 'last' : '';

            // Process nested conditions for this item
            $itemHtml = $blockTemplate;
            foreach ($item as $key => $value) {
                if (is_bool($value)) {
                    $itemHtml = $this->renderCondition($itemHtml, $key, $value);
                }
            }

            // Render item
            $output .= $this->render($itemHtml, $item);
        }

        // Replace block with rendered content
        return preg_replace($pattern, $output, $html);
    }

    /**
     * Process multiple loop blocks
     *
     * @param string $html HTML with loop blocks
     * @param array $loops Array of ['blockName' => $items]
     *
     * @return string Rendered HTML
     */
    public function renderLoops($html, $loops)
    {
        foreach ($loops as $blockName => $items) {
            $html = $this->renderLoop($html, $blockName, $items);
        }
        return $html;
    }

    /**
     * Process conditional blocks
     *
     * Format: <!--BEGIN:blockName-->...<!--END:blockName-->
     *
     * @param string $html HTML with conditional blocks
     * @param string $blockName Block name
     * @param bool $condition Condition to show/hide block
     *
     * @return string Rendered HTML
     */
    public function renderCondition($html, $blockName, $condition)
    {
        $pattern = '/<!--BEGIN:'.$blockName.'-->(.*)<!--END:'.$blockName.'-->/s';

        if ($condition) {
            // Keep block content, remove markers
            return preg_replace($pattern, '$1', $html);
        } else {
            // Remove entire block
            return preg_replace($pattern, '', $html);
        }
    }

    /**
     * Process multiple conditional blocks
     *
     * @param string $html HTML with conditional blocks
     * @param array $conditions Array of ['blockName' => bool]
     *
     * @return string Rendered HTML
     */
    public function renderConditions($html, $conditions)
    {
        foreach ($conditions as $blockName => $condition) {
            $html = $this->renderCondition($html, $blockName, $condition);
        }
        return $html;
    }

    /**
     * Extract a block from template
     *
     * @param string $html HTML content
     * @param string $blockName Block name
     *
     * @return string Block content or empty string
     */
    public function extractBlock($html, $blockName)
    {
        $pattern = '/<!--BEGIN:'.$blockName.'-->(.*)<!--END:'.$blockName.'-->/s';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Remove all unprocessed blocks from HTML
     *
     * @param string $html HTML content
     *
     * @return string Cleaned HTML
     */
    public function cleanBlocks($html)
    {
        // Remove any remaining BEGIN/END blocks
        $html = preg_replace('/<!--BEGIN:[a-zA-Z0-9_]+-->.*?<!--END:[a-zA-Z0-9_]+-->/s', '', $html);

        // Remove any remaining variable placeholders
        $html = preg_replace('/<!--[A-Z0-9_]+-->/', '', $html);
        $html = preg_replace('/\{[A-Z0-9_]+\}/', '', $html);

        return $html;
    }
}
