# AI Documentation Generation Prompt - Now.js Framework

คู่มือสำหรับใช้ AI สร้างเอกสารคู่มือ Now.js Framework ที่มีคุณภาพ

---

## � โครงสร้างเอกสาร Now.js

```
docs/
├── th/                          # เอกสารภาษาไทย
│   ├── index.md                 # หน้าแรก
│   ├── cache/                   # Cache System
│   │   ├── cache-overview.md
│   │   ├── cachefactory.md
│   │   └── filecache.md
│   ├── connection/              # Database Connection
│   │   ├── connection.md
│   │   ├── connectionmanager.md
│   │   ├── mysqldriver.md
│   │   └── ...
│   ├── utility/                 # Utility Classes
│   │   ├── arraytool.md
│   │   ├── collection.md
│   │   ├── date.md
│   │   └── ...
│   ├── querybuilder/            # Query Builder
│   ├── security/                # Security
│   ├── specialized/             # Specialized Tools
│   └── validation/              # Validation
└── en/                          # เอกสารภาษาอังกฤษ (โครงสร้างเดียวกัน)
```

## 📝 Prompt Template สำหรับ Now.js (ภาษาไทย)

```
คุณเป็นผู้เชี่ยวชาญด้านการเขียนเอกสารทางเทคนิค (Technical Writer)
ที่มีประสบการณ์ในการเขียนคู่มือ Framework และ Library

งานของคุณคือสร้างเอกสารคู่มือสำหรับ Now.js Javascript Framework
ที่มีคุณภาพสูง อ่านง่าย เข้าใจง่าย และใช้งานได้จริง

## ข้อมูล Now.js Javascript Framework:
- ชื่อ: Now.js
- ภาษา: PHP
- เวอร์ชัน: 2.0.0

## หัวข้อที่ต้องการเอกสาร:
- แยกตามไฟล์แต่ละไฟล์ เช่น Now.js\Database
- ให้เป็น Markdown แยกเป็น 2 ภาษา

## โครงสร้างเอกสารที่ต้องการ:

### 1. ภาพรวม (Overview)
- อธิบายว่าหัวข้อนี้คืออะไร
- ใช้ทำอะไร
- ทำไมต้องใช้
- เหมาะกับสถานการณ์ไหน

### 2. การติดตั้ง/เตรียมความพร้อม (Setup)
- ขั้นตอนการติดตั้ง (ถ้ามี)
- Dependencies ที่ต้องการ
- Configuration พื้นฐาน

### 3. การใช้งานพื้นฐาน (Basic Usage)
- ตัวอย่างโค้ดง่ายๆ ที่ใช้งานได้จริง
- อธิบายทีละบรรทัด
- ผลลัพธ์ที่คาดหวัง

### 4. ฟีเจอร์หลัก (Main Features)
- แต่ละฟีเจอร์มีตัวอย่างโค้ด
- Use cases ที่เป็นประโยชน์
- Best practices

### 5. ตัวอย่างขั้นสูง (Advanced Examples)
- Use cases ที่ซับซ้อนกว่า
- การผสมผสานหลายฟีเจอร์
- Performance optimization

### 6. API Reference (ถ้ามี)
- รายการ methods/functions
- Parameters และ return values
- ตัวอย่างการใช้งาน

### 7. ข้อควรระวัง (Gotchas/Common Mistakes)
- ข้อผิดพลาดที่พบบ่อย
- วิธีแก้ไข
- Best practices เพื่อหลีกเลี่ยงปัญหา

### 8. เพิ่มเติม (Additional Resources)
- ลิงก์ไปหัวข้ออื่นที่เกี่ยวข้อง
- External resources
- Community resources

## หลักการเขียน:

1. **ใช้ภาษาที่เข้าใจง่าย**
   - หลีกเลี่ยง jargon ที่ไม่จำเป็น
   - อธิบายศัพท์เทคนิคเมื่อใช้ครั้งแรก
   - ใช้ประโยคสั้นๆ กระชับ

2. **ตัวอย่างโค้ดต้อง:**
   - ใช้งานได้จริง (runnable)
   - มี comments อธิบาย
   - แสดงผลลัพธ์ที่คาดหวัง
   - ครอบคลุม use cases ที่สำคัญ

3. **โครงสร้างชัดเจน:**
   - ใช้ headings แบ่งหมวดหมู่
   - ใช้ bullet points สำหรับรายการ
   - ใช้ code blocks สำหรับโค้ด
   - ใช้ callouts สำหรับข้อมูลสำคัญ

4. **เน้นการใช้งานจริง:**
   - เริ่มจากง่ายไปยาก
   - แสดง real-world examples
   - อธิบาย "ทำไม" ไม่ใช่แค่ "อย่างไร"
   - สามารถใช้เป็นคู่มือสำหรับ AI ได้

5. **รูปแบบ Markdown:**
   - ใช้ syntax ที่ถูกต้อง
   - ใช้ code fences พร้อม language identifier
   - ใช้ tables สำหรับข้อมูลที่เปรียบเทียบ

## ตัวอย่างรูปแบบที่ต้องการ:

```markdown
# Controllers

## ภาพรวม

Controller คือส่วนที่จัดการ HTTP requests และส่ง responses กลับไปยัง client
ใน MVC pattern, Controller ทำหน้าที่เป็นตัวกลางระหว่าง Model และ View

**ใช้เมื่อไหร่:**
- ต้องการจัดการ HTTP requests
- ต้องการแยก business logic ออกจาก routing
- ต้องการโครงสร้างโค้ดที่เป็นระเบียบ

## การสร้าง Controller พื้นฐาน

### ตัวอย่างง่ายๆ

\`\`\`php
<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return view('home', [
            'title' => 'Welcome to Now.js'
        ]);
    }
}
\`\`\`

**อธิบาย:**
- `namespace` - กำหนด namespace ของ controller
- `index()` - method หลักที่จะถูกเรียกเมื่อเข้าหน้า home
- `view()` - helper function สำหรับ render view
- return ค่ากลับเป็น HTML response

### การใช้งาน

\`\`\`php
// routes.php
$router->get('/', [HomeController::class, 'index']);
\`\`\`

**ผลลัพธ์:**
เมื่อเข้า `http://localhost/` จะแสดงหน้า home พร้อมข้อความ "Welcome to Now.js"

## ฟีเจอร์หลัก

### 1. รับ Request Parameters

\`\`\`php
public function show($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->notFound();
    }

    return view('user.show', ['user' => $user]);
}
\`\`\`

### 2. Dependency Injection

\`\`\`php
public function __construct(
    private UserRepository $users,
    private Logger $logger
) {}

public function index()
{
    $this->logger->info('Viewing users list');
    $users = $this->users->all();

    return view('users.index', ['users' => $users]);
}
\`\`\`

## ข้อควรระวัง

⚠️ **อย่าใส่ business logic ใน Controller**
```php
// ❌ ไม่ดี
public function store()
{
    $user = new User();
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user->save();

    // ส่ง email
    mail($user->email, 'Welcome', 'Welcome to our site');

    return redirect('/users');
}

// ✅ ดี
public function store(CreateUserRequest $request)
{
    $user = $this->userService->create($request->validated());

    return redirect('/users')->with('success', 'User created!');
}
\`\`\`

## เพิ่มเติม

- [Routing](routing.md) - เรียนรู้การเชื่อม routes กับ controllers
- [Middleware](middleware.md) - เพิ่ม middleware ให้ controllers
- [Validation](validation.md) - validate input data
```

กรุณาสร้างเอกสารตามโครงสร้างและหลักการข้างต้น
โดยเน้นความชัดเจน ใช้งานได้จริง และเข้าใจง่าย
```

---

## 📝 Prompt Template (English)

```
You are an expert Technical Writer with extensive experience in creating
documentation for Frameworks and Libraries.

Your task is to create high-quality documentation for [Framework/Library Name]
that is clear, easy to understand, and practical.

## Framework/Library Information:
- Name: [Framework Name]
- Language: [PHP/JavaScript/Python/etc.]
- Version: [Current Version]
- Key Features: [Main Features]

## Topic to Document:
[Specify topic, e.g., "Controllers", "Database", "Routing"]

## Required Documentation Structure:

### 1. Overview
- What is this topic about
- What is it used for
- Why use it
- When to use it

### 2. Setup (if applicable)
- Installation steps
- Required dependencies
- Basic configuration

### 3. Basic Usage
- Simple, working code examples
- Line-by-line explanation
- Expected output

### 4. Main Features
- Each feature with code examples
- Useful use cases
- Best practices

### 5. Advanced Examples
- More complex use cases
- Combining multiple features
- Performance optimization

### 6. API Reference (if applicable)
- List of methods/functions
- Parameters and return values
- Usage examples

### 7. Gotchas/Common Mistakes
- Common errors
- How to fix them
- Best practices to avoid issues

### 8. Additional Resources
- Links to related topics
- External resources
- Community resources

## Writing Guidelines:

1. **Use Simple Language**
   - Avoid unnecessary jargon
   - Explain technical terms when first used
   - Use short, concise sentences

2. **Code Examples Must:**
   - Be runnable
   - Include explanatory comments
   - Show expected output
   - Cover important use cases

3. **Clear Structure:**
   - Use headings for categories
   - Use bullet points for lists
   - Use code blocks for code
   - Use callouts for important info

4. **Focus on Practical Usage:**
   - Start simple, progress to advanced
   - Show real-world examples
   - Explain "why" not just "how"

5. **Markdown Format:**
   - Use correct syntax
   - Use code fences with language identifier
   - Use tables for comparisons

## Example Format:

[Same example as Thai version above]

Please create documentation following the structure and guidelines above,
focusing on clarity, practicality, and ease of understanding.
```

---

## 🎯 ตัวอย่างการใช้งาน

### สำหรับ Now.js Framework

```
[ใช้ Prompt Template ข้างต้น แล้วแทนที่:]

- ชื่อ: Now.js Framework
- ภาษา: PHP 7.4+
- เวอร์ชัน: 2.0
- จุดเด่น: Lightweight, Fast, Easy to use, Thai-friendly

หัวข้อที่ต้องการ: Controllers
```

### สำหรับ Laravel

```
- ชื่อ: Laravel
- ภาษา: PHP 8.1+
- เวอร์ชัน: 10.x
- จุดเด่น: Elegant syntax, Rich ecosystem, Great documentation

หัวข้อที่ต้องการ: Eloquent ORM
```

---

## 📋 Checklist สำหรับเอกสารที่ดี

### เนื้อหา (Content)
- [ ] มีภาพรวมที่ชัดเจน
- [ ] มีตัวอย่างโค้ดที่ใช้งานได้จริง
- [ ] อธิบายทีละขั้นตอน
- [ ] มี use cases ที่หลากหลาย
- [ ] มีข้อควรระวังและ best practices
- [ ] มีลิงก์ไปหัวข้ออื่นที่เกี่ยวข้อง

### โค้ด (Code)
- [ ] Syntax ถูกต้อง
- [ ] มี comments อธิบาย
- [ ] แสดงผลลัพธ์ที่คาดหวัง
- [ ] ครอบคลุม edge cases
- [ ] ใช้ naming conventions ที่ดี

### รูปแบบ (Format)
- [ ] ใช้ Markdown syntax ถูกต้อง
- [ ] มี headings แบ่งหมวดหมู่ชัดเจน
- [ ] ใช้ code blocks พร้อม language identifier
- [ ] ใช้ callouts สำหรับข้อมูลสำคัญ
- [ ] มี table of contents (สำหรับเอกสารยาว)

### ภาษา (Language)
- [ ] ใช้ภาษาที่เข้าใจง่าย
- [ ] หลีกเลี่ยง jargon ที่ไม่จำเป็น
- [ ] อธิบายศัพท์เทคนิค
- [ ] ใช้ประโยคสั้นๆ กระชับ
- [ ] ไม่มีข้อผิดพลาดทางไวยากรณ์

---

## 💡 Tips สำหรับเอกสารที่ดี

### 1. เริ่มจากง่ายไปยาก
```markdown
## Basic Example (เริ่มต้น)
[โค้ดง่ายๆ 5-10 บรรทัด]

## Intermediate Example (ปานกลาง)
[โค้ดที่ซับซ้อนขึ้น 20-30 บรรทัด]

## Advanced Example (ขั้นสูง)
[โค้ดที่ซับซ้อน 50+ บรรทัด]
```

### 2. ใช้ Callouts
```markdown
> 💡 **Tip:** ใช้ cache เพื่อเพิ่มประสิทธิภาพ

> ⚠️ **Warning:** อย่าลืม validate input data

> ℹ️ **Note:** Feature นี้ต้องใช้ PHP 7.4+

> ✅ **Best Practice:** ใช้ dependency injection
```

### 3. แสดงผลลัพธ์
```markdown
\`\`\`php
echo "Hello, World!";
\`\`\`

**Output:**
```
Hello, World!
```

### 4. เปรียบเทียบ Good vs Bad
```markdown
## ❌ ไม่ดี
\`\`\`php
$data = $_POST;
$user = new User($data);
\`\`\`

## ✅ ดี
\`\`\`php
$data = $request->validated();
$user = User::create($data);
\`\`\`
```

### 5. ใช้ Tables สำหรับ API Reference
```markdown
| Method | Parameters | Return | Description |
|--------|-----------|--------|-------------|
| `find()` | `int $id` | `?Model` | Find by ID |
| `all()` | - | `Collection` | Get all records |
| `create()` | `array $data` | `Model` | Create new record |
```

---

## 🔄 Workflow การสร้างเอกสาร

### 1. วางแผน (Planning)
- กำหนดหัวข้อที่ต้องการ
- ระบุ target audience
- กำหนดโครงสร้าง

### 2. เขียนร่าง (Drafting)
- ใช้ AI Prompt Template
- สร้างเอกสารร่าง
- เพิ่มตัวอย่างโค้ด

### 3. ทดสอบ (Testing)
- ทดสอบโค้ดทุกตัวอย่าง
- ตรวจสอบ syntax
- ตรวจสอบผลลัพธ์

### 4. ปรับปรุง (Refining)
- แก้ไขข้อผิดพลาด
- เพิ่มรายละเอียด
- ปรับปรุงภาษา

### 5. Review
- ให้คนอื่นอ่าน
- รับ feedback
- แก้ไขตาม feedback

### 6. Publish
- Import เข้าระบบ
- Build dictionary
- ทดสอบการค้นหา

---

## 📚 ตัวอย่างหัวข้อที่ควรมี

### สำหรับ PHP Framework:
- [ ] Getting Started
- [ ] Installation
- [ ] Configuration
- [ ] Routing
- [ ] Controllers
- [ ] Models
- [ ] Views
- [ ] Database
- [ ] Migrations
- [ ] Validation
- [ ] Authentication
- [ ] Authorization
- [ ] Middleware
- [ ] Sessions
- [ ] Cache
- [ ] Logging
- [ ] Testing
- [ ] Deployment

### สำหรับ JavaScript Library:
- [ ] Introduction
- [ ] Installation
- [ ] Quick Start
- [ ] Components
- [ ] Props
- [ ] State Management
- [ ] Events
- [ ] Lifecycle
- [ ] Hooks
- [ ] Routing
- [ ] API Integration
- [ ] Testing
- [ ] Best Practices

---

## 🎨 Template สำหรับหัวข้อต่างๆ

### Template: Getting Started
```markdown
# Getting Started

## ภาพรวม
[อธิบายว่า framework นี้คืออะไร ใช้ทำอะไร]

## ความต้องการของระบบ
- PHP 7.4+
- Composer
- MySQL 5.7+

## การติดตั้ง

### ผ่าน Composer
\`\`\`bash
composer create-project framework/app my-app
cd my-app
\`\`\`

### Configuration
\`\`\`bash
cp .env.example .env
nano .env
\`\`\`

## Hello World

\`\`\`php
<?php
// index.php
echo "Hello, World!";
\`\`\`

## ขั้นตอนถัดไป
- [Routing](routing.md)
- [Controllers](controllers.md)
- [Database](database.md)
```

### Template: API Reference
```markdown
# API Reference: ClassName

## Overview
[อธิบายคลาสนี้ทำอะไร]

## Constructor

\`\`\`php
public function __construct(array $config = [])
\`\`\`

**Parameters:**
- `$config` (array) - Configuration options

**Example:**
\`\`\`php
$instance = new ClassName(['option' => 'value']);
\`\`\`

## Methods

### methodName()

\`\`\`php
public function methodName(string $param): ReturnType
\`\`\`

**Description:**
[อธิบายว่า method นี้ทำอะไร]

**Parameters:**
- `$param` (string) - [อธิบาย parameter]

**Returns:**
- `ReturnType` - [อธิบาย return value]

**Example:**
\`\`\`php
$result = $instance->methodName('value');
\`\`\`

**Throws:**
- `ExceptionType` - [เมื่อไหร่ที่จะ throw]
```

---

## ✅ Final Checklist

ก่อน publish เอกสาร ตรวจสอบ:

- [ ] ทุกตัวอย่างโค้ดทดสอบแล้ว
- [ ] ไม่มี syntax errors
- [ ] ไม่มี typos
- [ ] Links ทั้งหมดใช้งานได้
- [ ] รูปแบบ Markdown ถูกต้อง
- [ ] มี metadata (title, description, category)
- [ ] ภาษาเข้าใจง่าย
- [ ] โครงสร้างชัดเจน
- [ ] ครอบคลุมทุก use cases สำคัญ
- [ ] มี best practices และ warnings

---

## � ข้อกำหนดสำคัญในการตรวจสอบ Source Code

### 1. ตรวจสอบ Source Code ก่อนเขียนเอกสาร (CRITICAL)

**หลักการสำคัญ:**
> ⚠️ **NEVER assume the API!** ต้องตรวจสอบ source code จริงก่อนเขียนเอกสารเสมอ

```markdown
## ขั้นตอนที่ถูกต้อง:

1. **อ่าน source code จริง**
   - เปิดไฟล์ PHP/Class ที่ต้องการเขียนเอกสาร
   - อ่าน method signatures ทั้งหมด
   - ตรวจสอบ parameters, return types, และ visibility
   - อ่าน PHPDoc comments (ถ้ามี)

2. **ตรวจสอบ method signatures**
   ```php
   // ❌ ผิด - สมมติเอง
   public function toPng($code, $width, $height): string

   // ✅ ถูก - ตรวจสอบจาก source code
   public function toPng(): string
   ```

3. **ตรวจสอบ constructor และ factory methods**
   ```php
   // ต้องรู้ว่า parameters อะไรถูกส่งผ่าน constructor หรือ factory method
   public static function create($code, $height = 30, $fontSize = 0): static
   protected function __construct($code, $height, $fontSize = 0)
   ```

4. **ทดสอบตัวอย่างโค้ดทุกตัวอย่าง**
   - สร้างไฟล์ test script
   - รันโค้ดจริง
   - ตรวจสอบผลลัพธ์
   - แก้ไขถ้าไม่ทำงาน
```

### 2. ตัวอย่างการตรวจสอบที่ถูกต้อง

#### ตัวอย่างที่ 1: Barcode Class

```markdown
## ขั้นตอนการตรวจสอบ:

1. **เปิดไฟล์:** `Kotchasan/Barcode.php`

2. **ตรวจสอบ factory method:**
   ```php
   // จาก source code
   public static function create($code, $height = 30, $fontSize = 0)
   ```
   - Parameter 1: `$code` - รหัสบาร์โค้ด
   - Parameter 2: `$height` - ความสูง (default: 30)
   - Parameter 3: `$fontSize` - ขนาดฟอนต์ (default: 0)

3. **ตรวจสอบ output method:**
   ```php
   // จาก source code
   public function toPng()
   ```
   - ⚠️ ไม่มี parameters!
   - Return: PNG image data (string)

4. **การใช้งานที่ถูกต้อง:**
   ```php
   // ✅ ถูกต้อง
   $barcode = Barcode::create('123456', 50, 12);
   $imageData = $barcode->toPng();

   // ❌ ผิด - toPng() ไม่รับ parameters
   $imageData = $barcode->toPng('123456', 200, 80);
   ```
```

#### ตัวอย่างที่ 2: ตรวจสอบ Properties

```markdown
## ขั้นตอน:

1. **อ่าน class properties:**
   ```php
   // จาก source code
   private $height;
   private $bar_width = 1;
   private $width = 0;
   private $datas;
   private $code;
   public $font = ROOT_PATH.'skin/fonts/thsarabunnew-webfont.ttf';
   private $fontSize = 0;
   ```

2. **เขียนเอกสาร properties:**
   - แยกระหว่าง public, protected, private
   - ระบุ default values
   - อธิบายความหมายและการใช้งาน
```

### 3. การทดสอบตัวอย่างโค้ด (MANDATORY)

**ทุกตัวอย่างโค้ดใน documentation ต้องทดสอบให้ทำงานได้ 100%**

```markdown
## กระบวนการทดสอบ:

### Step 1: สร้างไฟล์ทดสอบ

```php
// test_barcode.php

require_once 'vendor/autoload.php'; // หรือ include autoloader

use Kotchasan\Barcode;

// Test 1: Basic usage
echo "Test 1: Basic barcode generation\n";
try {
    $barcode = Barcode::create('123456789');
    $imageData = $barcode->toPng();

    if (strlen($imageData) > 0) {
        echo "✅ Success: Generated " . strlen($imageData) . " bytes\n";
        file_put_contents('test_barcode.png', $imageData);
    } else {
        echo "❌ Failed: Empty image data\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Custom dimensions
echo "\nTest 2: Custom dimensions\n";
try {
    $barcode = Barcode::create('PRODUCT001', 80, 14);
    $imageData = $barcode->toPng();

    if (strlen($imageData) > 0) {
        echo "✅ Success: Generated with custom size\n";
        file_put_contents('test_barcode_custom.png', $imageData);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ All tests completed!\n";
```

### Step 2: รันการทดสอบ

```bash
php test_barcode.php
```

### Step 3: ตรวจสอบผลลัพธ์

```
Expected Output:
Test 1: Basic barcode generation
✅ Success: Generated 1234 bytes

Test 2: Custom dimensions
✅ Success: Generated with custom size

✅ All tests completed!
```

### Step 4: ตรวจสอบไฟล์ output

```bash
ls -lh test_barcode*.png
file test_barcode.png
```

Expected:
```
-rw-r--r-- 1 user user 1.2K test_barcode.png
-rw-r--r-- 1 user user 1.5K test_barcode_custom.png
test_barcode.png: PNG image data, 100 x 30, 8-bit/color RGB
```
```

### 4. ข้อกำหนดสำหรับตัวอย่างโค้ด

```markdown
## ทุกตัวอย่างโค้ดต้องมี:

1. **Complete Code**
   - ไม่มี `...` หรือ placeholders
   - มี `<?php` opening tag
   - มี use statements ที่จำเป็น
   - สามารถ copy-paste ไปรันได้เลย

2. **Error Handling**
   ```php
   // ✅ ดี - มี error handling
   try {
       $barcode = Barcode::create('123456');
       $data = $barcode->toPng();
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }

   // ❌ ไม่ดี - ไม่มี error handling
   $barcode = Barcode::create('123456');
   $data = $barcode->toPng();
   ```

3. **Comments อธิบาย**
   ```php
   // สร้าง Barcode instance ด้วยรหัส '123456'
   // ความสูง 50 pixels, แสดงข้อความด้วยฟอนต์ขนาด 12
   $barcode = Barcode::create('123456', 50, 12);

   // สร้างภาพ PNG
   $imageData = $barcode->toPng();

   // บันทึกเป็นไฟล์
   file_put_contents('barcode.png', $imageData);
   ```

4. **Expected Output**
   ```php
   echo $result;
   ```

   **Output:**
   ```
   Hello, World!
   ```

5. **Dependencies ที่ชัดเจน**
   ```markdown
   > 📋 **Requirements:**
   > - PHP 7.4+
   > - GD extension
   > - Font file: `skin/fonts/thsarabunnew-webfont.ttf`
   ```
```

### 5. Checklist ก่อนเขียนเอกสาร

```markdown
ก่อนเริ่มเขียนเอกสารทุกครั้ง:

- [ ] **เปิด source code file แล้ว**
- [ ] **อ่าน class definition แล้ว**
- [ ] **ตรวจสอบ all public methods แล้ว**
- [ ] **ตรวจสอบ constructor/factory methods แล้ว**
- [ ] **อ่าน PHPDoc comments แล้ว (ถ้ามี)**
- [ ] **ตรวจสอบ parent class/interfaces แล้ว (ถ้ามี)**
- [ ] **ดู method signatures อย่างละเอียดแล้ว**
- [ ] **เข้าใจ parameter types และ return types แล้ว**
```

### 6. Common Mistakes ที่ต้องหลีกเลี่ยง

```markdown
## ❌ ข้อผิดพลาดที่พบบ่อย:

### 1. สมมติ API โดยไม่ดู source code
```php
// ❌ ผิด - สมมติว่า toPng() รับ parameters
$barcode->toPng($code, $width, $height);

// ✅ ถูก - check source code ก่อน
$barcode = Barcode::create($code, $height, $fontSize);
$barcode->toPng();
```

### 2. ใช้ method ที่ไม่มีจริง
```php
// ❌ ผิด - method ไม่มีใน source code
$barcode->setWidth(200);
$barcode->setHeight(80);

// ✅ ถูก - ส่งผ่าน create() แทน
$barcode = Barcode::create($code, 80);
```

### 3. Parameters ผิด type หรือผิด order
```php
// ❌ ผิด - order ผิด
$barcode = Barcode::create($height, $code, $fontSize);

// ✅ ถูก
$barcode = Barcode::create($code, $height, $fontSize);
```

### 4. Return type ผิด
```php
// ❌ ผิด - toPng() return string ไม่ใช่ boolean
if ($barcode->toPng() === true) { ... }

// ✅ ถูก
$imageData = $barcode->toPng(); // Returns string (PNG binary data)
if (strlen($imageData) > 0) { ... }
```

### 5. ไม่ทดสอบโค้ด
```php
// ❌ อันตราย - เขียนโค้ดโดยไม่ทดสอบ
// อาจมี syntax error หรือ logic error

// ✅ ปลอดภัย - ทดสอบก่อน publish
// รันโค้ดจริง ตรวจสอบผลลัพธ์
```
```

### 7. Template สำหรับ API Documentation

```markdown
# Class Name

## Namespace

```php
namespace Full\\Namespace\\Path;
```

## Overview

[อธิบายว่า class นี้ทำอะไร]

## Requirements

- PHP 7.4+
- Required extensions: [list]
- Dependencies: [list]

## Class Properties

### Public Properties

```php
public string $propertyName = 'default value';
```

- **Type:** string
- **Default:** 'default value'
- **Description:** [อธิบาย]

### Private Properties

```php
private int $internalProperty;
```

- **Type:** int
- **Description:** [อธิบาย - เพื่อความเข้าใจเท่านั้น]

## Constructor

```php
protected function __construct(
    string $param1,
    int $param2 = 10
)
```

**⚠️ Note:** Constructor is protected. Use factory method `create()` instead.

## Factory Methods

### create()

```php
public static function create(
    string $param1,
    int $param2 = 10
): static
```

**Parameters:**
- `$param1` (string, required) - [อธิบาย]
- `$param2` (int, optional, default: 10) - [อธิบาย]

**Returns:** Instance of this class

**Example:**
```php
use Full\\Namespace\\Path\\ClassName;

$instance = ClassName::create('value', 20);
```

## Public Methods

### methodName()

```php
public function methodName(
    string $input,
    array $options = []
): ReturnType
```

**Parameters:**
- `$input` (string, required) - [อธิบาย]
- `$options` (array, optional) - [อธิบาย]
  - `'option1'` (int) - [อธิบาย]
  - `'option2'` (bool) - [อธิบาย]

**Returns:** ReturnType - [อธิบาย]

**Throws:**
- `ExceptionType` - [เมื่อไหร่]

**Example:**
```php
$result = $instance->methodName('input', [
    'option1' => 100,
    'option2' => true
]);

// Expected result
var_dump($result);
```

**Output:**
```
[แสดงผลลัพธ์ที่คาดหวัง]
```

## Complete Examples

### Example 1: Basic Usage

```php
require_once 'vendor/autoload.php';

use Full\\Namespace\\Path\\ClassName;

try {
    // Step 1: Create instance
    $instance = ClassName::create('value');

    // Step 2: Call method
    $result = $instance->methodName('input');

    // Step 3: Process result
    echo $result;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Example 2: Advanced Usage

[ตัวอย่างที่ซับซ้อนกว่า]

## Best Practices

✅ **DO:**
- [แนะนำ]

❌ **DON'T:**
- [สิ่งที่ไม่ควรทำ]

## Common Mistakes

[ข้อผิดพลาดที่พบบ่อย พร้อมวิธีแก้]

## Related Classes

- [ClassName](link.md) - [อธิบาย]
```

---

## 📊 การตรวจสอบคุณภาพเอกสาร

### Quality Metrics

```markdown
ก่อน publish ต้องผ่านเกณฑ์:

1. **Accuracy (ความถูกต้อง): 100%**
   - [ ] ทุก method signature ตรงกับ source code
   - [ ] ทุก parameter type ถูกต้อง
   - [ ] ทุก return type ถูกต้อง
   - [ ] ทุก default value ถูกต้อง

2. **Completeness (ความสมบูรณ์): 100%**
   - [ ] ครบทุก public methods
   - [ ] ครบทุก parameters
   - [ ] มีตัวอย่างทุก method สำคัญ
   - [ ] มี error handling

3. **Testability (ทดสอบได้): 100%**
   - [ ] ทุกตัวอย่างรันได้
   - [ ] ทุกตัวอย่างให้ผลลัพธ์ตรงตามที่บอก
   - [ ] ไม่มี syntax error
   - [ ] ไม่มี runtime error

4. **Usability (ใช้งานง่าย): ≥ 90%**
   - [ ] เข้าใจง่าย
   - [ ] มี use cases ที่ชัดเจน
   - [ ] มี best practices
   - [ ] มี troubleshooting guide

5. **Consistency (ความสอดคล้อง): 100%**
   - [ ] เอกสาร TH และ EN มีเนื้อหาเหมือนกัน
   - [ ] ใช้รูปแบบเดียวกันทุกหน้า
   - [ ] Terminology สอดคล้องกัน
```

---

## �📖 Resources

- [Markdown Guide](https://www.markdownguide.org/)
- [Technical Writing Best Practices](https://developers.google.com/tech-writing)
- [Write the Docs](https://www.writethedocs.org/)
- [PHP Documentation Standards](https://www.php.net/manual/en/about.phpdo​c.php)
