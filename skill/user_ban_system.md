# ระบบการแบนบัญชีผู้ใช้ (Permanent User Ban System)

## 1. ภาพรวม

ระบบนี้เป็นระบบสำหรับให้ **Admin สามารถแบนบัญชีผู้ใช้แบบถาวร** และเก็บ **ประวัติการแบน** ของผู้ใช้แต่ละบัญชี สำหรับโปรเจกต์ Snippet Box ที่ใช้ PHP, MySQL, XAMPP, HTML, CSS และ JavaScript

### Requirements สำคัญ

- รองรับเฉพาะการแบนถาวร
- ไม่มีวันหมดอายุการแบน
- เก็บประวัติการแบนในตารางแยก
- บันทึกผู้ใช้ที่ถูกแบน
- บันทึก Admin ที่เป็นผู้แบน
- บันทึกเหตุผล
- บันทึกวันที่และเวลา
- User ที่ถูกแบนไม่สามารถ Login ได้
- เฉพาะ Admin เท่านั้นที่สามารถแบนบัญชีได้

---

## 2. ตาราง `user` ที่มีอยู่แล้ว

ไม่ต้องสร้างตาราง User ใหม่ โดยตารางเดิมมี:

| Column | Type |
|---|---|
| `user_id` | INT, PK, AUTO_INCREMENT |
| `firstname` | VARCHAR(100) |
| `lastname` | VARCHAR(100) |
| `username` | VARCHAR(50) |
| `password` | VARCHAR(255) |
| `created_at` | DATETIME |
| `role` | ENUM('user','admin') |
| `question` | VARCHAR(255) |
| `answer` | VARCHAR(255) |

ให้เพิ่มเพียง `account_status`:

```sql
ALTER TABLE `user`
ADD COLUMN `account_status`
ENUM('active', 'banned')
NOT NULL DEFAULT 'active';
```

ความหมาย:

- `active` = ใช้งานได้
- `banned` = ถูกแบนถาวร

---

## 3. ตาราง `user_bans`

สร้างตารางใหม่เพื่อเก็บประวัติการแบน:

```sql
CREATE TABLE `user_bans` (
    `ban_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `banned_by` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `banned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unbanned_at` DATETIME NULL DEFAULT NULL,
    `unbanned_by` INT NULL DEFAULT NULL,

    CONSTRAINT `fk_user_bans_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `user`(`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_user_bans_admin`
        FOREIGN KEY (`banned_by`)
        REFERENCES `user`(`user_id`)
        ON DELETE RESTRICT,

    CONSTRAINT `fk_user_bans_unban_admin`
        FOREIGN KEY (`unbanned_by`)
        REFERENCES `user`(`user_id`)
        ON DELETE SET NULL
);
```

### Column

| Column | ความหมาย |
|---|---|
| `ban_id` | ID ของรายการแบน |
| `user_id` | ID ของ User ที่ถูกแบน |
| `banned_by` | ID ของ Admin ที่แบน |
| `reason` | เหตุผลการแบน |
| `banned_at` | วันและเวลาที่แบน |
| `unbanned_at` | วันและเวลาที่ปลดแบน (NULL หากยังถูกแบนอยู่) |
| `unbanned_by` | ID ของ Admin ที่ปลดแบน |

**ห้ามเพิ่ม** `expires_at`, `ban_type` หรือระบบแบนชั่วคราว เพราะ Requirement คือแบนถาวรเท่านั้น

---

## 4. ความสัมพันธ์

```text
user
  |
  | 1
  |
  | N
  v
user_bans
```

- `user_bans.user_id` → `user.user_id` = User ที่ถูกแบน
- `user_bans.banned_by` → `user.user_id` = Admin ที่เป็นผู้แบน

---

## 5. Flow การแบน

```text
Admin Login
    ↓
Admin Dashboard
    ↓
จัดการผู้ใช้
    ↓
เลือก User
    ↓
กด "แบนบัญชี"
    ↓
กรอกเหตุผล
    ↓
ยืนยันการแบน
    ↓
ตรวจสอบสิทธิ์ Admin
    ↓
ตรวจสอบ User
    ↓
ตรวจสอบว่า User ยังไม่ถูกแบน
    ↓
INSERT ลง user_bans
    ↓
UPDATE user.account_status = 'banned'
    ↓
แสดง "แบนบัญชีสำเร็จ"
```

การบันทึกควรใช้ Transaction เพื่อให้ INSERT และ UPDATE สำเร็จพร้อมกัน

---

## 6. SQL ตอนแบน

บันทึกประวัติ:

```sql
INSERT INTO `user_bans`
(`user_id`, `banned_by`, `reason`)
VALUES (?, ?, ?);
```

เปลี่ยนสถานะ:

```sql
UPDATE `user`
SET `account_status` = 'banned'
WHERE `user_id` = ?;
```

ควรใช้ Prepared Statement และ Transaction

ตัวอย่าง:

```php
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "INSERT INTO `user_bans`
        (`user_id`, `banned_by`, `reason`)
        VALUES (?, ?, ?)"
    );
    $stmt->bind_param("iis", $user_id, $admin_id, $reason);
    $stmt->execute();

    $stmt = $conn->prepare(
        "UPDATE `user`
        SET `account_status` = 'banned'
        WHERE `user_id` = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}
```

---

## 7. การตรวจสอบก่อนแบน

ต้องตรวจสอบ:

### 7.1 ผู้ดำเนินการต้องเป็น Admin

ตรวจสอบ Session และ Database ไม่ควรเชื่อค่าจากหน้าเว็บเพียงอย่างเดียว

```sql
SELECT `role`
FROM `user`
WHERE `user_id` = ?;
```

ต้องได้:

```text
admin
```

### 7.2 User เป้าหมายต้องมีอยู่จริง

```sql
SELECT `user_id`, `username`, `role`, `account_status`
FROM `user`
WHERE `user_id` = ?;
```

### 7.3 ห้ามแบน User ที่ถูกแบนอยู่แล้ว

ถ้า:

```text
account_status = banned
```

ให้หยุดการทำงานและแจ้ง:

```text
บัญชีนี้ถูกแบนอยู่แล้ว
```

### 7.4 ห้ามแบน Admin

ถ้า:

```text
role = admin
```

ให้ปฏิเสธและแจ้ง:

```text
ไม่สามารถแบนบัญชี Admin ได้
```

### 7.5 เหตุผลต้องไม่ว่าง

```php
$reason = trim($_POST['reason']);

if ($reason === '') {
    exit('กรุณาระบุเหตุผลในการแบน');
}
```

---

## 8. การ Login

หลังจากตรวจสอบ Username และ Password แล้ว ต้องตรวจสอบ `account_status`

```php
if ($user['account_status'] === 'banned') {
    echo "บัญชีของคุณถูกแบนถาวร";
    exit;
}
```

User ที่ถูกแบน:

- ห้ามสร้าง Login Session
- ห้ามเข้า Dashboard
- ห้ามใช้งานระบบ

ข้อความแนะนำ:

```text
บัญชีของคุณถูกระงับการใช้งานถาวร

หากคิดว่าเกิดข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ
```

---

## 9. หน้า Admin จัดการ User

ควรแสดงตารางประมาณนี้:

| User ID | Username | Role | Status | Action |
|---:|---|---|---|---|
| 1 | admin | admin | Active | - |
| 2 | user01 | user | Active | แบน |
| 3 | user02 | user | Banned | ถูกแบนแล้ว |

บัญชี `active` แสดง:

```text
[ แบนบัญชี ]
```

บัญชี `banned` แสดง:

```text
[ ปลดแบน ] [ ดูประวัติ ]
```

---

## 10. Form ยืนยันการแบน

เมื่อกดแบน ให้แสดง:

```text
================================
       ยืนยันการแบนบัญชี
================================

Username:
user123

สถานะ:
Active → Banned

เหตุผลในการแบน:
( ) Spam / Misleading
( ) Inappropriate Content / Violent / Harassment
( ) Malicious Code / Malware
( ) Other

รายละเอียดเพิ่มเติม:
[____________________________]

⚠ การแบนครั้งนี้เป็นการแบนถาวร

[ ยกเลิก ]    [ ยืนยันการแบน ]
```

ต้องไม่อนุญาตให้ส่ง Form หากไม่ได้เลือกเหตุผล หรือเลือก Other แต่ไม่ได้ระบุรายละเอียดเพิ่มเติม

---

## 11. ดูประวัติการแบน

Admin สามารถดูประวัติของ User ได้

```sql
SELECT
    ub.ban_id,
    ub.user_id,
    ub.banned_by,
    ub.reason,
    ub.banned_at,
    u.username AS banned_username,
    a.username AS admin_username
FROM `user_bans` ub
JOIN `user` u
    ON ub.user_id = u.user_id
JOIN `user` a
    ON ub.banned_by = a.user_id
WHERE ub.user_id = ?
ORDER BY ub.banned_at DESC;
```

ตัวอย่าง:

| Ban ID | User | Admin | Reason | Date |
|---:|---|---|---|---|
| 1 | user01 | admin | Spam | 2026-09-01 |
| 2 | user05 | admin | เนื้อหาไม่เหมาะสม | 2026-09-03 |

---

## 12. ดูประวัติการแบนทั้งหมด

```sql
SELECT
    ub.ban_id,
    u.username,
    a.username AS banned_by_username,
    ub.reason,
    ub.banned_at
FROM `user_bans` ub
JOIN `user` u
    ON ub.user_id = u.user_id
JOIN `user` a
    ON ub.banned_by = a.user_id
ORDER BY ub.banned_at DESC;
```

ใช้สำหรับหน้า Admin ที่ต้องการดูว่าเคยมี User คนไหนถูกแบนบ้าง

---

## 13. Security

ต้องปฏิบัติตามข้อกำหนด:

1. ใช้ Prepared Statement
2. ตรวจสอบ Admin ฝั่ง Server
3. Validate เหตุผลก่อนบันทึก
4. ตรวจสอบ User เป้าหมายจาก Database
5. ป้องกันการแบนซ้ำ
6. ป้องกันการแบน Admin
7. ใช้ Transaction สำหรับการบันทึกประวัติและเปลี่ยนสถานะ
8. ไม่ควรใช้ค่าจาก Client เป็นตัวตัดสินสิทธิ์

---

## 14. การปลดแบน (Unban)

Admin สามารถปลดแบนบัญชีผู้ใช้ที่ถูกแบนได้ โดย:
- เปลี่ยน `account_status` ในตาราง `user` กลับเป็น `'active'`
- บันทึกเวลาที่ปลดแบนลงในคอลัมน์ `unbanned_at = NOW()` และ `unbanned_by = admin_id` ในตาราง `user_bans`
- ประวัติการแบนใน `user_bans` ยังคงถูกเก็บรักษาไว้สำหรับตรวจสอบย้อนหลัง
- ผู้ใช้ที่ถูกปลดแบนจะสามารถล็อกอินและใช้งานระบบได้ทันที
- มีกล่องข้อความยืนยันการปลดแบนก่อนบันทึกจริง

---

## 15. Functional Requirements

### FR-01
Admin สามารถดูรายชื่อ User ได้

### FR-02
ระบบแสดงสถานะ Active/Banned

### FR-03
Admin สามารถแบน User แบบถาวรได้

### FR-04
Admin ต้องระบุเหตุผลก่อนแบน

### FR-05
ระบบบันทึก User, Admin, เหตุผล และวันเวลาใน `user_bans`

### FR-06
User ที่ถูกแบนไม่สามารถ Login ได้

### FR-07
Admin สามารถดูประวัติการแบนได้

### FR-08
ระบบป้องกันการแบน User ที่ถูกแบนอยู่แล้ว

### FR-09
ระบบป้องกันการแบนบัญชี Admin

### FR-10
Admin สามารถปลดแบน User ที่ถูกแบนกลับมาเป็นสถานะ Active ได้

### FR-11
ระบบบันทึกเวลาที่ปลดแบน (`unbanned_at`) และ Admin ผู้ปลดแบน (`unbanned_by`) ใน `user_bans`

---

## 16. Acceptance Criteria

- [ ] เพิ่ม `account_status` ในตาราง `user`
- [ ] สร้างตาราง `user_bans` พร้อมคอลัมน์ `unbanned_at` และ `unbanned_by`
- [ ] Admin สามารถดู User ได้
- [ ] Admin สามารถแบน User ได้
- [ ] ต้องกรอกเหตุผลก่อนแบน
- [ ] บันทึกประวัติลง `user_bans`
- [ ] เปลี่ยน `account_status` เป็น `banned`
- [ ] User ที่ถูกแบน Login ไม่ได้
- [ ] แสดงข้อความว่าถูกแบนถาวร
- [ ] ป้องกันการแบนซ้ำ
- [ ] ป้องกันการแบน Admin
- [ ] สามารถดู Admin ที่เป็นผู้แบน
- [ ] สามารถดูเหตุผลและวันที่แบน
- [ ] Admin สามารถปลดแบน User ได้ (ปุ่มปลดแบน)
- [ ] บันทึกเวลาที่ปลดแบนลง `unbanned_at`
- [ ] ใช้ Prepared Statement
- [ ] ใช้ Transaction

---

## 17. SQL สำหรับติดตั้ง

```sql
ALTER TABLE `user`
ADD COLUMN `account_status`
ENUM('active', 'banned')
NOT NULL DEFAULT 'active';

CREATE TABLE `user_bans` (
    `ban_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `banned_by` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `banned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unbanned_at` DATETIME NULL DEFAULT NULL,
    `unbanned_by` INT NULL DEFAULT NULL,

    CONSTRAINT `fk_user_bans_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `user`(`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_user_bans_admin`
        FOREIGN KEY (`banned_by`)
        REFERENCES `user`(`user_id`)
        ON DELETE RESTRICT,

    CONSTRAINT `fk_user_bans_unban_admin`
        FOREIGN KEY (`unbanned_by`)
        REFERENCES `user`(`user_id`)
        ON DELETE SET NULL
);
```

> หากฐานข้อมูลจริงใช้ชื่อตาราง `users` แทน `user` ให้ปรับชื่อให้ตรงกับฐานข้อมูลจริง

---

# 18. Instructions สำหรับ AI ที่นำไฟล์นี้ไปพัฒนา

เมื่อใช้เอกสารนี้เป็น Requirement ให้ปฏิบัติตามดังนี้:

1. ใช้ตาราง `user` เดิม ห้ามสร้างตาราง User ใหม่
2. เพิ่ม `account_status` ใน `user`
3. สร้าง `user_bans` เพื่อเก็บประวัติ
4. รองรับ Permanent Ban และ Admin Unban
5. ห้ามสร้าง `expires_at` หรือ `ban_type`
6. ต้องบันทึก User ที่ถูกแบน
7. ต้องบันทึก Admin ที่แบน
8. ต้องบันทึกเหตุผล
9. ต้องบันทึกวันที่และเวลา
10. User ที่ `account_status = banned` ต้อง Login ไม่ได้
11. ตรวจสอบสิทธิ์ Admin ฝั่ง Server
12. ป้องกันการแบนซ้ำ
13. ป้องกันการแบน Admin
14. ใช้ Prepared Statement
15. ใช้ Transaction ในขั้นตอนแบน
16. รักษาโค้ดและฟังก์ชันเดิมของ Snippet Box และแก้เฉพาะส่วนที่เกี่ยวข้อง
17. หากมีโค้ดเดิมอยู่แล้ว ให้ปรับเข้ากับโครงสร้างเดิม ไม่เขียนระบบใหม่ทับทั้งหมด
18. ก่อนสร้างไฟล์ใหม่ ให้ตรวจสอบโครงสร้างโปรเจกต์และชื่อไฟล์เดิมก่อน
19. ระบบรองรับการปลดแบน (Unban) โดย Admin ผ่านหน้าจัดการผู้ใช้
20. หากมีข้อขัดแย้งระหว่าง Requirement นี้กับการคาดเดาของ AI ให้ยึด Requirement ในเอกสารนี้เป็นหลัก
