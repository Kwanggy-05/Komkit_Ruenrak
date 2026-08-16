---

## 3. รายละเอียดการทำงานของแต่ละส่วน (Functional Requirements)

### 3.1 ฝั่งผู้ใช้ทั่วไป (User Facing - Client Side)
* **Report Trigger:** มีปุ่มจุด 3 จุด (Action Menu) หรือปุ่ม "รายงาน (Report)" ที่การ์ดของแต่ละโพสต์
* **Report Modal:** เมื่อกดแล้วจะแสดงหน้าต่าง Popup ให้เลือกเหตุผล:
  * Spam / ขยะ / หลอกลวง
  * Harassment / คุกคาม / Hate Speech
  * Inappropriate Content / เนื้อหาลามกอนาจาร / รุนแรง
  * Copyright Violation / ละเมิดลิขสิทธิ์
  * Other (มีช่อง Textarea ให้พิมพ์รายละเอียดเพิ่มเติม)
* **Prevention & UX:**
  * ป้องกันการรายงานซ้ำ (User เดิม ไม่สามารถกด Report โพสต์เดิมซ้ำได้)
  * ข้อความตอบกลับสำเร็จ: "ขอบคุณสำหรับการรายงาน เราจะดำเนินการตรวจสอบโดยเร็ว"

### 3.2 ฝั่งระบบ Backend & Database Logic
* **Report Ingestion:** สร้างเรคคอร์ดใหม่ในตาราง `reports` พร้อมสถานะเริ่มต้นเป็น `PENDING`
* **Auto-Flagging (Optional/Rule-based):** หากโพสต์ใดได้รับการรายงานเกิน $N$ ครั้ง (เช่น 5 ครั้ง) ให้ซ่อนโพสต์ชั่วคราว (`status = 'UNDER_REVIEW'`) รอแอดมินตัดสิน
* **Admin Notification:** ส่ง Notification ไปยัง Admin Dashboard, Email, หรือระบบอย่าง Discord/Slack Webhook เมื่อมีรายงานเข้ามา

### 3.3 ฝั่งผู้ดูแลระบบ (Admin Moderation Dashboard)
* **Report Management Table:** หน้าตารางรายการ Report ที่สามารถ:
  * Filter ตามสถานะ: `PENDING`, `RESOLVED`, `REJECTED`
  * Filter ตามหมวดหมู่เหตุผล หรือค้นหาตาม Post ID / User ID
* **Detail Modal / Inspection View:**
  * แสดงเนื้อหาโพสต์ต้นฉบับ (ข้อความ, รูปภาพ, ข้อมูลผู้โพสต์)
  * แสดงรายการผู้ที่เข้ามารายงานทั้งหมดพร้อมเหตุผลและเวลา
* **Action Buttons:**
  * **Approve & Delete Post (ดำเนินการลบ):**
    * อัปเดตสถานะโพสต์เป็น `DELETED` (Soft Delete)
    * อัปเดตสถานะ Report ทั้งหมดของโพสต์นี้เป็น `RESOLVED`
    * บันทึก Action Log ว่า Admin คนไหนเป็นคนลบ เวลาใด พร้อมหมายเหตุ
  * **Dismiss / Reject Report (ยกเลิกคำร้อง):**
    * โพสต์ยังคงแสดงผลตามปกติ
    * อัปเดตสถานะ Report เป็น `REJECTED`

---

## 4. โครงสร้างฐานข้อมูล (Database Schema)

### ตาราง `posts` (โพสต์)
```sql
CREATE TABLE posts (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    content TEXT NOT NULL,
    media_url VARCHAR(255),
    status ENUM('ACTIVE', 'UNDER_REVIEW', 'DELETED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);