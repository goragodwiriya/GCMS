# Stock Management Model

## ภาพรวม

`Product\Stock\Model` เป็น Model class สำหรับจัดการการตัดและคืน stock ของสินค้าตามสถานะ order โดยอัตโนมัติ

## ฟังก์ชันหลัก

### 1. `updateStockOnStatusChange()`

อัปเดท stock เมื่อมีการเปลี่ยนสถานะ order

**พารามิเตอร์:**
- `$orderId` (int): ID ของ order
- `$oldStatus` (int): สถานะเดิมของ order
- `$newStatus` (int): สถานะใหม่ของ order
- `$cutStock` (int, optional): ค่า cut_stock จาก config (ถ้าไม่ระบุจะดึงจาก config อัตโนมัติ)

**การทำงาน:**
- ถ้าสถานะเปลี่ยนจาก **ไม่อยู่ใน cut_stock** → **อยู่ใน cut_stock**: จะตัด stock
- ถ้าสถานะเปลี่ยนจาก **อยู่ใน cut_stock** → **ไม่อยู่ใน cut_stock**: จะคืน stock
- ถ้าสถานะยังคงอยู่ในกลุ่มเดิม: ไม่ทำอะไร

**ตัวอย่างการใช้งาน:**
```php
// อัปเดท stock เมื่อเปลี่ยนสถานะจาก 1 เป็น 2
\Product\Stock\Model::updateStockOnStatusChange($orderId, 1, 2);
```

### 2. `cutStockForNewOrder()`

ตัด stock สำหรับ order ใหม่ (ใช้ตอนสร้าง order)

**พารามิเตอร์:**
- `$orderId` (int): ID ของ order
- `$orderStatus` (int): สถานะของ order
- `$cartItems` (array): รายการสินค้าจาก cart
- `$cutStock` (int, optional): ค่า cut_stock จาก config

**ตัวอย่างการใช้งาน:**
```php
// ตัด stock สำหรับ order ใหม่
\Product\Stock\Model::cutStockForNewOrder($orderId, $orderStatus, $cartItems);
```

### 3. `decreaseStock()`

ตัด stock สินค้า

**พารามิเตอร์:**
- `$productId` (int): ID ของสินค้า
- `$quantity` (int): จำนวนที่ต้องการตัด

**ตัวอย่างการใช้งาน:**
```php
// ตัด stock สินค้า ID 123 จำนวน 5 ชิ้น
\Product\Stock\Model::decreaseStock(123, 5);
```

### 4. `increaseStock()`

คืน stock สินค้า

**พารามิเตอร์:**
- `$productId` (int): ID ของสินค้า
- `$quantity` (int): จำนวนที่ต้องการคืน

**ตัวอย่างการใช้งาน:**
```php
// คืน stock สินค้า ID 123 จำนวน 5 ชิ้น
\Product\Stock\Model::increaseStock(123, 5);
```

## การใช้งานใน Controllers

### ใน checkout.php (สร้าง order ใหม่)

```php
// บันทึกรายการสินค้าใน cart
foreach ($cart_items as $item) {
    $db->insert('cart', [
        'order_id' => $order_id,
        'product_id' => $item['product_id'],
        // ... fields อื่นๆ
    ]);
}

// ตัด stock โดยอัตโนมัติตามสถานะ order
\Product\Stock\Model::cutStockForNewOrder($order_id, $save['order']['order_status'], $cart_items);
```

### ใน order.php (แก้ไขสถานะ order)

```php
// ตรวจสอบว่ามีการเปลี่ยนสถานะหรือไม่
if (isset($save['order_status']) && $save['order_status'] != $order->order_status) {
    // อัปเดท stock อัตโนมัติตามสถานะใหม่
    \Product\Stock\Model::updateStockOnStatusChange(
        $order->id,
        $order->order_status,  // สถานะเดิม
        $save['order_status']   // สถานะใหม่
    );
}

// บันทึก order
$id = \Product\Order\Model::save($db, $order->id, $save);
```

## การตั้งค่า cut_stock

ค่า `cut_stock` ใน config จะกำหนดว่าสถานะ order ไหนบ้างที่ต้องตัด stock

**ตัวอย่าง:**
```php
// ใน config
$config['cut_stock'] = 2;
```

**ความหมาย:**
- สถานะ 1, 2 → **อยู่ใน cut_stock** (ตัด stock)
- สถานะ 3, 4, 5 → **ไม่อยู่ใน cut_stock** (ไม่ตัด stock)

## กรณีการใช้งานจริง

### กรณีที่ 1: สร้าง order ใหม่ (สถานะ 1 - รอชำระเงิน)
- ระบบจะตัด stock อัตโนมัติถ้า `cut_stock >= 1`

### กรณีที่ 2: ชำระเงินแล้ว (เปลี่ยนจากสถานะ 1 → 2)
- ถ้า `cut_stock = 1`: สถานะ 1 และ 2 อยู่ในกลุ่มเดิม → ไม่ทำอะไร
- ถ้า `cut_stock = 2`: ยังคงอยู่ในกลุ่ม cut_stock → ไม่ทำอะไร

### กรณีที่ 3: ยกเลิก order (เปลี่ยนจากสถานะ 2 → 5)
- ถ้า `cut_stock = 2`: เปลี่ยนจากอยู่ใน cut_stock → ไม่อยู่ใน cut_stock → **คืน stock**

### กรณีที่ 4: กู้คืน order (เปลี่ยนจากสถานะ 5 → 2)
- ถ้า `cut_stock = 2`: เปลี่ยนจากไม่อยู่ใน cut_stock → อยู่ใน cut_stock → **ตัด stock**

## ข้อควรระวัง

1. **ไม่ให้ stock เป็นลบ**: Method `decreaseStock()` จะตรวจสอบไม่ให้ stock น้อยกว่า 0
2. **Transaction**: ควรใช้ database transaction เมื่อมีการอัปเดท stock เพื่อความปลอดภัย
3. **Race Condition**: ในระบบที่มี traffic สูง อาจต้องใช้ database locking

## License

Copyright 2026 Goragod.com
License: https://www.kotchasan.com/license/
