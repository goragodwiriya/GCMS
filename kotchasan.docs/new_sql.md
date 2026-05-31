# Kotchasan Sql Helper

`Kotchasan\Database\Sql` เป็นชุด helper สำหรับสร้าง SQL expression เพื่อส่งเข้า Kotchasan QueryBuilder เช่น `select()`, `where()`, `orderBy()` และ `groupBy()`

หน้านี้อ้างอิงจาก implementation ปัจจุบันจริงใน `Kotchasan/Database/Sql.php` และ driver formatter ภายใต้ `Kotchasan/Connection/`

---

## เริ่มต้นใช้งาน

```php
use Kotchasan\Database\Sql;

$query->select([
    'id',
    Sql::COUNT('*', 'total'),
    Sql::SUM('amount', 'total_amount'),
]);
```

---

## กติกาการตีความค่า

สำหรับ helper ที่คืน `SqlFunction` ระบบจะตีความค่าประมาณนี้:

- string ที่หน้าตาเหมือน identifier เช่น `name` หรือ `table.column` จะถูกมองเป็นชื่อคอลัมน์
- string ที่มีช่องว่าง ขีด หรืออักขระอื่น จะถูกมองเป็น literal string
- ถ้า literal เป็นคำธรรมดาที่หน้าตาเหมือนชื่อคอลัมน์ ให้ส่งเป็น `Sql::raw("'value'")`
- ใช้ `Sql::column()` เมื่อต้องการบังคับให้ระบบ quote เป็น identifier แบบชัดเจน
- ใช้ `Sql::raw()` เมื่อต้องการ expression ที่ helper มาตรฐานยังไม่มี

```php
Sql::column('U.name');
Sql::raw('SUM(amount) / NULLIF(SUM(qty), 0)');
```

---

## สรุประดับการรองรับข้ามฐานข้อมูล

| ระดับ | Methods | หมายเหตุ |
|-------|---------|----------|
| รองรับผ่าน driver formatter | `COUNT`, `SUM`, `AVG`, `MIN`, `MAX`, `DATE`, `TIME`, `YEAR`, `QUARTER`, `MONTH`, `WEEK`, `DAY`, `HOUR`, `MINUTE`, `SECOND`, `DATEDIFF`, `TIMEDIFF`, `DATE_ADD`, `DATE_SUB`, `FIND_IN_SET`, `LPAD`, `RPAD`, `NULLIF`, `IFNULL`, `COALESCE`, `IF_EXPR`, `CASE_WHEN`, `ABS`, `CEIL`, `FLOOR`, `ROUND` | มี implementation แยกตาม driver |
| รองรับแต่มีข้อควรระวัง | `DATE_FORMAT`, `STR_TO_DATE`, `GROUP_CONCAT`, `TIMESTAMPDIFF`, `CONCAT`, `CURDATE`, `CURTIME`, `NOW`, `RAND` | API เดียวกัน แต่ SQL และ semantics ต่างกันบางส่วน |
| เอนเอียงทาง MySQL | `POSITION`, `FORMAT`, `NEXT` | methods กลุ่มนี้ประกอบ SQL ตรงๆ ไม่ได้ normalize ครบทุก driver |

---

## หมวดฟังก์ชัน

| หมวด | Methods |
|------|---------|
| Aggregate | `COUNT`, `SUM`, `AVG`, `MIN`, `MAX`, `DISTINCT` |
| Date and Time | `NOW`, `CURDATE`, `CURTIME`, `DATE`, `TIME`, `YEAR`, `QUARTER`, `MONTH`, `WEEK`, `DAY`, `HOUR`, `MINUTE`, `SECOND`, `DATE_FORMAT`, `STR_TO_DATE`, `DATEDIFF`, `TIMEDIFF`, `TIMESTAMPDIFF`, `DATE_ADD`, `DATE_SUB` |
| String | `LENGTH`, `UPPER`, `LOWER`, `TRIM`, `LTRIM`, `RTRIM`, `SUBSTRING`, `REPLACE`, `CONCAT`, `GROUP_CONCAT`, `LPAD`, `RPAD`, `POSITION` |
| Numeric | `ABS`, `CEIL`, `FLOOR`, `ROUND`, `FORMAT`, `RAND` |
| Null and Conditional | `IFNULL`, `NULLIF`, `COALESCE`, `ISNULL`, `ISNOTNULL`, `IF_EXPR`, `CASE_WHEN` |
| Set and List | `IN`, `NOT_IN`, `BETWEEN`, `FIND_IN_SET` |
| Utility | `raw`, `column`, `NEXT`, `extractSort` |

---

## ตัวอย่างที่ใช้บ่อย

### คำนวณ Aggregate

```php
$query->select([
    Sql::COUNT('*', 'total'),
    Sql::SUM('amount', 'total_amount'),
    Sql::AVG('score', 'avg_score'),
]);
```

### บวกช่วงเวลาให้วันที่

```php
$query->select([
    'member_id',
    Sql::DATE_ADD('register_date', 1, 'YEAR', 'expire_date'),
    Sql::TIMESTAMPDIFF('DAY', Sql::CURDATE(), Sql::DATE_ADD('register_date', 1, 'YEAR'), 'days_left'),
]);
```

### จัดการค่า NULL

```php
$query->select([
    Sql::IFNULL('nickname', 'first_name', 'display_name'),
    Sql::COALESCE(['nickname', 'first_name', Sql::raw("'unknown'")], 'name_fallback'),
    Sql::NULLIF('qty', 0, 'safe_qty'),
]);
```

### เช็กช่วงและสมาชิกในชุดข้อมูล

```php
$query->where(Sql::IN('status', [1, 2, 3]));
$query->where(Sql::NOT_IN('status', [0, 9]));
$query->where(Sql::BETWEEN('price', 100, 500));
```

### ค้นหาในคอลัมน์ comma-separated

ให้ใช้ `FIND_IN_SET` เป็นหลักในแบบตรวจว่าพบหรือไม่

```php
$query->where(Sql::raw('FIND_IN_SET(5, `tag_ids`) > 0'));
```

### กรณี helper ยังไม่พอ

```php
$query->select([
    Sql::raw('SUM(`revenue`) / NULLIF(SUM(`orders`), 0)'),
]);
```

---

## หมายเหตุราย driver

### `DATE_FORMAT`

- MySQL ใช้ `DATE_FORMAT(column, format)`
- PostgreSQL ใช้ `TO_CHAR(column, format)`
- SQLite ใช้ `strftime(format, column)`
- MSSQL ใช้ `FORMAT(column, format)`

### `STR_TO_DATE`

- MySQL ใช้ `STR_TO_DATE(value, format)`
- PostgreSQL ใช้ `TO_TIMESTAMP(value, format)`
- SQLite map เป็น `DATETIME(value)` และไม่ได้ใช้ format string จริง
- MSSQL map เป็น `TRY_CONVERT(DATETIME, value)` และไม่ได้ใช้ format string จริง

### `TIMESTAMPDIFF`

- MySQL และ MSSQL ใช้ unit ที่ส่งเข้าไปจริง
- PostgreSQL map เป็น `EXTRACT(unit FROM (end - start))`
- SQLite ปัจจุบันคืนค่า `JULIANDAY(end) - JULIANDAY(start)` โดยไม่เปลี่ยนตาม unit ที่ส่งเข้าไป ดังนั้นเชื่อถือได้เฉพาะงานระดับวัน เว้นแต่จะมีการแปลงค่าต่อเอง

### `GROUP_CONCAT`

- PostgreSQL และ MSSQL map เป็น `STRING_AGG`
- SQLite ใช้ `GROUP_CONCAT` แต่ implementation ปัจจุบันยังไม่ใช้พารามิเตอร์ `order`

### `FIND_IN_SET`

- MySQL คืน token index แบบ 1-based
- PostgreSQL ใช้ `ARRAY_POSITION(...)` และคืน token index เช่นกัน
- SQLite และ MSSQL จำลองด้วย string-position function ดังนั้นค่าตัวเลขที่ได้เป็นตำแหน่งอักขระ ไม่ใช่ลำดับ token
- ถ้าต้องการเขียนให้ข้ามฐานข้อมูลได้ ให้ใช้ในเชิง boolean เช่น `> 0`

### `POSITION`

`Sql::POSITION()` สร้าง `LOCATE(...)` ตรงๆ จึงควรมองว่าเป็น helper ที่เอนเอียงไปทาง MySQL ไม่ใช่ helper แบบ portable เต็มตัว

### `FORMAT`

`Sql::FORMAT()` สร้าง `FORMAT(column, format)` ตรงๆ และไม่ได้ normalize ให้ทุก driver

### `NEXT`

`Sql::NEXT()` สร้าง SQL โดยใช้ backticks และ `IFNULL(...)` แบบ MySQL จึงควรใช้กับฐานข้อมูลสาย MySQL เป็นหลัก

---

## ตัวช่วยเรื่องการ sort

`Sql::extractSort()` ใช้ sanitize sort input จากผู้ใช้โดยเทียบกับ allowlist ของคอลัมน์

```php
$sort = Sql::extractSort(
    ['name', 'price', 'created_at'],
    $request->get('sort', ''),
    ['created_at DESC']
);
```

ผลลัพธ์อาจเป็น string เดียว, array ของ sort strings หรือ default ที่ส่งเข้าไป
