<?php
/**
 * install/step2.php — เลือกประเภทเว็บไซต์ (ขั้นตอนการติดตั้ง)
 *
 * ประเภทอ่านจาก install/seeds/<type>/info.php (ดู install/seeds/README.md)
 * ค่าที่เลือกถูกเก็บใน $_SESSION['site_type'] / $_SESSION['with_sample'] ที่
 * step3.php แล้วนำไปใช้จริงตอนติดตั้งที่ step5.php (applySiteType())
 *
 * ขั้นตอนทั้งหมด : step0 ตรวจเซิร์ฟเวอร์ → step1 ตรวจโฟลเดอร์ → step2 ประเภทเว็บไซต์
 * → step3 ผู้ดูแลระบบ → step4 ฐานข้อมูล → step5 ติดตั้ง
 */
if (defined('ROOT_PATH')) {
    include_once ROOT_PATH.'install/seeds.php';
    $types = siteTypes();
    $selected = isset($_SESSION['site_type']) ? (string) $_SESSION['site_type'] : '';
    if (!isset($types[$selected])) {
        $selected = empty($types) ? '' : (string) key($types);
    }
    // ติ๊กไว้ให้เป็นค่าเริ่มต้น — เว็บใหม่ที่มีเนื้อหาตัวอย่างเปิดดูและทดสอบได้ทันที
    $with_sample = isset($_SESSION['with_sample']) ? (bool) $_SESSION['with_sample'] : true;
    echo '<form method=post action=index.php autocomplete=off>';
    echo '<h2>ประเภทเว็บไซต์</h2>';
    if (!empty($site_type_warning)) {
        echo '<p class=warning>'.$site_type_warning.'</p>';
    }
    if (empty($types)) {
        echo '<p class=warning>ไม่พบประเภทเว็บไซต์ในชุดติดตั้ง (<em>install/seeds/</em>) '
            .'ระบบจะติดตั้งด้วยธีมและค่ากำหนดเริ่มต้น</p>';
        echo '<input type=hidden name=site_type value="">';
    } else {
        echo '<p>เลือกแบบที่ใกล้กับเว็บไซต์ของคุณที่สุด ตัวติดตั้งจะตั้งธีมให้ตรงกับประเภท '
            .'เปลี่ยนธีมและแก้ไขเนื้อหาได้ภายหลังจากหน้าผู้ดูแลระบบ</p>';
        echo '<div class=sitetypes>';
        foreach ($types as $key => $info) {
            $id = 'site_type_'.$key;
            $shot = siteTypeScreenshot($info['skin']);
            echo '<label class=sitetype for='.$id.'>';
            echo '<input type=radio name=site_type id='.$id.' value="'.htmlspecialchars($key, ENT_QUOTES).'"'
                .($key === $selected ? ' checked' : '').'>';
            echo '<span class=sitetype-card>';
            if ($shot !== '') {
                echo '<img src="../'.htmlspecialchars($shot, ENT_QUOTES).'" alt="ตัวอย่างธีม '
                    .htmlspecialchars($info['skin'], ENT_QUOTES).'" loading=lazy>';
            } else {
                echo '<span class=sitetype-noshot>ไม่มีรูปตัวอย่าง</span>';
            }
            echo '<b>'.htmlspecialchars($info['label'], ENT_QUOTES).'</b>';
            if ($info['description'] !== '') {
                echo '<span class=sitetype-detail>'.htmlspecialchars($info['description'], ENT_QUOTES).'</span>';
            }
            $meta = [];
            if ($info['skin'] !== '') {
                $meta[] = 'ธีม '.htmlspecialchars($info['skin'], ENT_QUOTES);
            }
            if ((int) $info['columns'] > 0) {
                $meta[] = (int) $info['columns'].' คอลัมน์';
            }
            if (!$info['has_seed']) {
                $meta[] = 'ยังไม่มีข้อมูลตัวอย่าง';
            }
            echo '<span class=sitetype-meta>'.implode(' · ', $meta).'</span>';
            echo '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '<p class=item><label for=with_sample>';
        echo '<input type=checkbox id=with_sample name=with_sample value=1'.($with_sample ? ' checked' : '').'> ';
        echo 'ติดตั้งข้อมูลตัวอย่าง</label></p>';
        echo '<p class=comment>เมนู หน้าเว็บ ข่าว รูปภาพ และแบนเนอร์ตัวอย่างของประเภทที่เลือก '
            .'ไว้ดูหน้าตาและทดลองใช้งาน ลบหรือแก้ไขได้ภายหลัง (ไม่เลือก = ได้เว็บเปล่าพร้อมธีมของประเภทนั้น)</p>';
    }
    echo '<input type=hidden name=step value=3>';
    echo '<p class="submit"><button class="btn large btn-primary" type=submit>ดำเนินการต่อ</button></p>';
    echo '</form>';
}
