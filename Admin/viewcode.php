<?php
/**
 * ==============================================================================================
 * หน้าดูโค้ด Snippet สำหรับผู้ดูแลระบบ (Admin View Snippet)
 * ==============================================================================================
 * วัตถุประสงค์:
 *   - ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin)
 *   - ดึงและแสดงรายละเอียดของ Snippet โดยไม่มีการจำกัดสิทธิ์การมองเห็น (Visibility Bypass)
 *     เพื่อให้ Admin สามารถตรวจสอบเนื้อหา โค้ด คำอธิบาย และรูปภาพได้ทั้งหมด
 *   - รองรับการย้อนกลับไปยังหน้าที่เรียกมา (จัดการ Snippet หรือ จัดการคำร้องอุทธรณ์)
 *
 * โครงสร้างไฟล์:
 *   1. ส่วนการตรวจสอบสิทธิ์: เรียกใช้ check_admin.php
 *   2. ส่วนประมวลผล URL ย้อนกลับ: ตรวจสอบพารามิเตอร์ `from` หรือ HTTP Referer
 *   3. ส่วนดึงข้อมูล Snippet: เรียกใช้เมธอด getSnippetByIdAdmin() จาก Control
 *   4. ส่วน UI แสดงผล (Readonly): ข้อมูลผู้สร้าง, ชื่อเรื่อง, ภาษา, โค้ด, คำอธิบาย, Tags, การมองเห็น, รูปภาพ
 *   5. ส่วน JavaScript: จัดการนับบรรทัดโค้ด, ขยาย Textarea อัตโนมัติ, คัดลอกโค้ด, และ Lightbox ดูรูปขยาย
 * ==============================================================================================
 */

// ส่วนที่ 1: ตรวจสอบสิทธิ์ Admin
require_once __DIR__ . '/check_admin.php';

$snippet = null;

// ส่วนที่ 2: ตรวจสอบ URL ย้อนกลับ (Back URL)
$backUrl = 'manage_snippets.php';
if ((isset($_GET['from']) && $_GET['from'] === 'appeals') || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'manage_appeals.php') !== false)) {
    $backUrl = 'manage_appeals.php';
}

// ส่วนที่ 3: ดึงข้อมูล Snippet จากฐานข้อมูล (Admin View)
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $id = (int)$_GET['view'];
    $snippet = $control->getSnippetByIdAdmin($id);
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($snippet['title'] ?? 'ดู Snippet - Admin'); ?></title>
    <link rel="stylesheet" href="../style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style/createsnippetcss.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style/viewcodecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">ย้อนกลับ</a>

    <?php if(!$snippet): ?>
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 16px;">ไม่พบ Snippet</div>
    <?php else: ?>
        <div class="form-container">
            <!-- Top Actions Row -->
            <div class="top-actions-row">
                <div class="author-credit" title="เจ้าของ Snippet">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="author-icon">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="author-label">โดย</span>
                    <span class="author-username">@<?php echo htmlspecialchars($snippet['username'] ?? 'Unknown'); ?></span>
                </div>
                <div class="right-actions">
                    <!-- Copy Button -->
                    <button class="btn copy" id="copyBtn">คัดลอก</button>
                </div>
            </div>

            <!-- Top Row: Title & Language -->
            <div class="top-row">
                <div class="form-group">
                    <label for="title">ชื่อเรื่อง</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($snippet['title']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="language">ภาษา</label>
                    <select name="language_id" class="language-dropdown" disabled>
                        <option selected><?php echo htmlspecialchars($snippet['language_name'] ?? 'ภาษา'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Code Editor Container (Readonly) -->
            <div class="form-group">
                <label for="source_code">โค้ด</label>
                <div class="editor-container">
                    <div class="line-numbers" id="line-numbers">1</div>
                    <textarea name="source_code" id="source_code" readonly wrap="off"><?php echo htmlspecialchars($snippet['source_code']); ?></textarea>
                </div>
            </div>

            <!-- Description (Readonly) -->
            <div class="form-group">
                <label for="description">คำอธิบาย</label>
                <div class="description-view" id="description"><?php echo htmlspecialchars($snippet['description']); ?></div>
            </div>

            <!-- Tags & Visibility Row -->
            <div class="top-row">
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($snippet['tags'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="visibility">การมองเห็น</label>
                    <select name="visibility" class="visibility-dropdown" disabled>
                        <option value="public" <?php echo (($snippet['visibility'] ?? '') === 'public') ? 'selected' : ''; ?>>สาธารณะ (Public)</option>
                        <option value="protected" <?php echo (($snippet['visibility'] ?? '') === 'protected') ? 'selected' : ''; ?>>เฉพาะสมาชิก (Protected)</option>
                        <option value="private" <?php echo (($snippet['visibility'] ?? '') === 'private') ? 'selected' : ''; ?>>ส่วนตัว (Private)</option>
                    </select>
                </div>
            </div>

            <!-- Action Row -->
            <div class="action-row" style="margin-top: 0;">
                <!-- Left: Show Image directly -->
                <div class="left-actions">
                    <?php if(!empty($snippet['image_path']) && file_exists('../' . $snippet['image_path'])): ?>
                        <img id="image-preview" src="<?php echo '../' . htmlspecialchars($snippet['image_path']); ?>" alt="Snippet Image" style="display: block;">
                    <?php else: ?>
                        <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">ไม่มีรูปภาพ</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Image Viewer Modal (Lightbox) -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-card">
            <button class="modal-close" id="closeImageModalBtn">&times;</button>
            <img id="modal-image-display" src="" alt="Snippet Image Large">
        </div>
    </div>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <script>
        // ==============================================================================================
        // ส่วน JavaScript: ฟังก์ชันควบคุมการแสดงผลและเครื่องมือดูโค้ด (Admin Code Viewer)
        // ==============================================================================================

        /**
         * ฟังก์ชัน: showToast
         * ส่วนที่อยู่: Client-side JS - การแจ้งเตือน
         * หน้าที่: แสดงข้อความแจ้งเตือน Toast สั้นๆ ที่ด้านล่างของหน้าจอ และจางหายไปอัตโนมัติ
         * พารามิเตอร์: message (string) - ข้อความที่ต้องการแสดง
         */
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');

        /**
         * ฟังก์ชัน: autoResizeTextarea
         * ส่วนที่อยู่: Client-side JS - ตัวแสดงโค้ด
         * หน้าที่: ปรับความสูงของ Textarea และ Editor Container ให้พอดีกับปริมาณบรรทัดของโค้ดโดยอัตโนมัติ
         */
        function autoResizeTextarea() {
            if (!textarea || !lineNumbers) return;
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            textarea.style.height = scrollHeight + 'px';

            const finalHeight = Math.max(280, scrollHeight);
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        /**
         * ฟังก์ชัน: updateLineNumbers
         * ส่วนที่อยู่: Client-side JS - ตัวแสดงเลขบรรทัด
         * หน้าที่: คำนวณจำนวนบรรทัดของโค้ด และสร้างหมายเลขบรรทัด (1, 2, 3, ...) แสดงในแถบด้านซ้าย
         */
        function updateLineNumbers() {
            if (textarea && lineNumbers) {
                const lines = textarea.value.split('\n').length;
                lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
            }
        }

        /**
         * ฟังก์ชัน: updateEditor
         * ส่วนที่อยู่: Client-side JS - ตัวควบคุม Editor
         * หน้าที่: เรียกใช้งานอัปเดตเลขบรรทัดและปรับความสูงของกล่องข้อความพร้อมกัน
         */
        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
        }

        if (textarea) {
            // ซิงค์การเลื่อนหน้าจอ (Scroll) ระหว่างโค้ดและเลขบรรทัด
            textarea.addEventListener('scroll', () => {
                if (lineNumbers) lineNumbers.scrollTop = textarea.scrollTop;
            });
            updateEditor();
        }

        // จัดการคลิกปุ่มคัดลอกโค้ดลง Clipboard
        const copyBtn = document.getElementById('copyBtn');
        copyBtn?.addEventListener('click', async ()=>{
            const text = textarea.value;
            try{
                await navigator.clipboard.writeText(text);
                const old = copyBtn.innerText;
                copyBtn.innerText = 'คัดลอกแล้ว';
                setTimeout(()=> copyBtn.innerText = old, 1500);
            }catch(e){
                alert('ไม่สามารถคัดลอกได้');
            }
        });

        // ==============================================================================================
        // ส่วน Lightbox Modal สำหรับแสดงรูปภาพขนาดเต็ม
        // ==============================================================================================
        const imagePreviewEl = document.getElementById('image-preview');
        const imageModal = document.getElementById('imageModal');
        const closeImageModalBtn = document.getElementById('closeImageModalBtn');
        const modalImageDisplay = document.getElementById('modal-image-display');

        // เมื่อคลิกที่รูปภาพตัวอย่าง ให้เปิด Modal แสดงรูปภาพขนาดใหญ่
        imagePreviewEl?.addEventListener('click', function() {
            if (modalImageDisplay) {
                modalImageDisplay.src = this.src;
            }
            imageModal?.classList.add('open');
        });

        /**
         * ฟังก์ชัน: closeImageModal
         * ส่วนที่อยู่: Client-side JS - Lightbox Modal รูปภาพ
         * หน้าที่: ปิดหน้าต่าง Modal แสดงรูปภาพขนาดใหญ่
         */
        function closeImageModal() {
            imageModal?.classList.remove('open');
        }

        closeImageModalBtn?.addEventListener('click', closeImageModal);

        // ปิด Modal เมื่อคลิกที่พื้นหลังสีทึบภายนอกรูปภาพ
        imageModal?.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
