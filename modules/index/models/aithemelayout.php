<?php
/**
 * @filesource modules/index/models/aithemelayout.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Aithemelayout;

/**
 * หน้าแรก (home.html) ของธีมที่ AI Theme Generator สร้าง
 *
 * AI ไม่ได้เขียน HTML เอง แต่เลือกและเรียงบล็อกจากคลัง (catalog) ซึ่งสร้างจาก
 * โมดูลและข้อมูลที่ติดตั้งอยู่จริงในเว็บ แล้วระบบประกอบ HTML ด้วย class เดียวกับ
 * ธีมที่มากับระบบ — ได้หน้าแรกที่ widget มีข้อมูลแสดงและโครงสร้างถูกต้องเสมอ
 *
 * ธีมมีสองแบบ (frame)
 *   open   — index.html วาง {MAIN} ตรง ๆ ใน <main> (default, rw, shop) หน้าแรกกำหนดคอลัมน์เองทั้งหมด
 *   framed — index.html มี sidebar ซ้ายครอบ {MAIN} อยู่แล้ว (gts, maxsite, sea, ...) หน้าแรกเพิ่มได้แค่ sidebar ขวา
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * widget ของโมดูลแต่ละชนิด (owner)
     *   table  ตารางที่ใช้นับจำนวนรายการ, published = ตารางมีคอลัมน์ published
     *   size   ชื่อพารามิเตอร์จำนวนรายการ (null = widget ไม่มี)
     *   more   มีหน้ารายการให้ลิงก์ "ดูทั้งหมด"
     */
    const MODULE_WIDGETS = [
        'document' => ['widget' => 'DOCUMENT', 'table' => 'index', 'published' => true, 'icon' => 'icon-documents', 'size' => 'rows', 'more' => true],
        'product' => ['widget' => 'PRODUCT', 'table' => 'product', 'published' => true, 'icon' => 'icon-product', 'lng' => 'Products', 'size' => 'rows', 'more' => true],
        'board' => ['widget' => 'BOARD', 'table' => 'board_q', 'published' => true, 'icon' => 'icon-board', 'lng' => 'Forum', 'size' => 'limit', 'default' => 5, 'more' => true],
        'gallery' => ['widget' => 'GALLERY', 'table' => 'gallery_album', 'published' => false, 'icon' => 'icon-gallery', 'lng' => 'Gallery', 'size' => 'count', 'default' => 4, 'more' => true],
        'video' => ['widget' => 'VIDEO', 'table' => 'video', 'published' => false, 'icon' => 'icon-video', 'lng' => 'Video', 'size' => 'count', 'default' => 4, 'more' => true],
        'portfolio' => ['widget' => 'PORTFOLIO', 'table' => 'portfolio', 'published' => true, 'icon' => 'icon-gallery', 'lng' => 'Portfolio', 'size' => 'limit', 'default' => 6, 'more' => true],
        'event' => ['widget' => 'EVENT', 'table' => 'eventcalendar', 'published' => true, 'icon' => 'icon-calendar', 'lng' => 'Activities', 'size' => 'count', 'default' => 5, 'more' => true],
        'download' => ['widget' => 'DOWNLOAD', 'table' => 'download', 'published' => false, 'icon' => 'icon-download', 'lng' => 'Download', 'size' => 'limit', 'default' => 5, 'more' => false],
        'personnel' => ['widget' => 'PERSONNEL', 'table' => 'personnel', 'published' => true, 'icon' => 'icon-customer', 'lng' => 'Personnel', 'size' => null, 'more' => false]
    ];

    /**
     * หัวข้อและไอคอนของโมดูลเอกสารที่ธีมที่มากับระบบใช้
     */
    const DOCUMENT_TITLES = [
        'news' => ['icon-news', 'News'],
        'knowledge' => ['icon-documents', 'Knowledge'],
        'announce' => ['icon-info', 'Announcement']
    ];

    /**
     * รูปแบบการแสดงผลของ WIDGET_DOCUMENT / WIDGET_PRODUCT
     */
    const LIST_LAYOUTS = ['highlight', 'thumb', 'list', 'icon', 'carousel'];

    /**
     * จำนวนบล็อกสูงสุดในแต่ละส่วน
     */
    const LIMITS = ['top' => 2, 'main' => 12, 'sidebar' => 8, 'sidebar_left' => 8];

    /**
     * แบบของธีม: open หรือ framed (ดูคำอธิบายคลาส)
     *
     * @param string $dir โฟลเดอร์ของธีม (ลงท้ายด้วย /)
     *
     * @return string
     */
    public static function frame($dir)
    {
        $index = is_file($dir.'index.html') ? (string) file_get_contents($dir.'index.html') : '';

        return $index === '' || preg_match('#<main\b[^>]*>\s*\{MAIN\}\s*</main>#i', $index) ? 'open' : 'framed';
    }

    /**
     * ตำแหน่ง sidebar ที่ธีมแบบนี้ใช้ได้
     *
     * @param string $frame
     *
     * @return array
     */
    public static function positions($frame)
    {
        return $frame === 'framed' ? ['right', 'none'] : ['right', 'left', 'both', 'none'];
    }

    /**
     * คลังบล็อกที่ใช้ได้ในเว็บนี้
     * ไม่รวม widget ที่ index.html ของธีมแสดงอยู่แล้ว (เช่น ช่องค้นหาใน sidebar ของธีมแบบ framed)
     *
     * @param string $dir โฟลเดอร์ของธีมต้นแบบ (ลงท้ายด้วย /)
     *
     * @return array id => รายละเอียดของบล็อก
     */
    public static function catalog($dir)
    {
        $catalog = [
            'detail' => ['id' => 'detail', 'kind' => 'detail', 'description' => "the homepage's own editable content"],
            'hero' => ['id' => 'hero', 'kind' => 'hero', 'description' => 'large heading + intro text + one button'],
            'search' => ['id' => 'search', 'kind' => 'standalone', 'macro' => '{WIDGET_SEARCH}', 'icon' => 'icon-search', 'lng' => 'Search', 'description' => 'site search box'],
            'tags' => ['id' => 'tags', 'kind' => 'standalone', 'macro' => '{WIDGET_TAGS}', 'icon' => 'icon-tags', 'lng' => 'Tags', 'description' => 'popular tags cloud'],
            'counter' => ['id' => 'counter', 'kind' => 'standalone', 'macro' => '{WIDGET_COUNTER}', 'icon' => 'icon-chart', 'lng' => 'Counter', 'description' => 'visitor counter'],
            'contact' => ['id' => 'contact', 'kind' => 'standalone', 'macro' => '{WIDGET_CONTACT}', 'icon' => 'icon-email', 'lng' => 'Contact Us', 'description' => 'contact form (main column only)']
        ];
        if (!empty(self::$cfg->map['lat']) && !empty(self::$cfg->map['lng'])) {
            $catalog['map'] = ['id' => 'map', 'kind' => 'standalone', 'macro' => '{WIDGET_MAP}', 'icon' => 'icon-location', 'lng' => 'Map', 'description' => 'map of the organization (main column only)'];
        }
        if (!empty(self::$cfg->facebook['user'])) {
            $catalog['facebook'] = ['id' => 'facebook', 'kind' => 'standalone', 'macro' => '{WIDGET_FACEBOOK}', 'icon' => '', 'lng' => '', 'description' => 'Facebook page box (sidebar)'];
        }
        if (class_exists('Widgets\\Stats\\Models\\Index') && !empty(\Widgets\Stats\Models\Index::getItems('default'))) {
            $catalog['stats'] = ['id' => 'stats', 'kind' => 'standalone', 'macro' => '{WIDGET_STATS}', 'icon' => '', 'lng' => '', 'description' => 'animated statistics counters (main column only)'];
        }
        foreach (self::textlinks() as $name => $count) {
            $catalog['textlinks:'.$name] = [
                'id' => 'textlinks:'.$name,
                'kind' => 'textlinks',
                'name' => $name,
                'items' => $count,
                'description' => $name === 'slideshow' ? 'full-width image slideshow' : ($name === 'banner' ? 'rotating banner image' : 'image links "'.$name.'"')
            ];
        }
        foreach (self::modules() as $module) {
            $catalog[$module['id']] = $module;
        }

        // widget ที่โครงหน้าเว็บ (index.html) แสดงอยู่แล้ว ไม่ต้องใส่ซ้ำในหน้าแรก
        $index = is_file($dir.'index.html') ? (string) file_get_contents($dir.'index.html') : '';
        foreach (array_keys($catalog) as $id) {
            $entry = $catalog[$id];
            if (isset($entry['macro']) && strpos($index, $entry['macro']) !== false) {
                unset($catalog[$id]);
            } elseif ($entry['kind'] === 'textlinks' && preg_match('/\{WIDGET_TEXTLINKS\s[^}]*name='.preg_quote($entry['name'], '/').'[;}]/', $index)) {
                unset($catalog[$id]);
            }
        }

        return $catalog;
    }

    /**
     * รายการบล็อกสำหรับ prompt ของ AI
     *
     * @param array $catalog
     *
     * @return string
     */
    public static function describe(array $catalog)
    {
        $lines = [];
        foreach ($catalog as $id => $entry) {
            $line = '- '.$id;
            if (!empty($entry['topic'])) {
                $line .= ' "'.$entry['topic'].'"';
            }
            $line .= ' — '.$entry['description'];
            if (isset($entry['items'])) {
                $line .= $entry['items'] > 0 ? " ({$entry['items']} items)" : ' (EMPTY — no content yet, avoid)';
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * แผนหน้าแรกเริ่มต้นจากคลังบล็อก (ใช้เมื่อ AI ตอบแผนที่ใช้ไม่ได้)
     *
     * @param array  $catalog
     * @param string $frame
     *
     * @return array
     */
    public static function defaultPlan(array $catalog, $frame)
    {
        $plan = ['position' => 'right', 'top' => [], 'main' => [['block' => 'detail']], 'sidebar' => []];
        if (isset($catalog['textlinks:slideshow'])) {
            $plan['top'][] = ['block' => 'textlinks:slideshow'];
        }
        $wide = 0;
        foreach ($catalog as $id => $entry) {
            if ($entry['kind'] !== 'module' || empty($entry['items'])) {
                continue;
            }
            if (in_array($entry['owner'], ['document', 'product', 'gallery', 'video', 'portfolio'], true) && $wide < 4) {
                $plan['main'][] = ['block' => $id, 'layout' => $wide === 0 ? 'highlight' : 'thumb'];
                ++$wide;
            } else {
                $plan['sidebar'][] = ['block' => $id];
            }
        }
        foreach (['search', 'tags', 'counter'] as $id) {
            if (isset($catalog[$id])) {
                $plan['sidebar'][] = ['block' => $id];
            }
        }
        if ($frame === 'framed' && empty($plan['sidebar'])) {
            $plan['position'] = 'none';
        }

        return $plan;
    }

    /**
     * ประกอบ home.html จากแผนของ AI
     *
     * @param array  $plan     ['position', 'top', 'main', 'sidebar', 'sidebar_left']
     * @param array  $catalog  ค่าจาก catalog()
     * @param string $frame    open|framed
     * @param string $position ตำแหน่ง sidebar ที่ผู้ใช้เลือก ('' = ให้ AI เลือก)
     *
     * @return array ['html' => string, 'position' => string, 'blocks' => int]
     */
    public static function compose(array $plan, array $catalog, $frame, $position = '')
    {
        $allowed = self::positions($frame);
        if (!in_array($position, $allowed, true)) {
            $position = strtolower(trim((string) ($plan['position'] ?? $plan['sidebar_position'] ?? 'right')));
        }
        if (!in_array($position, $allowed, true)) {
            $position = 'right';
        }

        $columns = [];
        foreach (array_keys(self::LIMITS) as $column) {
            $columns[$column] = self::cleanList($plan[$column] ?? [], $column, $catalog);
        }
        // {DETAIL} คือเนื้อหาหน้าแรกที่แก้ได้จากหลังบ้าน ต้องมีหนึ่งครั้งในคอลัมน์หลัก
        $detail = false;
        foreach ($columns['main'] as $item) {
            $detail = $detail || $item['block'] === 'detail';
        }
        if (!$detail) {
            array_unshift($columns['main'], ['block' => 'detail']);
        }
        if ($position === 'both' && empty($columns['sidebar_left'])) {
            $position = 'right';
        }
        if ($position === 'both' && empty($columns['sidebar'])) {
            $columns['sidebar'] = $columns['sidebar_left'];
            $columns['sidebar_left'] = [];
            $position = 'right';
        }
        if ($position !== 'both' && !empty($columns['sidebar_left'])) {
            $columns['sidebar'] = array_merge($columns['sidebar_left'], $columns['sidebar']);
            $columns['sidebar_left'] = [];
        }
        if ($position !== 'none' && empty($columns['sidebar'])) {
            $position = 'none';
        }
        if ($position === 'none') {
            // ไม่มี sidebar: บล็อกของ sidebar ย้ายไปท้ายคอลัมน์หลัก
            foreach ($columns['sidebar'] as $item) {
                $columns['main'][] = $item;
            }
            $columns['sidebar'] = [];
        }

        $html = [];
        $count = 0;
        $render = function ($column, $indent) use ($columns, $catalog, $frame, &$count) {
            $out = [];
            foreach ($columns[$column] as $item) {
                $block = self::renderBlock($item, $catalog[$item['block']] ?? ['kind' => 'detail'], $column, $frame);
                if ($block !== '') {
                    $out[] = self::indent($block, $indent);
                    ++$count;
                }
            }

            return implode("\n", $out);
        };

        if ($frame === 'framed') {
            $html[] = '<div class="homepage'.($position === 'right' ? ' sidebar-right gap' : '').'">';
            $html[] = '  <div class="home-content">';
            $html[] = $render('top', 4);
            $html[] = $render('main', 4);
            $html[] = '  </div>';
            if ($position === 'right') {
                $html[] = '  <aside class="sidebar home-sidebar">';
                $html[] = $render('sidebar', 4);
                $html[] = '  </aside>';
            }
            $html[] = '</div>';
        } else {
            $html[] = '<div class="homepage">';
            $html[] = $render('top', 2);
            if ($position === 'none') {
                $html[] = '  <div class="home-content">';
                $html[] = $render('main', 4);
                $html[] = '  </div>';
            } else {
                $wrapper = [
                    'right' => 'sidebar-right gap',
                    'left' => 'sidebar-left sidebar-bottom-mobile gap',
                    'both' => 'three-columns gap'
                ];
                $html[] = '  <div class="container">';
                $html[] = '    <div class="'.$wrapper[$position].'">';
                if ($position === 'left') {
                    $html[] = '      <aside class="sidebar home-sidebar">';
                    $html[] = $render('sidebar', 8);
                    $html[] = '      </aside>';
                } elseif ($position === 'both') {
                    $html[] = '      <aside class="sidebar home-sidebar">';
                    $html[] = $render('sidebar_left', 8);
                    $html[] = '      </aside>';
                }
                $html[] = '      <div class="content home-content">';
                $html[] = $render('main', 8);
                $html[] = '      </div>';
                if ($position !== 'left') {
                    $html[] = '      <aside class="sidebar home-sidebar">';
                    $html[] = $render('sidebar', 8);
                    $html[] = '      </aside>';
                }
                $html[] = '    </div>';
                $html[] = '  </div>';
            }
            $html[] = '</div>';
        }

        return [
            'html' => implode("\n", array_filter($html, 'strlen'))."\n",
            'position' => $position,
            'blocks' => $count
        ];
    }

    /**
     * รายการบล็อกของคอลัมน์ที่ผ่านการตรวจแล้ว
     *
     * @param mixed  $items
     * @param string $column
     * @param array  $catalog
     *
     * @return array
     */
    private static function cleanList($items, $column, array $catalog)
    {
        $result = [];
        $seen = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_string($item)) {
                $item = ['block' => $item];
            }
            if (!is_array($item)) {
                continue;
            }
            $id = strtolower(trim((string) ($item['block'] ?? $item['id'] ?? '')));
            if (!isset($catalog[$id])) {
                continue;
            }
            $kind = $catalog[$id]['kind'];
            // บล็อกกว้างอยู่ได้เฉพาะบางตำแหน่ง
            if (($kind === 'hero' && $column !== 'top')
                || ($kind === 'detail' && $column !== 'main')
                || ($column === 'top' && !in_array($kind, ['hero', 'textlinks'], true))
                || ($id === 'textlinks:slideshow' && !in_array($column, ['top', 'main'], true))
                || (in_array($id, ['contact', 'map', 'stats'], true) && $column !== 'main')) {
                continue;
            }
            $key = $id.'|'.strtolower((string) ($item['layout'] ?? ''));
            if (isset($seen[$key]) || ($kind === 'detail' && isset($seen['detail|']))) {
                continue;
            }
            $seen[$kind === 'detail' ? 'detail|' : $key] = true;
            $item['block'] = $id;
            $result[] = $item;
            if (count($result) >= self::LIMITS[$column]) {
                break;
            }
        }

        return $result;
    }

    /**
     * HTML ของบล็อกหนึ่งบล็อก
     *
     * @param array  $item   บล็อกในแผน
     * @param array  $entry  รายละเอียดจากคลัง
     * @param string $column top|main|sidebar|sidebar_left
     * @param string $frame
     *
     * @return string
     */
    private static function renderBlock(array $item, array $entry, $column, $frame)
    {
        $sidebar = $column === 'sidebar' || $column === 'sidebar_left';
        switch ($entry['kind']) {
            case 'detail':
                return self::section('', '', '', '<div class="page-detail">{DETAIL}</div>', 'section-bg home');
            case 'hero':
                return self::hero($item);
            case 'textlinks':
                $macro = '{WIDGET_TEXTLINKS name='.$entry['name'].(in_array($entry['name'], ['slideshow', 'banner'], true) ? ';layout='.$entry['name'] : '').'}';
                if ($entry['name'] === 'slideshow') {
                    return $column === 'top' && $frame === 'open'
                        ? '<div class="container home-banner-wrap">'.$macro.'</div>'
                        : '<section class="slideshow-block">'.$macro.'</section>';
                }

                return self::section('', '', '', $macro);
            case 'standalone':
                if ($entry['id'] === 'contact' && !empty($item['map'])) {
                    $body = '<div class="ggrid"><div class="block6 tablet12">{WIDGET_CONTACT}</div><div class="block6 tablet12">{WIDGET_MAP}</div></div>';

                    return self::section(self::title($item, $entry), $entry['icon'], '', $body);
                }
                $title = $entry['lng'] === '' ? '' : self::title($item, $entry);

                return self::section($title, $entry['icon'], '', $entry['macro']);
            case 'module':
                $macro = self::moduleMacro($item, $entry, $sidebar);
                $more = $entry['more'] ? '{WEBURL}index.php?module='.$entry['module'] : '';

                return self::section(self::title($item, $entry), $entry['icon'], $more, $macro);
        }

        return '';
    }

    /**
     * แมโคร widget ของโมดูล พร้อมพารามิเตอร์ที่ตรวจแล้ว
     *
     * @param array $item
     * @param array $entry
     * @param bool  $sidebar
     *
     * @return string
     */
    private static function moduleMacro(array $item, array $entry, $sidebar)
    {
        $params = ['module='.$entry['module']];
        $widget = $entry['widget'];
        if ($widget === 'DOCUMENT' || $widget === 'PRODUCT') {
            $layout = strtolower((string) ($item['layout'] ?? ''));
            if ($layout === 'card') {
                $layout = 'thumb';
            }
            if (!in_array($layout, self::LIST_LAYOUTS, true)) {
                $layout = $widget === 'PRODUCT' ? 'thumb' : ($sidebar ? 'list' : 'thumb');
            }
            // sidebar แคบ: ภาพเด่นและภาพหลายคอลัมน์แสดงไม่ได้ ใช้รายการคอลัมน์เดียว
            if ($sidebar && in_array($layout, ['highlight', 'thumb', 'carousel'], true)) {
                $layout = 'list';
            }
            $params[] = 'layout='.$layout;
            if ($layout === 'carousel') {
                $params[] = 'limit='.self::clamp($item['limit'] ?? 6, 1, 12);
                $params[] = 'mobile=1;tablet=2;desktop='.($widget === 'PRODUCT' ? 4 : 3);
            } elseif ($layout !== 'highlight') {
                $cols = $sidebar ? 1 : self::clamp($item['cols'] ?? ($layout === 'list' ? 1 : 3), 1, 4);
                $rows = self::clamp($item['rows'] ?? ($cols === 1 ? 5 : 1), 1, $sidebar ? 10 : 20);
                $params[] = 'rows='.$rows;
                $params[] = 'cols='.$cols;
            }
        } elseif ($widget === 'PERSONNEL') {
            $params[] = 'level=1';
            $params[] = $sidebar ? 'layout=fade' : 'menu=1';
        } elseif ($entry['size'] !== null) {
            $value = $item['limit'] ?? $item['count'] ?? $item['rows'] ?? $entry['default'];
            $params[] = $entry['size'].'='.self::clamp($value, 1, 12);
        }

        return '{WIDGET_'.$widget.' '.implode(';', $params).'}';
    }

    /**
     * hero: หัวข้อ ข้อความ และปุ่ม (ข้อความจาก AI เป็นข้อความล้วน)
     *
     * @param array $item
     *
     * @return string
     */
    private static function hero(array $item)
    {
        $heading = self::text($item['heading'] ?? $item['title'] ?? '', 80);
        if ($heading === '') {
            $heading = '{WEBTITLE}';
        }
        $html = ['<section class="hero">', '  <div class="container">', '    <div class="hero__content">'];
        $html[] = '      <h1>'.$heading.'</h1>';
        $text = self::text($item['text'] ?? '', 240);
        if ($text !== '') {
            $html[] = '      <p>'.$text.'</p>';
        }
        $button = self::text($item['button'] ?? '', 40);
        $link = strtolower(trim((string) ($item['link'] ?? '')));
        if ($button !== '' && preg_match('/^[a-z]+:([a-z0-9_-]+)$/', $link, $match)) {
            $html[] = '      <div><a class="btn btn-secondary large" href="{WEBURL}index.php?module='.$match[1].'">'.$button.'</a></div>';
        }
        $html[] = '    </div>';
        $html[] = '  </div>';
        $html[] = '</section>';

        return implode("\n", $html);
    }

    /**
     * section มาตรฐานของธีม
     *
     * @param string $title หัวข้อ ('' = ไม่มีแถบหัวข้อ)
     * @param string $icon
     * @param string $more  ลิงก์ดูทั้งหมด
     * @param string $body  เนื้อหาใน section__body
     * @param string $class
     *
     * @return string
     */
    private static function section($title, $icon, $more, $body, $class = 'section-bg')
    {
        $html = ['<section class="'.$class.'">', '  <div class="container">'];
        if ($title !== '') {
            $html[] = '    <div class="section__header">';
            $html[] = '      <h2'.($icon === '' ? '' : ' class="'.$icon.'"').'>'.$title.'</h2>';
            if ($more !== '') {
                $html[] = '      <a href="'.$more.'" class="view-more">{LNG_View All}...</a>';
            }
            $html[] = '    </div>';
        }
        $html[] = '    <div class="section__body">'.$body.'</div>';
        $html[] = '  </div>';
        $html[] = '</section>';

        return implode("\n", $html);
    }

    /**
     * หัวข้อของบล็อก: ข้อความจาก AI หรือหัวข้อมาตรฐาน ({LNG_...} / ชื่อโมดูลในเมนู)
     *
     * @param array $item
     * @param array $entry
     *
     * @return string
     */
    private static function title(array $item, array $entry)
    {
        $title = self::text($item['title'] ?? '', 60);
        if ($title !== '') {
            return $title;
        }
        if (!empty($entry['lng'])) {
            return '{LNG_'.$entry['lng'].'}';
        }

        return self::text($entry['topic'] ?? '', 60);
    }

    /**
     * ข้อความล้วนที่ปลอดภัยสำหรับเทมเพลต (ไม่มีแท็กและแมโคร)
     *
     * @param mixed $text
     * @param int   $max
     *
     * @return string
     */
    private static function text($text, $max)
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
        $text = str_replace(['{', '}', '$', '\\'], '', $text);
        if (mb_strlen($text) > $max) {
            $text = rtrim(mb_substr($text, 0, $max));
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * @param mixed $value
     * @param int   $min
     * @param int   $max
     *
     * @return int
     */
    private static function clamp($value, $min, $max)
    {
        return max($min, min($max, (int) $value));
    }

    /**
     * @param string $html
     * @param int    $spaces
     *
     * @return string
     */
    private static function indent($html, $spaces)
    {
        $pad = str_repeat(' ', $spaces);

        return $pad.str_replace("\n", "\n".$pad, $html);
    }

    /**
     * ชุดลิงก์รูปภาพ (textlinks) ที่มีรายการเผยแพร่อยู่
     *
     * @return array ชื่อ => จำนวน
     */
    private static function textlinks()
    {
        $result = [];
        try {
            $rows = \Kotchasan\Model::createQuery()
                ->select('name')
                ->from('textlink')
                ->where(['published', 1])
                ->fetchAll();
            foreach ($rows as $row) {
                $name = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $row->name));
                if ($name !== '') {
                    $result[$name] = ($result[$name] ?? 0) + 1;
                }
            }
        } catch (\Throwable $e) {
            // ไม่มีตาราง textlink = ไม่มีบล็อกนี้
        }

        return $result;
    }

    /**
     * โมดูลที่ติดตั้งและมี widget พร้อมจำนวนรายการที่เผยแพร่
     *
     * @return array
     */
    private static function modules()
    {
        $result = [];
        try {
            $rows = \Kotchasan\Model::createQuery()
                ->select('M.id', 'M.owner', 'M.module', 'D.topic')
                ->from('modules M')
                ->join('index I', [['I.module_id', 'M.id'], ['I.index', 1]], 'LEFT')
                ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'M.id'], ['D.language', ['', \Kotchasan\Language::name()]]], 'LEFT')
                ->where(['M.owner', array_keys(self::MODULE_WIDGETS)])
                ->orderBy('M.id')
                ->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as $row) {
            $id = $row->owner.':'.$row->module;
            if (isset($result[$id])) {
                continue;
            }
            $widget = self::MODULE_WIDGETS[$row->owner];
            $entry = [
                'id' => $id,
                'kind' => 'module',
                'owner' => $row->owner,
                'module' => $row->module,
                'widget' => $widget['widget'],
                'topic' => trim((string) $row->topic),
                'icon' => $widget['icon'],
                'lng' => $widget['lng'] ?? '',
                'size' => $widget['size'],
                'default' => $widget['default'] ?? 5,
                'more' => $widget['more'],
                'items' => self::countItems($widget, (int) $row->id)
            ];
            if ($row->owner === 'document') {
                list($entry['icon'], $entry['lng']) = self::DOCUMENT_TITLES[$row->module] ?? ['icon-documents', ''];
                $entry['description'] = 'articles; layout highlight|thumb|list|icon|carousel, rows 1-20, cols 1-4, limit 1-12 (carousel)';
            } elseif ($row->owner === 'product') {
                $entry['description'] = 'products; layout thumb|list|icon|highlight|carousel, rows 1-20, cols 1-4, limit 1-12 (carousel)';
            } elseif ($widget['size'] !== null) {
                $entry['description'] = $row->owner.' items; limit 1-12';
            } else {
                $entry['description'] = $row->owner;
            }
            $result[$id] = $entry;
        }

        return array_values($result);
    }

    /**
     * จำนวนรายการที่เผยแพร่ของโมดูล
     *
     * @param array $widget
     * @param int   $moduleId
     *
     * @return int|null
     */
    private static function countItems(array $widget, $moduleId)
    {
        $where = [['module_id', $moduleId]];
        if ($widget['table'] === 'index') {
            $where[] = ['index', 0];
        }
        if ($widget['published']) {
            $where[] = ['published', 1];
        }
        try {
            $row = \Kotchasan\Model::createQuery()
                ->selectCount()
                ->from($widget['table'])
                ->where($where)
                ->first();

            return $row ? (int) $row->count : 0;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
