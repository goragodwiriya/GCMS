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
 * Template ของลิงค์แต่ละรายการ (ไม่รวม wrapper ของกลุ่ม ดู Views\Index)
 *
 * text  : เมนูข้อความ อยู่ใน <nav class="sidemenu"><ul> ของกลุ่ม
 * image : เมนูรูปภาพ อยู่ใน <div class="textlinks-footer"> ของกลุ่ม
 *
 * @return array
 */

return [
    'text' => '<a title="{TITLE}"{URL}{TARGET}>{TITLE}</a>',
    'image' => '<a title="{TITLE}"{URL}{TARGET}><img alt="{TITLE}" src="{LOGO}" loading="lazy"></a>'
];
