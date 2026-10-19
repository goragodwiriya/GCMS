<?php
/**
 * @filesource install/seeds/shop/content.php
 *
 * ข้อมูลตัวอย่างของประเภท "เว็บไซต์ร้านค้าออนไลน์ (e-commerce)" (ธีม shop)
 * ร้านของใช้ ของแต่งบ้าน แฟชั่น และของฝากสมมุติ — ชื่อร้านมาจาก {SITE_NAME}
 * สินค้า ราคา บัญชีธนาคาร และข้อมูลติดต่อทั้งหมดเป็นค่าสมมุติ
 *
 * แก้ไฟล์นี้แล้วสร้าง seed.sql / images.json / datas/ ใหม่ด้วย
 *   php install/seeds/build.php shop
 *
 * หมวดสินค้า category_id = 1 คือ "สินค้าแนะนำ" (carousel หน้าแรกของธีม shop)
 */

$site = '{SITE_NAME}';
$sample = 'คลิปตัวอย่างสำหรับทดสอบโมดูลวิดีโอ (ภาพยนตร์เปิดของ Blender Foundation สัญญาอนุญาต Creative Commons) แทนที่ด้วยวิดีโอรีวิวสินค้าของร้านได้ที่หน้าผู้ดูแล';

// รายละเอียดสินค้าแบบย่อ (th/en) — หัวข้อ คำอธิบาย และรายละเอียด HTML
$d = function ($topic, $description, array $points, $note = '') {
    $html = '<p>'.$description.'</p><ul>';
    foreach ($points as $point) {
        $html .= '<li>'.$point.'</li>';
    }
    $html .= '</ul>'.($note === '' ? '' : '<p>'.$note.'</p>');

    return ['topic' => $topic, 'description' => $description, 'detail' => $html, 'keywords' => $topic];
};

return [
    'theme' => [
        // สีของรูปตัวอย่าง (โทนแดง-เหลืองของธีม shop)
        'palette' => ['#cc0000', '#ffb81c', '#1f2937', '#0ea5e9', '#16a34a'],
        'label' => 'shop',
        'document_label' => 'ร้านตัวอย่างช้อป'
    ],

    'modules' => [
        // ---------------------------------------------------------------
        // หน้าแรก
        // ---------------------------------------------------------------
        'home' => [
            'owner' => 'index',
            'topic' => 'หน้าหลัก',
            'description' => $site.' ร้านค้าออนไลน์ของใช้ ของแต่งบ้าน แฟชั่น และของฝาก คัดสรรคุณภาพ จัดส่งทั่วประเทศ',
            'keywords' => 'ร้านค้าออนไลน์,ของใช้,ของแต่งบ้าน,ของฝาก,'.$site,
            'visited' => 3120,
            'detail' => '<h2>ยินดีต้อนรับสู่ '.$site.'</h2>'
                .'<p>เราคัดสรรของใช้ในชีวิตประจำวัน ของแต่งบ้าน และของฝากจากผู้ผลิตท้องถิ่น <strong>คุณภาพดี ราคายุติธรรม</strong> '
                .'สั่งง่าย ชำระเงินได้หลายช่องทาง และจัดส่งทั่วประเทศ</p>'
                .'<ul>'
                .'<li><a href="{WEBURL}product">สินค้าทั้งหมด</a> — เลือกชมตามหมวดหมู่ ขนาด และสี</li>'
                .'<li><a href="{WEBURL}news">โปรโมชัน</a> — ส่วนลดและกิจกรรมพิเศษประจำเดือน</li>'
                .'<li><a href="{WEBURL}howto">วิธีสั่งซื้อ</a> — ขั้นตอนสั่งซื้อ ชำระเงิน และติดตามพัสดุ</li>'
                .'</ul>'
                .'<p><em>สินค้าและราคาทั้งหมดเป็นข้อมูลตัวอย่าง แก้ไขได้ที่หน้าผู้ดูแล</em></p>'
        ],

        // ---------------------------------------------------------------
        // สินค้า
        // ---------------------------------------------------------------
        'product' => [
            'owner' => 'product',
            'topic' => 'สินค้าทั้งหมด',
            'description' => 'สินค้าของใช้ ของแต่งบ้าน แฟชั่น อาหาร และอุปกรณ์ไอทีจาก'.$site,
            'keywords' => 'สินค้า,ร้านค้าออนไลน์,ของใช้,แฟชั่น',
            'visited' => 1860,
            'detail' => '<p>เลือกซื้อสินค้าได้ตามหมวดหมู่ สินค้าที่มีตัวเลือกให้เลือกขนาดหรือสีได้ในหน้าสินค้า</p>',
            // ค่าเริ่มต้นจาก Product\Settings\Controller::defaultSettings() + ข้อมูลร้าน (ตัวอย่าง)
            // rows x cols = จำนวนสินค้าต่อหน้า (ไม่ระบุ = 4 x 5)
            'config' => [
                'store_name' => $site,
                'bank_info' => 'ธนาคารตัวอย่าง สาขาตัวอย่าง เลขที่บัญชี 000-0-00000-0 ชื่อบัญชี '.$site.' (ข้อมูลตัวอย่าง แก้ไขก่อนเปิดร้าน)',
                'order_prefix' => 'SO',
                'rows' => 3,
                'cols' => 4
            ],
            'categories' => [
                1 => ['th' => 'สินค้าแนะนำ', 'en' => 'Featured'],
                2 => ['th' => 'เสื้อผ้าและแฟชั่น', 'en' => 'Fashion'],
                3 => ['th' => 'ของใช้ในบ้าน', 'en' => 'Home & Living'],
                4 => ['th' => 'อาหารและของฝาก', 'en' => 'Food & Gifts'],
                5 => ['th' => 'อุปกรณ์ไอที', 'en' => 'Gadgets']
            ],
            'attributes' => [
                'size' => ['name' => ['th' => 'ขนาด', 'en' => 'Size'], 'values' => [
                    's' => ['th' => 'S', 'en' => 'S'], 'm' => ['th' => 'M', 'en' => 'M'], 'l' => ['th' => 'L', 'en' => 'L'], 'xl' => ['th' => 'XL', 'en' => 'XL']
                ]],
                'color' => ['name' => ['th' => 'สี', 'en' => 'Color'], 'values' => [
                    'white' => ['th' => 'ขาว', 'en' => 'White'], 'navy' => ['th' => 'กรมท่า', 'en' => 'Navy'], 'cream' => ['th' => 'ครีม', 'en' => 'Cream'],
                    'black' => ['th' => 'ดำ', 'en' => 'Black'], 'green' => ['th' => 'เขียว', 'en' => 'Green'], 'pink' => ['th' => 'ชมพู', 'en' => 'Pink'],
                    'blue' => ['th' => 'ฟ้า', 'en' => 'Blue']
                ]],
                'shoe' => ['name' => ['th' => 'ไซซ์รองเท้า', 'en' => 'Shoe size'], 'values' => [
                    '39' => ['th' => '39', 'en' => '39'], '40' => ['th' => '40', 'en' => '40'], '41' => ['th' => '41', 'en' => '41'], '42' => ['th' => '42', 'en' => '42']
                ]]
            ],
            'shipping' => [
                ['name' => ['th' => 'ไปรษณีย์ลงทะเบียน', 'en' => 'Registered Mail'], 'calc_type' => 'flat', 'base_rate' => 50, 'free_over' => 1000],
                ['name' => ['th' => 'ขนส่งด่วนเอกชน', 'en' => 'Express Delivery'], 'calc_type' => 'by_weight', 'base_rate' => 40, 'rate_per_kg' => 15],
                ['name' => ['th' => 'รับสินค้าที่ร้าน', 'en' => 'Store Pickup'], 'calc_type' => 'free']
            ],
            'products' => [
                // ---------------- สินค้าแนะนำ (category 1) ----------------
                [
                    'sku' => 'MUG-001', 'alias' => 'classic-ceramic-mug', 'category' => 1, 'featured' => 1, 'price' => 290, 'weight' => 0.35,
                    'stock' => 48, 'cost' => 140, 'days_ago' => 3, 'visited' => 412,
                    'images' => [['shape' => 'mug', 'color' => '#CC0000'], ['shape' => 'mug', 'color' => '#1F2937', 'caption' => 'แก้วมัคเซรามิก สีเทาเข้ม']],
                    'th' => $d('แก้วมัคเซรามิก รุ่นคลาสสิก', 'แก้วมัคเซรามิกเคลือบด้าน จับถนัดมือ เก็บความร้อนได้ดี ขนาด 350 มล.', ['ความจุ 350 มล.', 'เข้าไมโครเวฟและเครื่องล้างจานได้', 'ผลิตจากเซรามิกปลอดสารตะกั่ว']),
                    'en' => $d('Classic Ceramic Mug', 'Matte-glazed 350 ml ceramic mug that keeps drinks hot.', ['350 ml capacity', 'Microwave and dishwasher safe', 'Lead-free ceramic'])
                ],
                [
                    'sku' => 'TSH-001', 'alias' => 'organic-cotton-tshirt', 'category' => 1, 'featured' => 1, 'weight' => 0.2, 'days_ago' => 5, 'visited' => 530, 'cost' => 180,
                    'images' => [['shape' => 'shirt', 'color' => '#F3F4F6', 'caption' => 'เสื้อยืดคอตตอน สีขาว'], ['shape' => 'shirt', 'color' => '#1E3A8A', 'caption' => 'เสื้อยืดคอตตอน สีกรมท่า']],
                    'variants' => [
                        ['values' => ['size' => 's', 'color' => 'white'], 'price' => 390, 'stock' => 12, 'image' => 0],
                        ['values' => ['size' => 'm', 'color' => 'white'], 'price' => 390, 'stock' => 18, 'image' => 0],
                        ['values' => ['size' => 'l', 'color' => 'white'], 'price' => 390, 'stock' => 10, 'image' => 0],
                        ['values' => ['size' => 'xl', 'color' => 'white'], 'price' => 420, 'stock' => 6, 'image' => 0],
                        ['values' => ['size' => 's', 'color' => 'navy'], 'price' => 390, 'stock' => 8, 'image' => 1],
                        ['values' => ['size' => 'm', 'color' => 'navy'], 'price' => 390, 'stock' => 14, 'image' => 1],
                        ['values' => ['size' => 'l', 'color' => 'navy'], 'price' => 390, 'stock' => 0, 'image' => 1],
                        ['values' => ['size' => 'xl', 'color' => 'navy'], 'price' => 420, 'stock' => 4, 'image' => 1]
                    ],
                    'th' => $d('เสื้อยืดคอตตอนออร์แกนิก', 'เสื้อยืดผ้าคอตตอนออร์แกนิก 100% เนื้อนุ่ม ระบายอากาศดี มีให้เลือก 4 ขนาด 2 สี', ['ผ้าคอตตอนออร์แกนิก 100%', 'ทรงตรง ใส่ได้ทั้งชายและหญิง', 'ขนาด XL ราคา 420 บาท'], 'ดูตารางขนาดได้ที่หน้า <a href="{WEBURL}howto">วิธีสั่งซื้อ</a>'),
                    'en' => $d('Organic Cotton T-shirt', '100% organic cotton tee, soft and breathable. 4 sizes, 2 colours.', ['100% organic cotton', 'Unisex straight fit', 'Size XL costs 420 THB'])
                ],
                [
                    'sku' => 'BAG-001', 'alias' => 'canvas-tote-bag', 'category' => 1, 'featured' => 1, 'weight' => 0.3, 'days_ago' => 8, 'visited' => 377, 'cost' => 150,
                    'images' => [['shape' => 'bag', 'color' => '#E7D8B8', 'caption' => 'กระเป๋าผ้าแคนวาส สีครีม'], ['shape' => 'bag', 'color' => '#1F2937', 'caption' => 'กระเป๋าผ้าแคนวาส สีดำ'], ['shape' => 'bag', 'color' => '#16A34A', 'caption' => 'กระเป๋าผ้าแคนวาส สีเขียว']],
                    'variants' => [
                        ['values' => ['color' => 'cream'], 'price' => 350, 'stock' => 25, 'image' => 0],
                        ['values' => ['color' => 'black'], 'price' => 350, 'stock' => 20, 'image' => 1],
                        ['values' => ['color' => 'green'], 'price' => 350, 'sale_price' => 299, 'stock' => 15, 'image' => 2]
                    ],
                    'th' => $d('กระเป๋าผ้าแคนวาส', 'กระเป๋าผ้าแคนวาสหนา 16 ออนซ์ ใส่ของได้เยอะ มีช่องซิปด้านใน', ['ผ้าแคนวาส 16 ออนซ์', 'ขนาด 38 x 40 ซม.', 'สีเขียวลดราคาพิเศษ 299 บาท']),
                    'en' => $d('Canvas Tote Bag', 'Heavy 16 oz canvas tote with an inner zip pocket.', ['16 oz canvas', '38 x 40 cm', 'Green on sale at 299 THB'])
                ],
                [
                    'sku' => 'BTL-001', 'alias' => 'insulated-bottle-500ml', 'category' => 1, 'featured' => 1, 'weight' => 0.4, 'days_ago' => 10, 'visited' => 298, 'cost' => 210,
                    'images' => [['shape' => 'bottle', 'color' => '#0EA5E9', 'caption' => 'ขวดน้ำสแตนเลส สีฟ้า'], ['shape' => 'bottle', 'color' => '#1F2937', 'caption' => 'ขวดน้ำสแตนเลส สีดำ'], ['shape' => 'bottle', 'color' => '#EC4899', 'caption' => 'ขวดน้ำสแตนเลส สีชมพู']],
                    'variants' => [
                        ['values' => ['color' => 'blue'], 'price' => 450, 'stock' => 30, 'image' => 0],
                        ['values' => ['color' => 'black'], 'price' => 450, 'stock' => 22, 'image' => 1],
                        ['values' => ['color' => 'pink'], 'price' => 450, 'sale_price' => 399, 'stock' => 12, 'image' => 2]
                    ],
                    'th' => $d('ขวดน้ำสแตนเลสเก็บอุณหภูมิ 500 มล.', 'ขวดน้ำสแตนเลสสองชั้น เก็บความเย็นได้ 24 ชั่วโมง ความร้อน 12 ชั่วโมง', ['สแตนเลส 304 ปลอด BPA', 'ความจุ 500 มล.', 'ฝาปิดกันรั่ว']),
                    'en' => $d('Insulated Bottle 500 ml', 'Double-wall stainless bottle: cold for 24 h, hot for 12 h.', ['304 stainless steel, BPA free', '500 ml', 'Leak-proof lid'])
                ],
                [
                    'sku' => 'LMP-001', 'alias' => 'minimal-desk-lamp', 'category' => 1, 'featured' => 1, 'price' => 890, 'weight' => 1.2,
                    'stock' => 9, 'cost' => 520, 'days_ago' => 14, 'visited' => 188,
                    'images' => [['shape' => 'lamp', 'color' => '#FFB81C']],
                    'th' => $d('โคมไฟตั้งโต๊ะมินิมอล', 'โคมไฟตั้งโต๊ะดีไซน์เรียบ ปรับความสว่างได้ 3 ระดับ เหมาะกับโต๊ะทำงานและหัวเตียง', ['หลอด LED ประหยัดไฟ', 'ปรับแสง 3 ระดับ', 'รับประกัน 1 ปี']),
                    'en' => $d('Minimal Desk Lamp', 'Clean-lined desk lamp with 3 brightness levels.', ['Energy-saving LED', '3 brightness levels', '1-year warranty'])
                ],
                [
                    'sku' => 'PLT-001', 'alias' => 'air-purifying-plant', 'category' => 1, 'featured' => 1, 'price' => 320, 'weight' => 1.5,
                    'stock' => 16, 'cost' => 150, 'days_ago' => 18, 'visited' => 241,
                    'images' => [['shape' => 'plant', 'color' => '#F3F4F6']],
                    'th' => $d('ต้นไม้ฟอกอากาศในกระถางเซรามิก', 'ต้นไม้ฟอกอากาศดูแลง่าย มาพร้อมกระถางเซรามิกสีขาว เหมาะวางในห้องทำงาน', ['สูงประมาณ 35 ซม.', 'รดน้ำสัปดาห์ละครั้ง', 'แพ็กกันกระแทกอย่างดี']),
                    'en' => $d('Air-purifying Plant', 'Easy-care plant in a white ceramic pot.', ['About 35 cm tall', 'Water once a week', 'Carefully packed'])
                ],
                [
                    'sku' => 'HPH-001', 'alias' => 'wireless-earbuds-basic', 'category' => 1, 'featured' => 1, 'price' => 1290, 'weight' => 0.15,
                    'stock' => 20, 'cost' => 780, 'days_ago' => 22, 'visited' => 356,
                    'images' => [['shape' => 'headphone', 'color' => '#1F2937']],
                    'th' => $d('หูฟังไร้สาย รุ่นเบสิก', 'หูฟังไร้สายเสียงใส ใช้งานต่อเนื่อง 6 ชั่วโมง พร้อมกล่องชาร์จ', ['บลูทูธ 5.3', 'ใช้งานต่อเนื่อง 6 ชั่วโมง', 'รับประกัน 6 เดือน']),
                    'en' => $d('Wireless Earbuds Basic', 'Clear-sound wireless earbuds, 6 hours playback with charging case.', ['Bluetooth 5.3', '6 h playback', '6-month warranty'])
                ],
                [
                    'sku' => 'HNY-001', 'alias' => 'longan-honey-250g', 'category' => 1, 'featured' => 1, 'price' => 180, 'weight' => 0.35,
                    'manage_stock' => 0, 'days_ago' => 26, 'visited' => 205,
                    'images' => [['shape' => 'jar', 'color' => '#F59E0B']],
                    'th' => $d('น้ำผึ้งดอกลำไยแท้ 250 กรัม', 'น้ำผึ้งแท้จากสวนลำไยของเกษตรกรในชุมชน หอมหวานกำลังดี', ['ขนาด 250 กรัม', 'ไม่ผสมน้ำเชื่อม', 'สินค้าชุมชน']),
                    'en' => $d('Longan Honey 250 g', 'Pure honey from community longan orchards.', ['250 g', 'No added syrup', 'Community product'])
                ],
                // ---------------- เสื้อผ้าและแฟชั่น (category 2) ----------------
                [
                    'sku' => 'CAP-001', 'alias' => 'cotton-cap', 'category' => 2, 'weight' => 0.1, 'days_ago' => 30, 'visited' => 97, 'cost' => 110,
                    'images' => [['shape' => 'cap', 'color' => '#1F2937', 'caption' => 'หมวกแก๊ป สีดำ'], ['shape' => 'cap', 'color' => '#E7D8B8', 'caption' => 'หมวกแก๊ป สีครีม']],
                    'variants' => [
                        ['values' => ['color' => 'black'], 'price' => 250, 'stock' => 18, 'image' => 0],
                        ['values' => ['color' => 'cream'], 'price' => 250, 'stock' => 11, 'image' => 1]
                    ],
                    'th' => $d('หมวกแก๊ปผ้าฝ้าย', 'หมวกแก๊ปผ้าฝ้ายปรับขนาดได้ด้านหลัง ใส่สบายทุกวัน', ['ผ้าฝ้าย 100%', 'สายปรับขนาดด้านหลัง']),
                    'en' => $d('Cotton Cap', 'Adjustable cotton cap for everyday wear.', ['100% cotton', 'Adjustable strap'])
                ],
                [
                    'sku' => 'SHO-001', 'alias' => 'casual-sneakers', 'category' => 2, 'weight' => 0.8, 'days_ago' => 34, 'visited' => 164, 'cost' => 560,
                    'images' => [['shape' => 'shoe', 'color' => '#F3F4F6']],
                    'variants' => [
                        ['values' => ['shoe' => '39'], 'price' => 990, 'stock' => 4],
                        ['values' => ['shoe' => '40'], 'price' => 990, 'stock' => 7],
                        ['values' => ['shoe' => '41'], 'price' => 990, 'stock' => 6],
                        ['values' => ['shoe' => '42'], 'price' => 990, 'stock' => 3]
                    ],
                    'th' => $d('รองเท้าผ้าใบลำลอง', 'รองเท้าผ้าใบน้ำหนักเบา พื้นยางกันลื่น ใส่เดินได้ทั้งวัน', ['ไซซ์ 39-42', 'พื้นยางกันลื่น', 'เปลี่ยนไซซ์ได้ภายใน 7 วัน']),
                    'en' => $d('Casual Sneakers', 'Lightweight sneakers with a non-slip rubber sole.', ['Sizes 39-42', 'Non-slip sole', 'Size exchange within 7 days'])
                ],
                [
                    'sku' => 'TSH-002', 'alias' => 'limited-graphic-tee', 'category' => 2, 'price' => 490, 'weight' => 0.2,
                    'stock' => 0, 'days_ago' => 40, 'visited' => 322,
                    'images' => [['shape' => 'shirt', 'color' => '#CC0000']],
                    'th' => $d('เสื้อยืดลายกราฟิก รุ่นลิมิเต็ด', 'เสื้อยืดลายกราฟิกผลิตจำนวนจำกัด (สินค้าหมด — ตัวอย่างสินค้าที่ไม่มีสต็อก)', ['ผลิตจำนวนจำกัด 100 ตัว', 'สกรีนลายด้วยหมึกน้ำ'], 'สินค้าหมดชั่วคราว ติดตามรอบผลิตถัดไปได้ที่ <a href="{WEBURL}news">โปรโมชัน</a>'),
                    'en' => $d('Limited Graphic Tee', 'Limited-run graphic tee (sold out — sample of an out-of-stock product).', ['Limited run of 100', 'Water-based ink print'])
                ],
                // ---------------- ของใช้ในบ้าน (category 3) ----------------
                [
                    'sku' => 'MUG-002', 'alias' => 'couple-mug-gift-set', 'category' => 3, 'price' => 520, 'weight' => 0.8,
                    'stock' => 14, 'cost' => 260, 'days_ago' => 12, 'visited' => 143,
                    'images' => [['shape' => 'mug', 'color' => '#0EA5E9'], ['shape' => 'box', 'color' => '#FFB81C', 'caption' => 'กล่องของขวัญ']],
                    'th' => $d('ชุดของขวัญแก้วมัคคู่', 'แก้วมัคสองใบในกล่องของขวัญ พร้อมการ์ดอวยพร เหมาะเป็นของขวัญทุกโอกาส', ['แก้วมัค 2 ใบ', 'กล่องของขวัญพร้อมการ์ด']),
                    'en' => $d('Couple Mug Gift Set', 'Two mugs in a gift box with a greeting card.', ['2 mugs', 'Gift box and card'])
                ],
                [
                    'sku' => 'NTB-001', 'alias' => 'fabric-notebook-a5', 'category' => 3, 'weight' => 0.3, 'days_ago' => 16, 'visited' => 118, 'cost' => 70,
                    'images' => [['shape' => 'notebook', 'color' => '#16A34A', 'caption' => 'สมุดปกผ้า สีเขียว'], ['shape' => 'notebook', 'color' => '#1E3A8A', 'caption' => 'สมุดปกผ้า สีกรมท่า'], ['shape' => 'notebook', 'color' => '#EC4899', 'caption' => 'สมุดปกผ้า สีชมพู']],
                    'variants' => [
                        ['values' => ['color' => 'green'], 'price' => 159, 'stock' => 40, 'image' => 0],
                        ['values' => ['color' => 'navy'], 'price' => 159, 'stock' => 35, 'image' => 1],
                        ['values' => ['color' => 'pink'], 'price' => 159, 'stock' => 28, 'image' => 2]
                    ],
                    'th' => $d('สมุดบันทึกปกผ้า A5', 'สมุดบันทึกปกผ้าขนาด A5 กระดาษถนอมสายตา 120 แผ่น', ['ขนาด A5', 'กระดาษ 100 แกรม 120 แผ่น', 'มีให้เลือก 3 สี']),
                    'en' => $d('Fabric Notebook A5', 'A5 fabric-covered notebook, 120 sheets of eye-friendly paper.', ['A5', '120 sheets, 100 gsm', '3 colours'])
                ],
                [
                    'sku' => 'BOX-001', 'alias' => 'bamboo-storage-box', 'category' => 3, 'price' => 390, 'weight' => 0.9,
                    'manage_stock' => 0, 'days_ago' => 24, 'visited' => 86,
                    'images' => [['shape' => 'box', 'color' => '#B08968']],
                    'th' => $d('กล่องเก็บของไม้ไผ่', 'กล่องเก็บของสานจากไม้ไผ่ งานฝีมือชุมชน แข็งแรง ใช้ได้นาน', ['ขนาด 30 x 20 x 15 ซม.', 'งานฝีมือจากวิสาหกิจชุมชน']),
                    'en' => $d('Bamboo Storage Box', 'Handwoven bamboo storage box by a community enterprise.', ['30 x 20 x 15 cm', 'Community handicraft'])
                ],
                // ---------------- อาหารและของฝาก (category 4) ----------------
                [
                    'sku' => 'JAM-001', 'alias' => 'homemade-strawberry-jam', 'category' => 4, 'price' => 145, 'weight' => 0.3,
                    'stock' => 36, 'cost' => 60, 'days_ago' => 6, 'visited' => 131,
                    'images' => [['shape' => 'jar', 'color' => '#CC0000']],
                    'th' => $d('แยมสตรอว์เบอร์รีโฮมเมด', 'แยมสตรอว์เบอร์รีทำมือ หวานน้อย เนื้อผลไม้แน่น', ['ขนาด 200 กรัม', 'ไม่ใส่สารกันบูด', 'เก็บในตู้เย็นหลังเปิด']),
                    'en' => $d('Homemade Strawberry Jam', 'Handmade low-sugar strawberry jam with real fruit pieces.', ['200 g', 'No preservatives', 'Refrigerate after opening'])
                ],
                [
                    'sku' => 'TEA-001', 'alias' => 'dried-flower-tea-gift-box', 'category' => 4, 'price' => 260, 'weight' => 0.25,
                    'manage_stock' => 0, 'days_ago' => 20, 'visited' => 102,
                    'images' => [['shape' => 'box', 'color' => '#16A34A']],
                    'th' => $d('ชาดอกไม้อบแห้ง กล่องของขวัญ', 'ชาดอกไม้อบแห้ง 4 ชนิดในกล่องของขวัญ หอมละมุน ดื่มง่าย', ['4 รสชาติ รสละ 5 ซอง', 'บรรจุกล่องของขวัญ']),
                    'en' => $d('Dried Flower Tea Gift Box', 'Four dried-flower teas in a gift box.', ['4 flavours x 5 sachets', 'Gift boxed'])
                ],
                // ---------------- อุปกรณ์ไอที (category 5) ----------------
                [
                    'sku' => 'WTC-001', 'alias' => 'smart-watch-sport', 'category' => 5, 'weight' => 0.1, 'days_ago' => 4, 'visited' => 488, 'cost' => 1100,
                    'images' => [['shape' => 'watch', 'color' => '#1F2937', 'caption' => 'นาฬิกาอัจฉริยะ สีดำ'], ['shape' => 'watch', 'color' => '#EC4899', 'caption' => 'นาฬิกาอัจฉริยะ สีชมพู']],
                    'variants' => [
                        ['values' => ['color' => 'black'], 'price' => 1990, 'sale_price' => 1790, 'stock' => 10, 'image' => 0],
                        ['values' => ['color' => 'pink'], 'price' => 1990, 'sale_price' => 1790, 'stock' => 7, 'image' => 1]
                    ],
                    'th' => $d('นาฬิกาอัจฉริยะ รุ่นสปอร์ต', 'นาฬิกาอัจฉริยะวัดชีพจร นับก้าว และแจ้งเตือนข้อความ กันน้ำ 5 ATM ลดราคาพิเศษ', ['หน้าจอสี 1.4 นิ้ว', 'แบตเตอรี่ 7 วัน', 'กันน้ำ 5 ATM']),
                    'en' => $d('Smart Watch Sport', 'Heart rate, steps and notifications, 5 ATM water resistant. On sale.', ['1.4-inch colour display', '7-day battery', '5 ATM'])
                ],
                [
                    'sku' => 'HPH-002', 'alias' => 'over-ear-headphones-pro', 'category' => 5, 'price' => 2490, 'weight' => 0.35,
                    'stock' => 5, 'cost' => 1500, 'days_ago' => 45, 'visited' => 267,
                    'images' => [['shape' => 'headphone', 'color' => '#CC0000']],
                    'th' => $d('หูฟังครอบหู รุ่นโปร', 'หูฟังครอบหูตัดเสียงรบกวน เสียงเบสแน่น ใส่สบายตลอดวัน', ['ตัดเสียงรบกวนแบบแอกทีฟ', 'ใช้งานต่อเนื่อง 30 ชั่วโมง', 'รับประกัน 1 ปี']),
                    'en' => $d('Over-ear Headphones Pro', 'Noise-cancelling over-ear headphones with rich bass.', ['Active noise cancelling', '30 h playback', '1-year warranty'])
                ]
            ]
        ],

        // ---------------------------------------------------------------
        // ข่าวสารและโปรโมชัน
        // ---------------------------------------------------------------
        'news' => [
            'owner' => 'document',
            'topic' => 'ข่าวสารและโปรโมชัน',
            'description' => 'โปรโมชัน ส่วนลด สินค้าใหม่ และข่าวสารจาก'.$site,
            'keywords' => 'โปรโมชัน,ส่วนลด,สินค้าใหม่',
            'visited' => 820,
            'detail' => '<p>โปรโมชันและข่าวสารล่าสุดของร้าน</p>',
            'config' => ['category_display' => '', 'can_reply' => [0, 1], 'sort' => 2],
            'default_icon' => 'โปรโมชัน',
            'categories' => [
                1 => ['th' => 'โปรโมชัน', 'en' => 'Promotions'],
                2 => ['th' => 'สินค้าใหม่', 'en' => 'New Arrivals'],
                3 => ['th' => 'ข่าวจากร้าน', 'en' => 'Shop News']
            ],
            'articles' => [
                [
                    'topic' => 'ลดทั้งร้าน 10% เมื่อซื้อครบ 1,000 บาท ส่งฟรีทั่วประเทศ',
                    'category' => 1, 'tags' => ['โปรโมชัน', 'ส่งฟรี'], 'picture' => true, 'art' => 'shop', 'days_ago' => 1, 'visited' => 640,
                    'description' => 'ช้อปครบ 1,000 บาท รับส่วนลด 10% และจัดส่งฟรีด้วยไปรษณีย์ลงทะเบียน ตลอดเดือนนี้',
                    'detail' => '<p>เพียงช้อปสินค้าครบ <strong>1,000 บาท</strong> ต่อคำสั่งซื้อ รับส่วนลด 10% ทันที และจัดส่งฟรีด้วยไปรษณีย์ลงทะเบียน</p>'
                        .'<ul><li>ระยะเวลา: ตลอดเดือนนี้</li><li>ใช้ได้กับสินค้าทุกหมวด ยกเว้นสินค้าลดราคาอยู่แล้ว</li><li>จำกัด 1 สิทธิ์ต่อคำสั่งซื้อ</li></ul>'
                        .'<p><a href="{WEBURL}product">เลือกซื้อสินค้า</a></p>',
                    'comments' => [
                        ['sender' => 'คุณมิ้นท์', 'detail' => 'ใช้กับสินค้าชุมชนได้ไหมคะ'],
                        ['sender' => 'แอดมินร้าน', 'detail' => 'ได้ค่ะ ใช้ได้ทุกหมวดยกเว้นสินค้าที่ลดราคาอยู่แล้วค่ะ']
                    ]
                ],
                [
                    'topic' => 'สินค้าใหม่ เสื้อยืดคอตตอนออร์แกนิก มาแล้ว',
                    'category' => 2, 'tags' => ['สินค้าใหม่', 'แฟชั่น'], 'picture' => true, 'days_ago' => 5, 'visited' => 388,
                    'description' => 'เสื้อยืดผ้าคอตตอนออร์แกนิก 100% มีให้เลือก 4 ขนาด 2 สี',
                    'detail' => '<p>เสื้อยืดคอตตอนออร์แกนิก 100% เนื้อผ้านุ่ม ระบายอากาศดี มีให้เลือกขนาด S ถึง XL สีขาวและสีกรมท่า</p>'
                        .'<p>ดูรายละเอียดและเลือกขนาดได้ที่ <a href="{WEBURL}product">หน้าสินค้า</a></p>'
                ],
                [
                    'topic' => 'กระเป๋าผ้าสีเขียว ลดเหลือ 299 บาท',
                    'category' => 1, 'tags' => ['โปรโมชัน', 'กระเป๋า'], 'picture' => true, 'days_ago' => 8, 'visited' => 297,
                    'description' => 'ราคาพิเศษสำหรับกระเป๋าผ้าแคนวาสสีเขียว จากปกติ 350 บาท',
                    'detail' => '<p>กระเป๋าผ้าแคนวาสสีเขียว ลดราคาพิเศษเหลือ <strong>299 บาท</strong> จากปกติ 350 บาท จนกว่าสินค้าจะหมด</p>'
                ],
                [
                    'topic' => 'ของขวัญปีใหม่ ห่อของขวัญฟรีทุกคำสั่งซื้อ',
                    'category' => 1, 'tags' => ['ของขวัญ', 'โปรโมชัน'], 'picture' => true, 'art' => 'shop', 'days_ago' => 12, 'visited' => 214,
                    'description' => 'เลือกบริการห่อของขวัญพร้อมการ์ดอวยพรได้ฟรี เพียงระบุในหมายเหตุตอนสั่งซื้อ',
                    'detail' => '<p>ร้านห่อของขวัญพร้อมการ์ดอวยพรให้ฟรี เพียงพิมพ์ข้อความในช่องหมายเหตุตอนสั่งซื้อ</p>'
                ],
                [
                    'topic' => 'สินค้าชุมชน น้ำผึ้งดอกลำไยและกล่องไม้ไผ่',
                    'category' => 2, 'tags' => ['สินค้าชุมชน', 'ของฝาก'], 'picture' => true, 'art' => 'garden', 'days_ago' => 18, 'visited' => 176,
                    'description' => 'สนับสนุนผู้ผลิตท้องถิ่นด้วยสินค้าคุณภาพจากวิสาหกิจชุมชน',
                    'detail' => '<p>ร้านร่วมกับวิสาหกิจชุมชนนำสินค้าคุณภาพมาจำหน่าย รายได้ส่วนหนึ่งกลับคืนสู่ผู้ผลิตโดยตรง</p>'
                ],
                [
                    'topic' => 'ประกาศวันหยุดจัดส่งสินค้า',
                    'category' => 3, 'tags' => ['ประกาศ', 'การจัดส่ง'], 'picture' => false, 'days_ago' => 22, 'visited' => 142,
                    'description' => 'งดจัดส่งสินค้าในวันหยุดนักขัตฤกษ์ คำสั่งซื้อในช่วงดังกล่าวจัดส่งในวันทำการถัดไป',
                    'detail' => '<p>ร้านงดจัดส่งสินค้าในวันหยุดนักขัตฤกษ์ คำสั่งซื้อที่เข้ามาในช่วงดังกล่าวจะจัดส่งในวันทำการถัดไปตามลำดับ</p>'
                ],
                [
                    'topic' => 'รีวิวจากลูกค้า ขวดน้ำสแตนเลสเก็บความเย็นได้ทั้งวัน',
                    'category' => 3, 'tags' => ['รีวิว', 'ขวดน้ำ'], 'picture' => true, 'days_ago' => 27, 'visited' => 233,
                    'description' => 'ลูกค้าเล่าประสบการณ์ใช้ขวดน้ำสแตนเลสระหว่างเดินทางและออกกำลังกาย',
                    'detail' => '<blockquote>ใส่น้ำแข็งตอนเช้า ตกเย็นยังเหลือน้ำแข็งอยู่เลย พกไปทำงานทุกวัน</blockquote><p>ขอบคุณลูกค้าทุกท่านที่ส่งรีวิวมาให้ร้าน</p>'
                ],
                [
                    'topic' => 'วิธีดูแลแก้วมัคเซรามิกให้สวยนาน',
                    'category' => 3, 'tags' => ['เคล็ดลับ', 'แก้วมัค'], 'picture' => true, 'days_ago' => 33, 'visited' => 158,
                    'description' => 'เคล็ดลับทำความสะอาดคราบชากาแฟ และข้อควรระวังในการใช้งาน',
                    'detail' => '<ol><li>ล้างทันทีหลังใช้งานเพื่อลดคราบ</li><li>ใช้เบกกิ้งโซดาขัดคราบชากาแฟ</li><li>หลีกเลี่ยงการเปลี่ยนอุณหภูมิกะทันหัน</li></ol>'
                ]
            ]
        ],

        // ---------------------------------------------------------------
        // ถาม-ตอบ
        // ---------------------------------------------------------------
        'forum' => [
            'owner' => 'board',
            'topic' => 'ถาม-ตอบสินค้า',
            'description' => 'สอบถามข้อมูลสินค้า การสั่งซื้อ และการจัดส่งกับ'.$site,
            'keywords' => 'ถาม-ตอบ,สอบถามสินค้า,การจัดส่ง',
            'visited' => 410,
            'categories' => [
                1 => ['th' => 'สอบถามสินค้า', 'en' => 'Products', 'icon' => 'cart'],
                2 => ['th' => 'การสั่งซื้อและชำระเงิน', 'en' => 'Orders & Payment', 'icon' => 'money'],
                3 => ['th' => 'การจัดส่งและคืนสินค้า', 'en' => 'Shipping & Returns', 'icon' => 'truck']
            ],
            'topics' => [
                ['topic' => 'อ่านก่อนสอบถาม: ช่องทางติดต่อและเวลาตอบกลับ', 'category' => 1, 'pin' => 1, 'days_ago' => 60, 'visited' => 380, 'sender' => 'แอดมินร้าน',
                    'detail' => '<p>ทีมงานตอบคำถามทุกวัน 09.00 - 18.00 น. กรุณาอย่าโพสต์ข้อมูลส่วนตัว เช่น ที่อยู่ หรือเบอร์โทรศัพท์ในกระทู้</p>'],
                ['topic' => 'เสื้อยืดคอตตอนไซซ์ M อกกี่นิ้วคะ', 'category' => 1, 'days_ago' => 3, 'visited' => 64, 'sender' => 'คุณแพรว',
                    'detail' => '<p>เสื้อยืดคอตตอนออร์แกนิกไซซ์ M รอบอกกี่นิ้ว ยาวกี่นิ้วคะ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>ไซซ์ M รอบอก 40 นิ้ว ความยาว 28 นิ้วค่ะ ดูตารางขนาดทั้งหมดได้ที่หน้าวิธีสั่งซื้อค่ะ</p>'],
                        ['sender' => 'คุณแพรว', 'detail' => '<p>ขอบคุณค่ะ</p>']
                    ]],
                ['topic' => 'โอนเงินแล้วต้องแจ้งชำระเงินที่ไหน', 'category' => 2, 'days_ago' => 5, 'visited' => 88, 'sender' => 'คุณต้น',
                    'detail' => '<p>โอนเงินผ่านแอปธนาคารแล้ว ต้องแจ้งที่ไหนครับ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>เข้าเมนูคำสั่งซื้อของฉัน เลือกคำสั่งซื้อแล้วกดแจ้งชำระเงิน แนบสลิปได้เลยค่ะ</p>']
                    ]],
                ['topic' => 'สั่งวันนี้ได้ของกี่วันครับ', 'category' => 3, 'days_ago' => 7, 'visited' => 102, 'sender' => 'คุณบอส',
                    'detail' => '<p>อยู่ต่างจังหวัด สั่งวันนี้ประมาณกี่วันได้ของครับ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>ร้านจัดส่งภายใน 1-2 วันทำการ ไปรษณีย์ลงทะเบียนใช้เวลา 2-4 วัน ขนส่งด่วน 1-2 วันค่ะ</p>'],
                        ['sender' => 'คุณบอส', 'detail' => '<p>รับทราบครับ</p>'],
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>ติดตามเลขพัสดุได้ในหน้าคำสั่งซื้อค่ะ</p>']
                    ]],
                ['topic' => 'เปลี่ยนไซซ์รองเท้าได้ไหม', 'category' => 3, 'days_ago' => 11, 'visited' => 57, 'sender' => 'คุณเจ',
                    'detail' => '<p>สั่งรองเท้าไซซ์ 41 แล้วคับไป เปลี่ยนเป็น 42 ได้ไหมครับ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>เปลี่ยนได้ภายใน 7 วันหลังได้รับสินค้า สินค้าต้องยังไม่ผ่านการใช้งานค่ะ</p>']
                    ]],
                ['topic' => 'มีบริการเก็บเงินปลายทางไหมคะ', 'category' => 2, 'days_ago' => 15, 'visited' => 73, 'sender' => 'คุณนิด',
                    'detail' => '<p>สะดวกจ่ายปลายทาง มีบริการไหมคะ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>มีค่ะ เลือก "เก็บเงินปลายทาง" ตอนชำระเงินได้เลยค่ะ</p>']
                    ]],
                ['topic' => 'เสื้อลายกราฟิกรุ่นลิมิเต็ดจะมีอีกไหม', 'category' => 1, 'days_ago' => 9, 'visited' => 91, 'sender' => 'คุณเก่ง',
                    'detail' => '<p>รุ่นลิมิเต็ดหมดแล้ว จะผลิตเพิ่มไหมครับ</p>',
                    'replies' => [
                        ['sender' => 'แอดมินร้าน', 'detail' => '<p>รอบถัดไปกำลังเตรียมผลิตค่ะ ติดตามได้ที่หน้าโปรโมชันค่ะ</p>']
                    ]]
            ]
        ],

        // ---------------------------------------------------------------
        // แกลเลอรี่
        // ---------------------------------------------------------------
        'gallery' => [
            'owner' => 'gallery',
            'topic' => 'แกลเลอรี่',
            'description' => 'ภาพหน้าร้าน กิจกรรม และภาพสินค้าจากลูกค้าของ'.$site,
            'keywords' => 'แกลเลอรี่,ภาพสินค้า,หน้าร้าน',
            'visited' => 300,
            'albums' => [
                ['topic' => 'บรรยากาศหน้าร้าน', 'detail' => 'มุมต่าง ๆ ของหน้าร้านและชั้นวางสินค้า', 'scene' => 'shop', 'label' => 'หน้าร้าน', 'days_ago' => 6, 'visited' => 120,
                    'captions' => ['หน้าร้าน', 'ชั้นวางของใช้ในบ้าน', 'มุมเสื้อผ้า', 'มุมของฝาก', 'เคาน์เตอร์ชำระเงิน', 'มุมห่อของขวัญ']],
                ['topic' => 'ตลาดนัดสินค้าชุมชน', 'detail' => 'ร้านร่วมออกบูธในตลาดนัดสินค้าชุมชน', 'scene' => 'market', 'label' => 'ตลาดนัด', 'days_ago' => 15, 'visited' => 96,
                    'captions' => ['บูธของร้าน', 'สินค้าชุมชน', 'ชิมน้ำผึ้งดอกลำไย', 'ลูกค้าเลือกซื้อสินค้า', 'สาธิตงานจักสาน', 'ภาพหมู่ทีมงาน']],
                ['topic' => 'เบื้องหลังการแพ็กสินค้า', 'detail' => 'ทุกคำสั่งซื้อแพ็กอย่างใส่ใจก่อนส่งถึงมือลูกค้า', 'scene' => 'workshop', 'label' => 'แพ็กสินค้า', 'days_ago' => 25, 'visited' => 80,
                    'captions' => ['ตรวจสินค้าก่อนแพ็ก', 'ห่อกันกระแทก', 'ติดใบปะหน้า', 'ส่งมอบให้ขนส่ง', 'พัสดุพร้อมส่ง', 'ทีมแพ็กสินค้า']],
                ['topic' => 'กิจกรรมเวิร์กช็อปจัดสวนขวด', 'detail' => 'เวิร์กช็อปจัดสวนขวดสำหรับลูกค้า', 'scene' => 'garden', 'label' => 'เวิร์กช็อป', 'days_ago' => 38, 'visited' => 65,
                    'captions' => ['เตรียมอุปกรณ์', 'เลือกต้นไม้', 'จัดวางหินและดิน', 'ผลงานผู้ร่วมกิจกรรม', 'ถ่ายภาพร่วมกัน', 'ของที่ระลึก']]
            ]
        ],

        // ---------------------------------------------------------------
        // วิดีโอ
        // ---------------------------------------------------------------
        'video' => [
            'owner' => 'video',
            'topic' => 'วิดีโอรีวิวสินค้า',
            'description' => 'วิดีโอรีวิวและแนะนำวิธีใช้สินค้า',
            'keywords' => 'วิดีโอ,รีวิวสินค้า,YouTube',
            'visited' => 240,
            'videos' => [
                ['youtube' => 'aqz-KE-bpKQ', 'topic' => 'แนะนำร้านของเรา (ตัวอย่าง)', 'label' => 'แนะนำร้าน', 'views' => 420, 'description' => $sample],
                ['youtube' => 'eRsGyueVLvQ', 'topic' => 'รีวิวขวดน้ำสแตนเลส (ตัวอย่าง)', 'label' => 'รีวิว', 'views' => 318, 'description' => $sample],
                ['youtube' => 'R6MlUcmOul8', 'topic' => 'วิธีเลือกไซซ์เสื้อยืด (ตัวอย่าง)', 'label' => 'How-to', 'views' => 205, 'description' => $sample],
                ['youtube' => 'WhWc3b3KhnY', 'topic' => 'เบื้องหลังการแพ็กสินค้า (ตัวอย่าง)', 'label' => 'เบื้องหลัง', 'views' => 144, 'description' => $sample],
                ['youtube' => 'YE7VzlLtp-4', 'topic' => 'รีวิวหูฟังไร้สาย (ตัวอย่าง)', 'label' => 'รีวิว', 'views' => 276, 'description' => $sample],
                ['youtube' => 'Y-rmzh0PI3c', 'topic' => 'เวิร์กช็อปจัดสวนขวด (ตัวอย่าง)', 'label' => 'กิจกรรม', 'views' => 97, 'description' => $sample]
            ]
        ],

        // ---------------------------------------------------------------
        // หน้าเพจ
        // ---------------------------------------------------------------
        'about' => [
            'owner' => 'index',
            'topic' => 'เกี่ยวกับร้าน',
            'description' => 'เรื่องราวและความตั้งใจของ'.$site,
            'keywords' => 'เกี่ยวกับร้าน,เรื่องราว',
            'visited' => 260,
            'detail' => '<h2>เรื่องราวของเรา</h2>'
                .'<p>'.$site.' เริ่มจากความชอบของใช้ที่ดีไซน์เรียบง่ายแต่ใช้งานได้จริง เราคัดสินค้าทุกชิ้นด้วยตัวเอง '
                .'และทำงานร่วมกับผู้ผลิตท้องถิ่นเพื่อให้ลูกค้าได้สินค้าคุณภาพในราคายุติธรรม</p>'
                .'<h2>สิ่งที่เรายึดถือ</h2>'
                .'<ul><li><strong>คุณภาพก่อนราคา</strong> — ทดลองใช้ก่อนนำมาขายทุกชิ้น</li><li><strong>สนับสนุนชุมชน</strong> — รายได้ส่วนหนึ่งกลับสู่ผู้ผลิตท้องถิ่น</li><li><strong>บริการจริงใจ</strong> — เปลี่ยนคืนได้ภายใน 7 วัน</li></ul>'
                .'<table><tbody><tr><th>ลูกค้า</th><td>12,000+ ราย</td></tr><tr><th>สินค้า</th><td>300+ รายการ</td></tr><tr><th>คะแนนรีวิว</th><td>4.9 / 5</td></tr></tbody></table>'
        ],
        'howto' => [
            'owner' => 'index',
            'topic' => 'วิธีสั่งซื้อ',
            'description' => 'ขั้นตอนการสั่งซื้อ ชำระเงิน จัดส่ง และการเปลี่ยนคืนสินค้า',
            'keywords' => 'วิธีสั่งซื้อ,ชำระเงิน,จัดส่ง,เปลี่ยนคืนสินค้า',
            'visited' => 540,
            'detail' => '<h2>ขั้นตอนการสั่งซื้อ</h2>'
                .'<ol><li>เลือกสินค้าและตัวเลือก (ขนาด/สี) แล้วกด <strong>หยิบใส่ตะกร้า</strong></li>'
                .'<li>ตรวจสอบตะกร้าแล้วกด <strong>สั่งซื้อ</strong></li>'
                .'<li>กรอกชื่อ ที่อยู่จัดส่ง และเลือกวิธีจัดส่ง</li>'
                .'<li>เลือกวิธีชำระเงิน โอนเงินผ่านธนาคาร หรือเก็บเงินปลายทาง</li>'
                .'<li>โอนเงินแล้วแจ้งชำระเงินพร้อมแนบสลิปที่หน้า <strong>คำสั่งซื้อของฉัน</strong></li></ol>'
                .'<h2>ค่าจัดส่ง</h2>'
                .'<table><thead><tr><th>วิธีจัดส่ง</th><th>ค่าบริการ</th><th>ระยะเวลา</th></tr></thead><tbody>'
                .'<tr><td>ไปรษณีย์ลงทะเบียน</td><td>50 บาท (ฟรีเมื่อซื้อครบ 1,000 บาท)</td><td>2-4 วัน</td></tr>'
                .'<tr><td>ขนส่งด่วนเอกชน</td><td>40 บาท + 15 บาท/กก.</td><td>1-2 วัน</td></tr>'
                .'<tr><td>รับสินค้าที่ร้าน</td><td>ฟรี</td><td>พร้อมรับภายใน 1 วัน</td></tr>'
                .'</tbody></table>'
                .'<h2>ตารางขนาดเสื้อยืด</h2>'
                .'<table><thead><tr><th>ขนาด</th><th>รอบอก (นิ้ว)</th><th>ความยาว (นิ้ว)</th></tr></thead><tbody>'
                .'<tr><td>S</td><td>38</td><td>27</td></tr><tr><td>M</td><td>40</td><td>28</td></tr><tr><td>L</td><td>42</td><td>29</td></tr><tr><td>XL</td><td>44</td><td>30</td></tr>'
                .'</tbody></table>'
                .'<h2>การเปลี่ยนคืนสินค้า</h2>'
                .'<p>เปลี่ยนหรือคืนสินค้าได้ภายใน 7 วันหลังได้รับสินค้า สินค้าต้องอยู่ในสภาพเดิมพร้อมบรรจุภัณฑ์ สอบถามได้ที่ <a href="{WEBURL}forum">ถาม-ตอบสินค้า</a></p>'
        ],
        'contact' => [
            'owner' => 'index',
            'topic' => 'ติดต่อร้าน',
            'description' => 'ที่อยู่ร้าน เบอร์โทรศัพท์ และช่องทางติดต่อ'.$site,
            'keywords' => 'ติดต่อร้าน,ที่อยู่,โทรศัพท์',
            'visited' => 310,
            'detail' => '<h2>ติดต่อ '.$site.'</h2>'
                .'<p><strong>หน้าร้าน :</strong> เลขที่ 9 ถนนตัวอย่าง ตำบลตัวอย่าง อำเภอเมือง จังหวัดตัวอย่าง 99999<br>'
                .'<strong>โทรศัพท์ :</strong> 08-0000-0000<br>'
                .'<strong>อีเมล :</strong> {SITE_EMAIL}<br>'
                .'<strong>เวลาเปิดร้าน :</strong> ทุกวัน 10.00 – 19.00 น.</p>'
                .'<p>สอบถามสินค้าออนไลน์ได้ที่ <a href="{WEBURL}forum">ถาม-ตอบสินค้า</a></p>'
                .'<p><em>ข้อมูลติดต่อเป็นตัวอย่าง แก้ไขได้ที่หน้าผู้ดูแล › หน้าเพจ › ติดต่อร้าน</em></p>'
        ]
    ],

    // -------------------------------------------------------------------
    // เมนู
    // -------------------------------------------------------------------
    'menus' => [
        '0_MAINMENU' => [
            ['text' => 'หน้าหลัก', 'module' => 'home', 'icon' => 'icon-home'],
            ['text' => 'สินค้าทั้งหมด', 'module' => 'product'],
            ['text' => 'โปรโมชัน', 'module' => 'news'],
            ['text' => 'วิธีสั่งซื้อ', 'module' => 'howto'],
            ['text' => 'มีเดีย', 'module' => 'gallery', 'children' => [
                ['text' => 'แกลเลอรี่', 'module' => 'gallery'],
                ['text' => 'วิดีโอรีวิว', 'module' => 'video']
            ]],
            ['text' => 'ถาม-ตอบ', 'module' => 'forum'],
            ['text' => 'เกี่ยวกับร้าน', 'module' => 'about'],
            ['text' => 'ติดต่อร้าน', 'module' => 'contact']
        ],
        '1_SIDEMENU' => [
            ['text' => 'สินค้าทั้งหมด', 'module' => 'product'],
            ['text' => 'โปรโมชัน', 'module' => 'news'],
            ['text' => 'วิธีสั่งซื้อ', 'module' => 'howto'],
            ['text' => 'ถาม-ตอบสินค้า', 'module' => 'forum']
        ],
        '2_BOTTOMMENU' => [
            ['text' => 'เกี่ยวกับร้าน', 'module' => 'about'],
            ['text' => 'วิธีสั่งซื้อ', 'module' => 'howto'],
            ['text' => 'ถาม-ตอบ', 'module' => 'forum'],
            ['text' => 'ติดต่อร้าน', 'module' => 'contact']
        ]
    ],

    // -------------------------------------------------------------------
    // Textlinks
    // -------------------------------------------------------------------
    'textlinks' => [
        ['name' => 'slideshow', 'text' => 'ลดทั้งร้าน 10% ส่งฟรีทั่วประเทศ', 'description' => 'เมื่อช้อปครบ 1,000 บาท ตลอดเดือนนี้',
            'label' => 'โปรโมชัน', 'button' => 'ช้อปเลย', 'url' => '{WEBURL}product', 'target' => '_self', 'scene' => 'shop'],
        ['name' => 'slideshow', 'text' => 'เสื้อยืดคอตตอน ออร์แกนิก คอลเลกชันใหม่', 'description' => 'นุ่ม ใส่สบาย ระบายอากาศดี มีให้เลือก 4 ขนาด',
            'label' => 'สินค้าใหม่', 'button' => 'ดูสินค้า', 'url' => '{WEBURL}product', 'target' => '_self', 'scene' => 'market'],
        ['name' => 'slideshow', 'text' => 'ของฝากจากชุมชน คุณภาพจากผู้ผลิตท้องถิ่น', 'description' => 'น้ำผึ้งดอกลำไย แยมโฮมเมด และงานจักสานไม้ไผ่',
            'label' => 'สินค้าชุมชน', 'button' => 'เลือกซื้อ', 'url' => '{WEBURL}product', 'target' => '_self', 'scene' => 'garden'],
        ['name' => 'slideshow', 'text' => 'ห่อของขวัญฟรี พร้อมการ์ดอวยพร', 'description' => 'ระบุข้อความในหมายเหตุตอนสั่งซื้อ',
            'label' => 'บริการพิเศษ', 'button' => 'อ่านรายละเอียด', 'url' => '{WEBURL}news', 'target' => '_self', 'scene' => 'food'],
        ['name' => 'banner', 'text' => 'สมาชิกใหม่รับส่วนลด 50 บาท', 'description' => 'สมัครสมาชิกวันนี้ ใช้ได้กับคำสั่งซื้อแรก',
            'button' => 'สมัครสมาชิก', 'url' => '{WEBURL}register', 'target' => '_self'],
        ['name' => 'banner', 'text' => 'เก็บเงินปลายทาง ทั่วประเทศ', 'description' => 'สะดวก ปลอดภัย ตรวจสอบสินค้าก่อนจ่าย',
            'button' => 'วิธีสั่งซื้อ', 'url' => '{WEBURL}howto', 'target' => '_self'],
        ['name' => 'imagemenu', 'text' => 'จัดส่งทั่วประเทศ', 'description' => 'ส่งฟรีเมื่อซื้อครบ 1,000 บาท', 'icon' => 'truck', 'url' => '{WEBURL}howto', 'target' => '_self'],
        ['name' => 'imagemenu', 'text' => 'เปลี่ยนคืนใน 7 วัน', 'description' => 'มั่นใจทุกคำสั่งซื้อ', 'icon' => 'shield', 'url' => '{WEBURL}howto', 'target' => '_self'],
        ['name' => 'imagemenu', 'text' => 'ชำระเงินปลอดภัย', 'description' => 'โอนเงิน / ปลายทาง', 'icon' => 'money', 'url' => '{WEBURL}howto', 'target' => '_self'],
        ['name' => 'imagemenu', 'text' => 'สอบถามสินค้า', 'description' => 'ตอบทุกวัน 9.00-18.00 น.', 'icon' => 'chat', 'url' => '{WEBURL}forum', 'target' => '_self']
    ],

    // ตัวเลขของ {WIDGET_STATS}
    'stats' => [
        ['icon' => 'icon-customer', 'label' => 'ลูกค้าที่ไว้วางใจ', 'value' => 12000, 'suffix' => '+'],
        ['icon' => 'icon-cart', 'label' => 'สินค้าในร้าน', 'value' => 300, 'suffix' => '+'],
        ['icon' => 'icon-favorite', 'label' => 'คะแนนรีวิว', 'value' => 4.9, 'suffix' => '/5', 'decimals' => 1],
        ['icon' => 'icon-gift', 'label' => 'คำสั่งซื้อที่จัดส่งแล้ว', 'value' => 25000, 'suffix' => '+']
    ],

    'counter_days' => 45,
    'counter_base' => 320
];
