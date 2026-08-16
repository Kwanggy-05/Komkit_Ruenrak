# Spec: ระบบดูรายชื่อผู้ใช้ที่สมัครสมาชิก (Admin User Management)

## 1. จุดประสงค์ (Objective)
ระบบฝั่ง Admin Dashboard สำหรับตรวจสอบ ค้นหา กรอง และจัดการสถานะบัญชีผู้ใช้งานที่สมัครสมาชิกเข้ามาในระบบ

---

## 2. โครงสร้างฐานข้อมูล (Database Schema - `users`)
- `id` (UUID / Primary Key)
- `email` (String, Unique)
- `password_hash` (String, Hashed)
- `first_name`, `last_name` (String)
- `phone_number` (String, Nullable)
- `role` (Enum: `USER`, `ADMIN`, `SUPER_ADMIN` / Default: `USER`)
- `status` (Enum: `ACTIVE`, `PENDING`, `SUSPENDED`, `BANNED` / Default: `ACTIVE`)
- `created_at` (Timestamp / วันที่สมัคร)
- `last_login_at` (Timestamp / เข้าใช้งานล่าสุด)

---

## 3. API Endpoints ที่จำเป็น (Admin Only)
- **`GET /api/admin/users`**: ดึงรายชื่อสมาชิก
  - *Query Params*: `page`, `limit`, `search` (ชื่อ/อีเมล), `status`, `role`, `sortBy`, `sortOrder`
- **`GET /api/admin/users/:id`**: ดูรายละเอียดรายบุคคล
- **`PATCH /api/admin/users/:id/status`**: อัปเดตสถานะ (`ACTIVE`, `SUSPENDED`, `BANNED`)
- **`GET /api/admin/users/stats`**: สรุปสถิติ (ยอดสมัครรวม, สมัครวันนี้, รอตรวจสอบ)

---

## 4. องค์ประกอบหน้าจอ (UI Components)
1. **Summary Cards**: แสดงจำนวนสมาชิกทั้งหมด, ผู้ใช้ใหม่วันนี้, บัญชีที่ถูกระงับ
2. **Filter & Search Bar**: กล่อง Search แบบ Realtime/Debounce + Dropdown เลือกสถานะและบทบาท
3. **User Data Table**: 
   - คอลัมน์: ข้อมูลผู้ใช้ (ชื่อ/ID), อีเมล/เบอร์โทร, บทบาท, สถานะ (Badge สี), วันที่สมัคร, ปุ่ม Actions
4. **Action Menu**: ดูประวัติ (Detail Drawer), เปลี่ยนสถานะ (Suspend/Ban), รีเซ็ตรหัสผ่าน
5. **Pagination Controls**: ปุ่มเปลี่ยนหน้า และตัวเลือกจำนวนแถวต่อหน้า

---

## 5. กฎความปลอดภัย (Security Rules)
- ตรวจสอบสิทธิ์ (RBAC Middleware): เฉพาะ Role `ADMIN` หรือ `SUPER_ADMIN` เท่านั้นที่เรียก API ได้
- ป้องกันข้อมูลรั่วไหล: **ห้ามส่ง `password_hash`** ออกมาใน API response โดยเด็ดขาด
- บันทึกประวัติ (Audit Trail): บันทึก Log ทุกครั้งที่มี Admin สั่งแก้ไขสถานะหรือแบนผู้ใช้