# install/seeds — ประเภทเว็บไซต์และข้อมูลตัวอย่างของตัวติดตั้ง

ตอนติดตั้ง ผู้ใช้เลือกประเภทเว็บไซต์ 1 ใน 3 แบบ ตัวติดตั้งจะตั้งธีมให้ตรงกับประเภท
และ (ถ้าเลือก) นำเข้าข้อมูลตัวอย่างพร้อมรูป ไซต์จึงเปิดดูและทดสอบได้ทันที

ไฟล์นี้คือ **สัญญากลาง** ระหว่างสามส่วนที่ต้องตรงกันเสมอ:
ธีม (`themes/<skin>/*.html` อ้างโมดูลผ่าน `{WIDGET_... module=...}`),
ข้อมูลตัวอย่าง (`install/seeds/<type>/seed.sql` สร้างโมดูลชื่อเหล่านั้น) และ
ตัวติดตั้ง (`install/step*.php` / `install/cli-fresh.php` ที่นำเข้า seed)

## ประเภทเว็บไซต์

| key       | ชื่อที่แสดงตอนติดตั้ง                      | ธีม (skin) | คอลัมน์ |
|-----------|---------------------------------------------|------------|---------|
| `company` | เว็บไซต์บริษัท / ข่าวสาร                    | `rw`       | 2       |
| `school`  | เว็บไซต์หน่วยงานราชการ / อบต. / โรงเรียน   | `gts`      | 3       |
| `shop`    | เว็บไซต์ร้านค้าออนไลน์ (e-commerce)         | `shop`     | 1       |

## ชื่อโมดูล (modules.module) ที่ seed ต้องสร้าง และธีมใช้อ้างอิง

รูปแบบ `ชื่อโมดูล(owner)` — owner คือโฟลเดอร์ใน `modules/`

- **company** : `home(index)` `news(document)` `knowledge(document)` `portfolio(portfolio)`
  `gallery(gallery)` `download(download)` `video(video)` `event(event)` `forum(board)`
  `about(index)` `services(index)` `contact(index)`
- **school** : `home(index)` `news(document)` `announce(document)` `knowledge(document)`
  `personnel(personnel)` `gallery(gallery)` `download(download)` `event(event)` `video(video)`
  `forum(board)` `edocument(edocument)` `about(index)` `vision(index)` `structure(index)` `contact(index)`
- **shop** : `home(index)` `product(product)` `news(document)` `forum(board)` `gallery(gallery)`
  `video(video)` `about(index)` `howto(index)` `contact(index)`

โมดูลแรก (`home`) คือหน้าแรกของเว็บ และต้องเป็นเมนูแรกของเมนูหลัก

## กลุ่ม Textlinks (textlink.name) ที่ธีมใช้

widget textlinks (รุ่นเดียวกับ gcms.in.th) มีข้อมูล 2 ชนิดคือ `text` และ `image`
ชนิดเก่า (slideshow, banner ฯลฯ) ถูกอ่านเป็น `image` อัตโนมัติ ส่วนวิธีแสดงผลกำหนดที่ธีม
ด้วย `layout=slideshow` (สไลด์) หรือ `layout=banner` (สุ่มแสดงทีละรูป) — ไม่ระบุ = เมนูตามชนิด

| name        | ชนิด (textlink.type) | ใช้ที่ |
|-------------|----------------------|--------|
| `slideshow` | `image`              | สไลด์ภาพใหญ่หน้าแรก — `{WIDGET_TEXTLINKS name=slideshow;layout=slideshow}` |
| `banner`    | `image`              | แบนเนอร์ประชาสัมพันธ์ สุ่มแสดงทีละรูป — `{WIDGET_TEXTLINKS name=banner;layout=banner}` |
| `imagemenu` | `image`              | ลิงก์รูปภาพใน sidebar/footer — `{WIDGET_TEXTLINKS name=imagemenu}` |

ถ้าใช้รูปแบบ `{WIDGET_TEXTLINKS_<name>}` จะเพิ่มพารามิเตอร์อื่นต่อท้ายไม่ได้ (ชื่อกลุ่มจะหาย)

ธีมห้ามใช้รูปจากภายนอก (picsum ฯลฯ) — รูปทุกรูปที่หน้าแรกแสดงต้องมาจาก widget
หรือจากไฟล์ของธีมเอง

## ข้อมูลอื่นที่ธีมคาดหวัง

- เมนู 3 ตำแหน่ง: `0_MAINMENU` (`{MAINMENU}`), `1_SIDEMENU` (`{SIDEMENU}`), `2_BOTTOMMENU` (`{BOTTOMMENU}`)
  ตำแหน่งที่ไม่มีรายการ กล่องจะถูกซ่อนเอง
- `{WIDGET_STATS}` (rw, gts, shop) อ่านชุดชื่อ `default` จาก `ROOT/datas/widgets/stats.json`
  (ไม่ใช่ DATA_FOLDER) รูปแบบ `{"items":[{"id":..,"name":"default","icon":..,"label":..,"value":..,"suffix":..,"order":..}],"next_id":..}`
  seed จึงต้องมี `<type>/datas/widgets/stats.json`
- shop: หมวดสินค้า `category_id = 1` คือ "สินค้าแนะนำ" (carousel หน้าแรก)
- บทความที่มีป้ายกำกับ (`{WIDGET_TAGS}`), บุคลากร `level = 1` (`{WIDGET_PERSONNEL ... level=1}`),
  กิจกรรมที่วันที่อยู่ในอนาคต (`{WIDGET_EVENT}`)
- `{WIDGET_FACEBOOK}` ซ่อนเองถ้าไม่ได้ตั้งค่า, `{WIDGET_MAP}` ใช้ค่าปริยายถ้าไม่ได้ตั้ง

## รูปแบบโฟลเดอร์ของแต่ละประเภท

```
install/seeds/<type>/
├── info.php      return ['label' => ..., 'description' => ..., 'skin' => ..., 'columns' => N, 'order' => N]
├── config.php    ค่าที่รวมเข้า settings/config.php (skin, web_description ...) ห้ามมีค่าความลับ
├── content.php   เนื้อหาตัวอย่าง (PHP array) — แก้ที่นี่ แล้วสร้าง seed.sql ใหม่
├── seed.sql      สร้างจาก content.php ด้วย install/seeds/build.php — ห้ามแก้เอง
├── images.json   รายการรูปที่ seed อ้างถึง (สร้างโดย build.php)
└── datas/        ไฟล์ตัวอย่าง คัดลอกไป ROOT/datas/ ตอนติดตั้ง (ไม่ทับไฟล์ที่มีอยู่)
```

กติกาของ `seed.sql` (ตัวติดตั้งอ่านแบบเดียวกับ `sqlCommands()`)

- หนึ่งคำสั่งต่อหนึ่งบรรทัด ปิดด้วย `;` — ห้ามขึ้นบรรทัดใหม่ในข้อความ (ใช้ `\n`)
- บรรทัดที่ขึ้นต้นด้วย `--` ถูกตัดทิ้ง
- ตารางเขียนเป็น `` `{prefix}_ชื่อ` `` เสมอ
- token ที่ตัวติดตั้งแทนค่าให้: `{prefix}` `{SITE_NAME}` (ชื่อเว็บ) `{SITE_EMAIL}` (อีเมลผู้ดูแล)
  ส่วนลิงก์ภายในเว็บใช้ `{WEBURL}` ซึ่งระบบแทนตอนแสดงผลอยู่แล้ว
- วันที่ใช้นิพจน์สัมพันธ์กับวันติดตั้ง (`NOW() - INTERVAL 3 DAY`, `CURDATE()`) ไซต์ใหม่จึงดูสดเสมอ
- ห้ามสร้างแถวใน `{prefix}_user` (ผู้ดูแลสูงสุดสร้างจากฟอร์มติดตั้งเท่านั้น)
- ใช้ได้เฉพาะตารางที่ `install/database.sql`, `install/core.sql` และ
  `modules/*/install/database.sql` สร้าง

## ตัวติดตั้งใช้โฟลเดอร์นี้อย่างไร (install/seeds.php)

- หน้าเว็บ : `step2.php` แสดงการ์ดของทุกโฟลเดอร์ที่มี `info.php` (เรียงตาม `order`, รูปจาก
  `themes/<skin>/screenshot.*`) และช่อง "ติดตั้งข้อมูลตัวอย่าง" · ติดตั้งจริงที่ `step5.php`
  ด้วย `applySiteType()` ตัวเดียวกับ `install/cli-fresh.php --type=<key> [--no-sample]`
- `config.php` ถูกรวมเข้า `settings/config.php` แบบทับทีละคีย์บนสุด (`array_replace`)
  คีย์ `version reversion password_key api_tokens api_secret jwt_secret` ถูกข้ามเสมอ
  และ `skin` ต้องมี `themes/<skin>/index.html` จริง ไม่งั้นใช้ธีมเริ่มต้น
- `seed.sql` นำเข้าใน **ธุรกรรมเดียว** (`SET NAMES utf8mb4`) ล้มคำสั่งใดยกเลิกทั้งชุด
  ค่า `{SITE_NAME}` `{SITE_EMAIL}` ถูกตัดอักขระ `" \ < >` และอักขระควบคุมออก แล้ว escape `'`
  (token อยู่ได้ทั้งในสตริง SQL และในสตริง JSON ที่ซ้อนอยู่)
- `datas/` ถูกคัดลอกเฉพาะเมื่อเลือกติดตั้งข้อมูลตัวอย่าง และไม่ทับไฟล์ที่มีอยู่
- โฟลเดอร์ที่ยังไม่มี `seed.sql` เลือกได้ตามปกติ (ตั้งแค่ธีม/ค่ากำหนด พร้อมหมายเหตุ)

ตรวจ seed ก่อนส่ง (ต้องผ่านทั้งหมด — นำเข้าใต้ STRICT mode, ตรวจรายชื่อโมดูลตามหัวข้อ
"ชื่อโมดูล" ข้างบน, เมนูแรกเป็น `home`, โมดูล/Textlinks ที่ธีมอ้างมีข้อมูล):

```
php install/cli-test.php --db-prefix=<ชื่อฐานทดสอบ> --types=company
php install/cli-test.php --seeds=/ที่อื่น/seeds   # ทดสอบ seed ที่ยังไม่วางลงชุดติดตั้ง
```

## สร้าง seed.sql / images.json / datas/ ใหม่ (build.php + images.py)

แก้เนื้อหาที่ `<type>/content.php` เท่านั้น แล้วสั่ง

```
php install/seeds/build.php company            # ประเภทเดียว (หลายประเภทคั่นด้วยช่องว่างได้)
php install/seeds/build.php --all              # ทุกโฟลเดอร์ที่มี content.php
php install/seeds/build.php --all --no-images  # ไม่วาดรูปใหม่ (ใช้ไฟล์ใน datas/ ที่มีอยู่)
```

`build.php` ทำตามลำดับ : สร้างแถวทั้งหมดจาก `content.php` → **ตรวจทุกแถวกับสคีมาจริง**
(`schemaCommands()` ตัวเดียวกับตัวติดตั้ง — คอลัมน์ที่ไม่มี, NOT NULL ที่ไม่ได้ใส่,
ข้อความยาวเกิน varchar, ค่า enum ผิด = หยุดทันทีไม่เขียนไฟล์) → เขียน `images.json` →
เรียก `python3 install/seeds/images.py <type>` วาดไฟล์ลง `datas/` → เขียน
`datas/widgets/stats.json` → เขียน `seed.sql` (ขนาดไฟล์ดาวน์โหลด/เอกสารอ่านจากไฟล์จริง)

- `images.py` วาดทุกรูปขึ้นเอง (Pillow + WebP + libraqm, ฟอนต์ Noto Sans Thai/Noto Sans
  หรือ tlwg, ตัดคำไทยด้วย PyICU ถ้ามี) ไม่มีภาพบุคคล/สถานที่/โลโก้จริง ผลลัพธ์คงที่
  (seed ของการสุ่มมาจาก images.json) — เอกสาร PDF/DOCX/ZIP ก็สร้างขึ้นเองพร้อมข้อความไทย
- ค่า `modules.config` = `defaultSettings()` ของโมดูลนั้น (`Index\Page\Model::defaultConfig()`)
  ทับเฉพาะคีย์ใน `'config' => [...]` ของ content.php
- วันที่ทั้งหมดสัมพันธ์กับวันติดตั้ง (`days_ago`, ปฏิทินใช้ `'month' => 0|1, 'day' => n`
  = เดือนนี้/เดือนหน้า หรือ `'days' => n` นับจากวันติดตั้ง)
- ชื่อไฟล์และที่เก็บตามที่หน้าผู้ดูแลของแต่ละโมดูลสร้าง

| ข้อมูล | ไฟล์ใน datas/ | คอลัมน์ |
|--------|---------------|---------|
| รูปบทความ | `document/picture-{module_id}-{id}.webp` | `index.picture` |
| รูปหมวด / รูปเริ่มต้นของโมดูล | `document/cat_th_{id}.webp`, `board/cat_th_{id}.webp`, `document/default-{module_id}.webp` | `category.icon`, `config.default_icon` |
| อัลบั้มภาพ | `gallery/{album_id}/{image}` (count 0 = ปก) | `gallery_image.image` |
| บุคลากร | `personnel/person-NN.webp` | `personnel.picture` |
| ผลงาน | `portfolio/{id}.webp` | `portfolio.image` |
| วิดีโอ (รูปย่อ) | `video/{youtube}.jpg` | `video.youtube` |
| ดาวน์โหลด | `download/<ไฟล์>` | `download.file` (เทียบกับ datas/) |
| หนังสือราชการ | `edocument/<ไฟล์>` (≤ 20 ตัวอักษร) | `edocument.file` |
| Textlinks | `image/textlink-{id}.webp` | `textlink.logo` (ชื่อไฟล์ล้วน) |
| สินค้า | `product/{product_id}/{image_id}.webp` | `product_image.filename` |
| ตัวเลข {WIDGET_STATS} | `widgets/stats.json` | — |

สินค้า (shop) : สินค้าแบบ simple มี variant แฝง 1 รายการ (price = base_price) ส่วนแบบ variable
ใช้ `product_attribute` / `product_attribute_value` / `product_variant_value` · สต็อกมาจาก
lot (`product_stock_lot` + `product_stock_movement` ชนิด in/receipt) และ `stock_qty` ของ
product/variant เป็นค่าแคชที่ตรงกับ lot · สินค้าที่ `manage_stock = 0` ไม่มี lot (ขายได้เสมอ)

หลังสร้างใหม่ ให้ตรวจด้วย `php install/cli-fresh.php gcms15c_x gcms --type=<type> --strict --datas=/tmp/…`
และ `php install/cli-test.php` (หัวข้อด้านบน) แล้ว commit ทั้ง `content.php`, `seed.sql`,
`images.json` และ `datas/`
