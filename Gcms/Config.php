<?php
/**
 * @filesource Gcms/Config.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

/**
 * Config Class สำหรับ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Config extends \Kotchasan\Config
{
    /**
     * กำหนดอายุของแคช (วินาที)
     * 0 หมายถึงไม่มีการใช้งานแคช
     *
     * @var int
     */
    public $cache_expire = 5;
    /**
     * สีของสมาชิกตามสถานะ
     *
     * @var array
     */
    public $color_status = array(
        0 => '#336600',
        1 => '#FF0000'
    );
    /**
     * จำนวนหลักของตัวนับคนเยี่ยมชม
     *
     * @var int
     */
    public $counter_digit = 4;
    /**
     * prefix ของ database
     *
     * @var bool default false
     */
    public $demo_mode = false;
    /**
     * @var int
     */
    public $document_cols = 1;
    /**
     * @var int
     */
    public $document_rows = 20;
    /**
     * การแสดงผลบทความสำหรับหน้าแสดงรายการตามวันที่ และ Tags
     */
    public $document_style = 'iconview';
    /**
     * รายชื่อฟิลด์จากตารางสมาชิก สำหรับตรวจสอบการ login
     *
     * @var array
     */
    public $login_fields = array('email', 'phone1');
    /**
     * บัตรประชาชน
     *
     * @var int
     */
    public $member_idcard = 0;
    /**
     * โทรศัพท์
     *
     * @var int
     */
    public $member_phone = 0;
    /**
     * ชื่อสงวน ไม่อนุญาติให้ตั้งเป็นชื่อสมาชิก
     *
     * @var array
     */
    public $member_reserv = array(
        'website',
        'webmaster',
        'cms',
        'gcms',
        'module',
        'website',
        'member',
        'members',
        'register',
        'edit',
        'forgot'
    );
    /**
     * สถานะสมาชิก
     * 0 สมาชิกทั่วไป
     * 1 ผู้ดูแลระบบ
     *
     * @var array
     */
    public $member_status = array(
        0 => 'Member',
        1 => 'Administrator'
    );
    /**
     * กำหนดรูปแบบของ URL ที่สร้างจากระบบ
     * ตามที่กำหนดโดย \Settings->urls
     *
     * @var int
     */
    public $module_url = 0;
    /**
     * สถานะของสมาชิก เมื่อมีการสมัครสมาชิกใหม่
     *
     * @var int
     */
    public $new_register_status = 0;
    /**
     * ถ้าเป็น true จะไม่แสดงข่าวสารจาก GCMS และการแจ้งเตือนการอัปเดต
     * สำหรับสคริปต์ที่ขาย
     *
     * @var bool
     */
    public $production = false;
    /**
     * template ที่กำลังใช้งานอยู่ (ชื่อโฟลเดอร์)
     *
     * @var string
     */
    public $skin = 'rooster';
    /**
     * @var int
     */
    public $use_ajax = 0;
    /**
     * สมาชิกใหม่ต้องยืนยันอีเมล
     *
     * @var bool
     */
    public $user_activate = true;
    /**
     * ความสูงสูงสุดของรูปประจำตัวสมาชิก
     *
     * @var int
     */
    public $user_icon_h = 50;
    /**
     * ชนิดของรูปถาพที่สามารถอัปโหลดเป็นรูปประจำตัวสมาชิก ได้
     *
     * @var array
     */
    public $user_icon_typies = array('jpg', 'jpeg', 'gif', 'png');
    /**
     * ความกว้างสูงสุดของรูปประจำตัวสมาชิก
     *
     * @var int
     */
    public $user_icon_w = 50;
    /**
     * ไดเร็คทอรี่เก็บ icon สมาชิก
     *
     * @var string
     */
    public $usericon_folder = 'datas/member/';
    /**
     * ช่วงเวลาจำการเข้าระบบ
     * 86400 = 1 วัน
     *
     * @var int
     */
    public $remember_expired = 2592000;
    /**
     * คำอธิบายเกี่ยวกับเว็บไซต์
     *
     * @var string
     */
    public $web_description = 'ระบบบริหารจัดการเว็บไซต์ (CMS) ด้วย Ajax โดยคนไทย';
    /**
     * ชื่อเว็บไซต์
     *
     * @var string
     */
    public $web_title = 'GCMS Ajax CMS';
    /**
     * รายการคำหยาบ
     *
     * @var array
     */
    public $wordrude = array(
        'ashole',
        'a s h o l e',
        'a.s.h.o.l.e',
        'bitch',
        'b i t c h',
        'b.i.t.c.h',
        'shit',
        's h i t',
        's.h.i.t',
        'fuck',
        'dick',
        'f u c k',
        'd i c k',
        'f.u.c.k',
        'd.i.c.k',
        'มึ ง',
        'ม ึ ง',
        'ม ึง',
        'มงึ',
        'มึ.ง',
        'มึ_ง',
        'มึ-ง',
        'มึ+ง',
        'ค ว ย',
        'ค.ว.ย',
        'คอ วอ ยอ',
        'คอ-วอ-ยอ',
        'ไอ้เหี้ย',
        'เฮี้ย',
        'ชาติหมา',
        'ชาดหมา',
        'ช า ด ห ม า',
        'ช.า.ด.ห.ม.า',
        'ช า ติ ห ม า',
        'ช.า.ติ.ห.ม.า',
        'สัดหมา',
        'สันดาน',
        'ระยำ',
        'ส้นตีน'
    );
    /**
     * ข้อความแทนที่คำหยาบ
     *
     * @var string
     */
    public $wordrude_replace = 'xxx';
    /**
     * แท็บบทความในเมนูข้อมูลส่วนตัว
     *
     * @var bool
     */
    public $document_can_write = true;
    /**
     * Theme Color (PWA)
     *
     * @var string
     */
    public $theme_color = '#006EA0';
    /**
     * เวลาหมดอายุของ Token ในกระบวนการ login (วินาที)
     * 3600 = 1 ชม.
     *
     * @var int
     */
    public $token_login_expire_time = 1800;
}
