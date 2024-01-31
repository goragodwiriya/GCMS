<?php
/**
 * @filesource maxsite/installer.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Maxsite\Installer;

use Index\Install\Model as Installer;
use Index\Upgrade\Model as Upgrade;
use Kotchasan\File;
use Kotchasan\Text;

/**
 * นำเข้าข้อมูลจาก ATOMYMAXSITE 2.5
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param $db
     */
    public static function import($db)
    {
        // แก้ไข URL ให้เป็น URL ของ maxsite
        // ใช้สำหรับการนำเข้าไฟล์จาก maxsite ต้องมี '/' ปิดท้ายด้วย
        // เช่น http://maxsite.com/ หรือใช้ค่าที่กำหนดนี้ หากติดตั้ง GCMS ลงในไดเร็คทอรี่เดิมของ maxsite
        $maxsite_url = WEB_URL;
        $maxsite_url = 'http://localhost/maxsite/';
        // database prefix ของ maxsite
        $prefix = 'web';
        // เนื้อหาส่งกลับ
        $content = array();
        // import users
        $displayname = array();
        $users = array();
        $admin = array();
        if (Upgrade::tableExists($db, $prefix.'_admin')) {
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_admin` WHERE `email` != '';") as $item) {
                $admin[$item->email] = $item->email;
            }
        }
        if (Upgrade::tableExists($db, $prefix.'_member')) {
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_member` WHERE `email` != '' ORDER BY `id`;") as $item) {
                if ($item->id == 1) {
                    $item->email = $_SESSION['email'];
                    $item->password = $_SESSION['password'];
                    $item->status = 1;
                    $item->active = 1;
                } elseif (in_array($item->email, $admin)) {
                    $item->status = 1;
                    $item->active = 1;
                } else {
                    $item->status = 0;
                    $item->active = 0;
                }
                if (mb_strlen($item->nic_name) < 2) {
                    $item->nic_name = $item->name;
                }
                $nic_name = strtoupper($item->nic_name);
                if (!isset($displayname[$nic_name])) {
                    $displayname[$nic_name] = 1;
                } else {
                    ++$displayname[$nic_name];
                    $item->nic_name = $item->nic_name.$displayname[$nic_name];
                }
                list($y, $m, $d) = explode('/', $item->signup);
                $users[$item->email] = array(
                    'id' => $item->id,
                    'email' => $item->email,
                    'password' => sha1(self::$cfg->password_key.$item->password.$item->email),
                    'name' => $item->name,
                    'displayname' => $item->nic_name,
                    'address1' => $item->address,
                    'province' => $item->province,
                    'zipcode' => $item->zipcode,
                    'country' => 'TH',
                    'phone1' => $item->phone,
                    'company' => $item->office,
                    'birthday' => ($item->year - 543).'-'.$item->month.'-'.$item->date,
                    'status' => $item->status,
                    'social' => 0,
                    'active' => $item->active,
                    'create_date' => mktime(0, 0, 0, $m, $d, $y - 543),
                    'icon' => $item->member_pic,
                    'sex' => $item->sex == 'ชาย' ? 'f' : 'm',
                );
            }
            // ลบข้อมูล user
            $db->emptyTable($_SESSION['prefix'].'_user');
            // insert user
            foreach ($users as $item) {
                $db->insert($_SESSION['prefix'].'_user', $item);
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_member</b> complete...</li>';
        }
        if (Upgrade::tableExists($db, $prefix.'_page')) {
            // หน้า Home ของเว็บ อบต
            $homepage = '<div class="rightsidebar homepage"><div class="content subcontent"><div class="bdr_content"><h2>ข้อกำหนด การนำไปใช้งาน</h2>ผู้ที่ต้องการนำสคริปต์ไปใช้งาน แนะนำให้อ่านก่อนนะครับ โดยผมอนุญาตให้นำสคริปต์ ไปใช้งานได้ โดยมีข้อแม้ว่า<ol>	<li>การนำสคริปต์ไปใช้งาน ต้องทำลิงค์กลับมายังเว็บไซต์ผมในทุกๆหน้า ย้ำว่าลิงค์นะครับ จะเป็นข้อความ หรือรูปภาพก็ได้ และต้องสามารถมองเห็นและเข้าถึงได้ทั้งคนและ Search Engine คำแนะนำคือใส่ไว้ในส่วน footer ของ template แหละครับ (หลายคน นำไปใช้แล้ว ไปบอกว่าเป็นของตัวเอง) ไม่ว่าคุณจะไปพัฒนาหรือปรับแต่งอย่างไรต่อก็ตาม ขอให้เกียรติกันบ้าง</li>	<li>ห้ามนำไปขายต่อ หรือใช้กับบุคลที่สาม การนำไปขายต่อ จะต้องแจ้งให้ผมทราบ และจ่ายค่านำไปใช้งาน ต่อ 1 ชิ้น (ไม่มีการเหมาจ่ายนะครับ เอาไปใช้ 1 เว็บจ่ายให้ผมแค่ 1 เว็บ)</li>	<li>สามารถนำไปเผยแหร่ หรือ พัฒนาต่อได้ ไม่ห้ามหวง โดยผมแนะนำว่า ให้ทำลิงค์กลับมาดาวน์โหลดบนเว็บไซต์ผม เนื่องจาก หากมีการอัปเกรด จะสามารถหาได้จากที่ผมที่เดียว โดยการนำไปเผยแพร่ ให้ใช้กฏเดียวกันกับที่ใช้บนเว็บไซต์นี้</li>	<li>ขอความร่วมมือในการช่วยเหลือค่าใช้จ่ายในการจัดทำของผม (ไม่จำกัดจำนวนเงิน) เพื่อให้ผมได้มีเวลาในการพัฒนาต่ออย่างต่อเนื่อง และเป็นการบอกแก่ชาวโลกด้วยว่า คนไทยก็รู้จักเห็นค่าความรู้คนอื่นเช่นกัน</li>	<li>มีข้อสงสัย ใดๆ หรือ อยากให้ผมช่วยเหลืออย่างไร หรืออยากให้ผมพัฒนาอะไรเพิ่มเติม แจ้งได้ที่เว็บบอร์ด ถ้าไม่เหลือบ่ากว่าแรงผมจะจัดทำให้ หรือ จะว่าจ้างให้ผมจัดทำให้ตามความต้องการก็ไม่ขัดนะครับ</li>	<li>พัฒนาโมดูล วิดเจ็ท และ Theme ที่สามารถใช้ร่วมกับ GCMS ได้ และอยากเผยแพร่ต่อ ส่งมาให้ผมเพื่อเผยแพร่ให้ได้นะครับ</li></ol></div><section class="widget widget_bdr document news"><header class="bar1"><h2>ข่าวประชาสัมพันธ์</h2></header><div class="bg">{WIDGET_DOCUMENT_news}</div><p class="right"><a class="icon-next" href="news.html" rel="nofollow">อ่านต่อ</a></p></section><section class="widget widget_bdr document knowledge"><header class="bar2"><h2>ข่าวสารทั่วไป</h2></header><div class="bg">{WIDGET_DOCUMENT_knowledge}</div><p class="right"><a class="icon-next" href="knowledge.html" rel="nofollow">อ่านต่อ</a></p></section><section class="widget widget_bdr download"><header class="bar2"><h2>ดาวน์โหลด</h2></header><div class="bg">{WIDGET_DOWNLOAD_download}</div><p class="right"><a class="icon-next" href="download.html" rel="nofollow">ดูทั้งหมด</a></p></section><section class="widget widget_bdr board"><header class="bartop"><h2>กระดานข่าว</h2></header><div class="bg">{WIDGET_BOARD_forum}</div><p class="right"><a class="icon-next" href="forum.html" rel="nofollow">อ่านต่อ</a></p></section><section class="widget widget_bdr gallery"><header class="bar2"><h2>Gallery</h2></header>{WIDGET_ALBUM}</section><section class="widget" id="rss_widget">{WIDGET_RSS}</section></div><aside class="sidebar"><div class="sidebar_bg_color"><section class="widget widget_bdr personnel"><header><h2>ผู้บริหาร</h2></header><div class="widget_bg">{WIDGET_PERSONNEL_1_menu}</div></section><section class="widget widget_bdr counter"><header><h2>Counter</h2></header><div class="widget_bg">{WIDGET_COUNTER}</div></section></div></aside></div><script>new GRSS(\'{WEBURL}gallery.rss\').show(\'rss-entertain\',0);</script>';
            // install Module & Menu
            Installer::installing($db, 'index', 'home', 'Home', $homepage, 1, 'MAINMENU', 'Home');
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_page` ORDER BY `sort`") as $item) {
                if (preg_match('/name=([a-z]+)/', $item->links, $match)) {
                    if (strtolower($match[1]) == 'page') {
                        Installer::installing($db, 'index', substr(uniqid(), 0, 5), $item->name, $item->detail, $item->status, strtoupper($item->menugr), $item->menuname);
                    }
                } else {
                    Installer::installMenu($db, strtoupper($item->menugr), $item->name, $item->proto.$item->links, $item->target, $item->status, 0);
                }
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_page</b> complete...</li>';
        }
        $menus = array();
        if (Upgrade::tableExists($db, $prefix.'_menu')) {
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_menu`") as $item) {
                if (preg_match('/file=([a-z]+)/', $item->link, $match)) {
                    $menus[$match[1]] = $item->name;
                }
            }
        }
        // บทความ
        foreach (array('blog', 'knowledge', 'news', 'research') as $module) {
            $table = $prefix.'_'.$module;
            if (Upgrade::tableExists($db, $table)) {
                // เมนู
                $menu = isset($menus[$module]) ? $menus[$module] : ucwords($module);
                $module_id = Installer::installing($db, 'document', $module, $menu, '', 1, 'SIDEMENU', $menu);
                // หมวดหมู่
                foreach ($db->customQuery("SELECT * FROM `{$prefix}_{$module}_category` ORDER BY `sort`") as $item) {
                    $db->insert($_SESSION['prefix'].'_category', array(
                        'module_id' => $module_id,
                        'category_id' => $item->id,
                        'config' => serialize(array('can_reply' => 1)),
                        'topic' => serialize(array('' => $item->category_name)),
                        'detail' => serialize(array('' => $item->category_name)),
                        'published' => 1,
                    ));
                }
                $sql = "SELECT `id` FROM `{$prefix}_member` AS M WHERE M.`user`=Q.`posted` LIMIT 1";
                foreach ($db->customQuery("SELECT Q.*,($sql) AS `member_id` FROM `{$prefix}_{$module}` AS Q ORDER BY Q.`id`") as $item) {
                    if (!empty($item->pic)) {
                        $picture = $maxsite_url.'icon/'.$module.'_'.$item->post_date.'_'.$item->posted.'.jpg';
                    } else {
                        $picture = '';
                    }
                    Installer::insertIndex($db, $module_id, 1, $item->topic, $item->detail, (int) $item->post_date, $item->pageview, 0, $item->topic, Text::oneLine(strip_tags($item->headline)), (int) $item->member_id, $item->enable_comment == 1 ? 1 : 0, (int) $item->category, $picture);
                }
                // อัปเดตหมวดหมู่
                Installer::updateCategories($db, $module_id);
                // ความคิดเห็น
                $sql = "SELECT Q.*,M.`id` AS `member_id`,M.`nic_name` FROM `{$prefix}_{$module}_comment` AS Q";
                $sql .= " LEFT JOIN `{$prefix}_member` AS M ON M.`user`=Q.`name`";
                foreach ($db->customQuery($sql) as $item) {
                    $db->insert($_SESSION['prefix'].'_comment', array(
                        'module_id' => $module_id,
                        'index_id' => $item->{$module.'_id'},
                        'detail' => trim(htmlspecialchars(strip_tags(html_entity_decode($item->comment)))),
                        'last_update' => $item->post_date,
                        'ip' => $item->ip,
                        'member_id' => (int) $item->member_id,
                        'sender' => empty($item->member_id) ? $item->name : $item->nic_name,
                    ));
                }
                $content[] = '<li class="correct">Import from <b>'.$prefix.'_'.$module.'</b> complete...</li>';
            }
        }
        // อัปเดตความคิดเห็น document
        Installer::updateComments($db, $_SESSION['prefix'].'_index', $_SESSION['prefix'].'_comment');
        // webboard
        if (Upgrade::tableExists($db, $prefix.'_webboard')) {
            // install Module & Menu
            $menu = isset($menus['forum']) ? $menus['forum'] : 'เว็บบอร์ด';
            $module_id = Installer::installing($db, 'board', 'forum', $menu, '', 1, 'MAINMENU', $menu);
            // หมวดหมู่ webboard
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_webboard_category` ORDER BY `id`") as $item) {
                $db->insert($_SESSION['prefix'].'_category', array(
                    'module_id' => $module_id,
                    'category_id' => $item->id,
                    'config' => serialize(array(
                        'img_upload_type' => array('jpg', 'jpeg'),
                        'img_upload_size' => 1024,
                        'img_law' => 0,
                        'can_reply' => array(0, 1),
                        'can_view' => array(-1, 0, 1),
                        'moderator' => array(1),
                    )),
                    'topic' => serialize(array('' => $item->category_name)),
                    'detail' => serialize(array('' => $item->category_name)),
                    'published' => 1,
                ));
            }
            // webboard
            $sql = "SELECT Q.*,M.`id` AS `member_id`,M.`nic_name`,M.`email` FROM `{$prefix}_webboard` AS Q";
            $sql .= " LEFT JOIN `{$prefix}_member` AS M ON M.`user`=Q.`post_name`";
            foreach ($db->customQuery($sql) as $item) {
                if ($item->picture != '' && Installer::copy($maxsite_url.'webboard_upload/'.rawurlencode($item->picture), DATA_FOLDER.'board/'.$item->id.'.jpg')) {
                    if (@getimagesize(ROOT_PATH.DATA_FOLDER.'board/'.$item->id.'.jpg') === false) {
                        unlink(ROOT_PATH.DATA_FOLDER.'board/'.$item->id.'.jpg');
                        $item->picture = '';
                    } else {
                        $item->picture = $item->id.'.jpg';
                    }
                } else {
                    $item->picture = '';
                }
                $db->insert($_SESSION['prefix'].'_board_q', array(
                    'id' => $item->id,
                    'module_id' => $module_id,
                    'category_id' => (int) $item->category,
                    'topic' => $item->topic,
                    'detail' => $item->detail,
                    'picture' => $item->picture,
                    'member_id' => (int) $item->member_id,
                    'sender' => empty($item->nic_name) ? $item->post_name : $item->nic_name,
                    'email' => empty($item->email) ? '' : $item->email,
                    'ip' => $item->ip_address,
                    'create_date' => $item->post_date,
                    'last_update' => $item->post_update,
                    'visited' => $item->pageview,
                ));
            }
            // ความคิดเห็น
            $topic_id = 0;
            $sql = "SELECT Q.*,M.`id` AS `member_id`,M.`nic_name`,M.`email` FROM `{$prefix}_webboard_comment` AS Q";
            $sql .= " LEFT JOIN `{$prefix}_member` AS M ON M.`user`=Q.`post_name`";
            $sql .= ' ORDER BY Q.`topic_id`,Q.`post_date` DESC';
            foreach ($db->customQuery($sql) as $item) {
                if ($item->picture != '' && Installer::copy($maxsite_url.'webboard_upload/'.rawurlencode($item->picture), DATA_FOLDER.'board/'.$item->post_date.'.jpg')) {
                    if (@getimagesize(ROOT_PATH.DATA_FOLDER.'board/'.$item->post_date.'.jpg') === false) {
                        unlink(ROOT_PATH.DATA_FOLDER.'board/'.$item->post_date.'.jpg');
                        $item->picture = '';
                    } else {
                        $item->picture = $item->post_date.'.jpg';
                    }
                } else {
                    $item->picture = '';
                }
                $comment_id = $db->insert($_SESSION['prefix'].'_board_r', array(
                    'module_id' => $module_id,
                    'index_id' => $item->topic_id,
                    'detail' => trim(htmlspecialchars(strip_tags(html_entity_decode($item->detail)))),
                    'picture' => $item->picture,
                    'ip' => $item->ip_address,
                    'last_update' => $item->post_date,
                    'member_id' => (int) $item->member_id,
                    'sender' => empty($item->nic_name) ? $item->post_name : $item->nic_name,
                ));
                if ($topic_id != $item->topic_id) {
                    $topic_id = $item->topic_id;
                    // อัปเดตคำตอบล่าสุด
                    $db->update($_SESSION['prefix'].'_board_q', $topic_id, array(
                        'comment_id' => $comment_id,
                        'commentator' => empty($item->nic_name) ? $item->post_name : $item->nic_name,
                        'commentator_id' => (int) $item->member_id,
                        'comment_date' => $item->post_date,
                    ));
                }
            }
            // อัปเดตหมวดหมู่ webboard
            Installer::updateBoardCategories($db, $module_id);
            // อัปเดตความคิดเห็น webboard
            Installer::updateComments($db, $_SESSION['prefix'].'_board_q', $_SESSION['prefix'].'_board_r');
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_webboard</b> complete...</li>';
        }
        // module personnel
        if (Upgrade::tableExists($db, $prefix.'_personnel')) {
            // install Module & Menu
            $menu = isset($menus['personnel']) ? $menus['personnel'] : 'บุคลากร';
            $module_id = Installer::installing($db, 'personnel', 'personnel', $menu, '', 1, 'SIDEMENU', $menu);
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_personnel_group` ORDER BY `gp_id`") as $item) {
                $db->insert($_SESSION['prefix'].'_category', array(
                    'module_id' => $module_id,
                    'category_id' => $item->gp_id,
                    'config' => '',
                    'topic' => serialize(array('th' => $item->gp_name, 'en' => $item->gp_name)),
                    'detail' => '',
                    'published' => 1,
                ));
            }
            $sql = "SELECT P.*,L.`g_id` FROM `{$prefix}_personnel` AS P";
            $sql .= " LEFT JOIN `{$prefix}_personnel_list` AS L ON L.`u_id`=P.`id`";
            $sql .= ' ORDER BY L.`g_id`,P.`sort`,L.`p_order`';
            $g_id = -1;
            $id = 1;
            foreach ($db->customQuery($sql) as $item) {
                if ($g_id != $item->g_id) {
                    $g_id = $item->g_id;
                    $order = 1;
                } else {
                    ++$order;
                }
                $db->insert($_SESSION['prefix'].'_personnel', array(
                    'id' => $id,
                    'module_id' => $module_id,
                    'category_id' => (int) $g_id,
                    'name' => $item->p_name,
                    'position' => $item->p_position,
                    'detail' => $item->p_data,
                    'address' => $item->p_add,
                    'phone' => $item->p_tel,
                    'email' => $item->p_mail,
                    'picture' => $id.'.jpg',
                    'order' => $order,
                ));
                if (Installer::copy($maxsite_url.'images/personnel/'.rawurlencode($item->p_pic), DATA_FOLDER.'personnel/'.$id.'.jpg')) {
                    if (@getimagesize(ROOT_PATH.DATA_FOLDER.'personnel/'.$id.'.jpg') === false) {
                        unlink(ROOT_PATH.DATA_FOLDER.'personnel/'.$id.'.jpg');
                    }
                }
                ++$id;
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_personnel</b> complete...</li>';
        }
        // module video
        if (Upgrade::tableExists($db, $prefix.'_video')) {
            // install Module & Menu
            $menu = isset($menus['video']) ? $menus['video'] : 'วีดีโอ';
            $module_id = Installer::installing($db, 'video', 'video', $menu, '', 1, 'SIDEMENU', $menu);
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_video` ORDER BY `sort`") as $item) {
                if ($item->youtube == 1) {
                    $db->insert($_SESSION['prefix'].'_video', array(
                        'module_id' => $module_id,
                        'topic' => $item->topic,
                        'description' => $item->detail,
                        'youtube' => $item->video,
                        'last_update' => $item->post_date,
                        'views' => $item->times,
                    ));
                    //$item->pic
                }
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_video</b> complete...</li>';
        }
        // module gallery
        if (Upgrade::tableExists($db, $prefix.'_gallery')) {
            // install Module & Menu
            $menu = isset($menus['gallery']) ? $menus['gallery'] : 'แกลอรี่';
            $module_id = Installer::installing($db, 'gallery', 'gallery', $menu, '', 1, 'MAINMENU', $menu);
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_gallery_category` ORDER BY `id`") as $item) {
                $db->insert($_SESSION['prefix'].'_gallery_album', array(
                    'module_id' => $module_id,
                    'id' => $item->id,
                    'topic' => trim(htmlspecialchars(strip_tags($item->category_name))),
                    'detail' => trim(htmlspecialchars(strip_tags(html_entity_decode($item->category_detail)))),
                    'last_update' => $item->post_date,
                    'count' => 0,
                    'visited' => 0,
                ));
                File::makeDirectory(ROOT_PATH.DATA_FOLDER.'gallery/'.$item->id.'/');
            }
            $sql = "SELECT P.*,L.`post_date` AS `src` FROM `{$prefix}_gallery` AS P";
            $sql .= " INNER JOIN `{$prefix}_gallery_category` AS L ON L.`id`=P.`category`";
            $sql .= ' ORDER BY P.`category`,P.`id`';
            foreach ($db->customQuery($sql) as $item) {
                $db->insert($_SESSION['prefix'].'_gallery', array(
                    'id' => $item->id,
                    'module_id' => $module_id,
                    'album_id' => (int) $item->category,
                    'last_update' => $item->post_date,
                    'image' => $item->id.'.jpg',
                    'count' => 0,
                ));
                $dir = DATA_FOLDER.'gallery/'.(int) $item->category.'/';
                if (Installer::copy($maxsite_url.'images/gallery/gal_'.$item->src.'/'.$item->pic, $dir.$item->id.'.jpg')) {
                    if (@getimagesize(ROOT_PATH.$dir.$item->id.'.jpg') === false) {
                        unlink(ROOT_PATH.$dir.$item->id.'.jpg');
                    }
                }
                if (Installer::copy($maxsite_url.'images/gallery/gal_'.$item->src.'/thb_'.$item->pic, $dir.'thumb_'.$item->id.'.jpg')) {
                    if (@getimagesize(ROOT_PATH.$dir.'thumb_'.$item->id.'.jpg') === false) {
                        unlink(ROOT_PATH.$dir.'thumb_'.$item->id.'.jpg');
                    }
                }
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_gallery</b> complete...</li>';
        }
        // module download
        if (Upgrade::tableExists($db, $prefix.'_download')) {
            $menu = isset($menus['download']) ? $menus['download'] : 'ดาวน์โหลด';
            $module_id = Installer::installing($db, 'download', 'download', $menu, '', 1, 'SIDEMENU', $menu);
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_download_category` ORDER BY `id`") as $item) {
                $db->insert($_SESSION['prefix'].'_category', array(
                    'module_id' => $module_id,
                    'category_id' => (int) $item->id,
                    'config' => '',
                    'topic' => serialize(array('th' => $item->category_name, 'en' => $item->category_name)),
                    'detail' => '',
                    'published' => 1,
                ));
            }
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_download` ORDER BY `id`") as $item) {
                if (preg_match('/(.*)\.([a-z0-9]{2,5})$/', $item->full_text, $match)) {
                    $ext = strtolower($match[2]);
                    if (Installer::copy($maxsite_url.'data/download_'.$item->full_text, DATA_FOLDER.'download/'.$item->id.'.'.$ext)) {
                        $item->full_text = DATA_FOLDER.'download/'.$item->id.'.'.$ext;
                        $item->size = filesize(ROOT_PATH.$item->full_text);
                    } else {
                        $item->size = 0;
                    }
                    $db->insert($_SESSION['prefix'].'_download', array(
                        'module_id' => $module_id,
                        'category_id' => (int) $item->category,
                        'member_id' => 1,
                        'detail' => trim(htmlspecialchars(strip_tags(html_entity_decode($item->detail)))),
                        'last_update' => (int) $item->update_date,
                        'name' => trim(htmlspecialchars(strip_tags($item->topic))),
                        'ext' => $ext,
                        'size' => (int) $item->size,
                        'file' => $item->full_text,
                        'downloads' => (int) $item->pageview,
                        'reciever' => serialize(array(-1, 0, 1)),
                    ));
                }
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_download</b> complete...</li>';
        }
        // module event
        if (Upgrade::tableExists($db, $prefix.'_event')) {
            // install Module & Menu
            $menu = isset($menus['event']) ? $menus['event'] : 'Event';
            $module_id = Installer::installing($db, 'event', 'event', $menu, '', 1, 'SIDEMENU', $menu);
            foreach ($db->customQuery("SELECT * FROM `{$prefix}_calendar` ORDER BY `id`") as $item) {
                $title = trim(htmlspecialchars(strip_tags($item->subject)));
                if (preg_match('/.*?([0-9]{1,2}:[0-9]{1,2}).*?/', $item->timeout, $match)) {
                    $item->date_event = $item->date_event.' '.$match[1].':00';
                } else {
                    $item->date_event = $item->date_event.' 00:00:00';
                }
                $db->insert($_SESSION['prefix'].'_eventcalendar', array(
                    'module_id' => $module_id,
                    'begin_date' => $item->date_event,
                    'end_date' => '0000-00-00 00:00:00',
                    'topic' => $title,
                    'keywords' => Text::oneLine($title),
                    'detail' => $item->detail,
                    'description' => Text::oneLine(strip_tags($item->detail), 255),
                    'last_update' => (int) $item->post_date,
                    'published_date' => date('Y-m-d', $item->post_date),
                    'member_id' => 1,
                    'published' => 1,
                ));
            }
            $content[] = '<li class="correct">Import from <b>'.$prefix.'_event</b> complete...</li>';
        }
        // เมนูเข้าระบบผู้ดูแล
        Installer::installMenu($db, 'SIDEMENU', 'เข้าระบบผู้ดูแล', WEB_URL.'admin/index.php', '_blank', 1, 0);
        $content[] = '<li class="correct">Installed <b>Menu</b> complete...</li>';
        // คืนค่า
        return implode('', $content);
    }
}
