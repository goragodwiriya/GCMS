<?php
/**
 * @filesource widgets/textlinks/styles.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
/**
 * @return array template สำหรับ Text links
 */

return [
    'custom' => '',
    'text' => '<a title="{TITLE}"{URL}{TARGET}>{TITLE}</a>',
    'menu' => '<li><a title="{TITLE}"{URL}{TARGET}><span>{TITLE}</span></a></li>',
    'image' => '<a title="{TITLE}"{URL}{TARGET}><img alt="{TITLE}" src="{LOGO}"></a>',
    'banner' => '<a title="{TITLE}"{URL}{TARGET}><img alt="{TITLE}" src="{LOGO}"></a>',
    'hero' => '<div class="hero" style="background-image: url({LOGO});"><h1 class="page-title">{TITLE}</h1><p class="hero-description">{DESCRIPTION}</p></div>',
    'slideshow' => ''
];
