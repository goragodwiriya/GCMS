<?php
/**
 * @filesource Widgets/Textlink/styles.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
/**
 * @return array template สำหรับ Text links
 */

return array(
    'custom' => '',
    'text' => '<a title="{TITLE}"{URL}{TARGET}>{TITLE}</a>',
    'menu' => '<li><a title="{TITLE}"{URL}{TARGET}><span>{TITLE}</span></a></li>',
    'image' => '<a title="{TITLE}"{URL}{TARGET}><img class="nozoom" alt="{TITLE}" src="{LOGO}"></a>',
    'banner' => '<a title="{TITLE}"{URL}{TARGET}><img class="nozoom" alt="{TITLE}" src="{LOGO}"></a>',
    'slideshow' => ''
);
