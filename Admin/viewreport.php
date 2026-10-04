<?php
/**
 * ==============================================================================================
 * หน้ารายละเอียดการรายงานสำหรับผู้ดูแลระบบ (Admin View Report)
 * ==============================================================================================
 * วัตถุประสงค์:
 *   - ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin)
 *   - ตรวจสอบรายละเอียดการรายงาน (ผู้รายงาน เหตุผล วันที่ คำอธิบาย)
 *   - ตรวจสอบเนื้อหาของ Snippet ที่ถูกรายงาน (ชื่อเรื่อง ภาษา โค้ด คำอธิบาย Tags รูปภาพ)
 *   - ตรวจสอบประวัติการระงับ (Suspension History) ย้อนหลังของ Snippet นั้นๆ
 *   - ดำเนินการตัดสิน: ปฏิเสธรายงาน (Reject Report), ระงับ Snippet (Suspend), หรือยกเลิกการระงับ (Unsuspend)
 *
 * โครงสร้างไฟล์:
 *   1. ส่วนการตรวจสอบสิทธิ์: เรียกใช้ check_admin.php
 *   2. ส่วนจัดการ POST Actions:
 *      - `reject`: ปฏิเสธรายงานโดยไม่ระงับ Snippet
 *      - `suspend_snippet`: ระงับ Snippet พร้อมบันทึกเหตุผล และอัปเดตรายงานที่เกี่ยวข้อง
 *      - `unsuspend_snippet`: ยกเลิกการระงับ Snippet
 *   3. ส่วนดึงข้อมูลรายงานและประวัติการระงับ: เรียกใช้ getReportByIdAdmin() และ getSnippetSuspensionHistory()
 *   4. ส่วน UI การ์ดข้อมูลรายงาน (Report Info Card)
 *   5. ส่วน UI การ์ดประวัติการระงับ (Suspension History Card)
 *   6. ส่วน UI แสดงเนื้อหาโค้ดของ Snippet (Code Details Card)
 *   7. ส่วน JavaScript: จัดการปรับขนาดโค้ด, คัดลอก, Lightbox รูปภาพ, และ prompt ระบุเหตุผลการระงับ
 * ==============================================================================================
 */

// ส่วนที่ 1: ตรวจสอบสิทธิ์ Admin
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";
$report = null;

// ส่วนที่ 2: จัดการ Actions (POST: reject, suspend_snippet, unsuspend_snippet)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        $adminUsername = $_SESSION['username'] ?? 'Admin';
        
        // จัดการกรณี: ปฏิเสธรายงาน (Reject Report)
        if ($_POST['action'] === 'reject') {
            if ($reportId <= 0) {
                $errorMsg = "รหัสรายงานไม่ถูกต้อง";
            } else {
                if ($control->rejectReportAdmin($reportId, $adminUsername)) {
                    $successMsg = "ปฏิเสธรายงานเรียบร้อยแล้ว";
                } else {
                    $errorMsg = "ไม่สามารถปฏิเสธรายงานได้";
                }
            }
        // จัดการกรณี: ระงับ Snippet (Suspend Snippet)
        } elseif ($_POST['action'] === 'suspend_snippet') {
            if ($snippetId <= 0) {
                $errorMsg = "รหัส Snippet ไม่ถูกต้อง";
            } else {
                $reason = trim($_POST['reason'] ?? 'ได้รับการรายงานว่าละเมิดนโยบาย');
                if ($reason === '') {
                    $reason = 'ได้รับการรายงานว่าละเมิดนโยบาย';
                }
                if ($control->suspendSnippetAdmin($snippetId, $adminUsername, $reason)) {
                    $successMsg = "ระงับ Snippet เรียบร้อยแล้วและปิดรายงานที่เกี่ยวข้อง";
                } else {
                    $errorMsg = "ไม่สามารถระงับ Snippet ได้";
                }
            }
        // จัดการกรณี: ยกเลิกการระงับ Snippet (Unsuspend Snippet)
        } elseif ($_POST['action'] === 'unsuspend_snippet') {
            if ($snippetId <= 0) {
                $errorMsg = "รหัส Snippet ไม่ถูกต้อง";
            } else {
                if ($control->unsuspendSnippetAdmin($snippetId, $adminUsername)) {
                    $successMsg = "ยกเลิกการระงับ Snippet สำเร็จ";
                } else {
                    $errorMsg = "ไม่สามารถยกเลิกการระงับ Snippet ได้";
                }
            }
        }
    }
}

$backUrl = 'manage_reports.php';

// ส่วนที่ 3: ดึงข้อมูลรายงานและประวัติการระงับของ Snippet
$suspensionHistory = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $report = $control->getReportByIdAdmin($id);
    if ($report && !empty($report['snippet_id'])) {
        $suspensionHistory = $control->getSnippetSuspensionHistory((int)$report['snippet_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>รายละเอียดรายงาน - Admin</title>
    <link rel="stylesheet" href="../style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style/createsnippetcss.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../style/viewcodecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">ย้อนกลับ</a>

    <?php if(!$report): ?>
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 16px;">ไม่พบรายงาน</div>
    <?php else: ?>
        <div class="form-container" style="max-width: 900px; margin: 0 auto; gap: 20px;">
            <!-- Status Alerts -->
            <?php if ($successMsg !== ''): ?>
                <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 16px; border-radius: 8px; font-size: 14px; text-align: center; width: 100%;">
                    <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg !== ''): ?>
                <div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 16px; border-radius: 8px; font-size: 14px; text-align: center; width: 100%;">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Report Information Card -->
            <div class="report-info-card" style="background-color: #fff; border: 1px solid var(--border-color); border-left: 4px solid #dc3545; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; width: 100%; box-sizing: border-box;">
                <h3 style="margin-top: 0; margin-bottom: 12px; color: #dc3545; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; font-family: 'Noto Sans Thai', 'Lato', sans-serif;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    รายละเอียดรายงาน #<?php echo htmlspecialchars($report['report_id']); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.95rem; font-family: 'Noto Sans Thai', 'Lato', sans-serif;">
                    <div>
                        <strong>เหตุผลที่รายงาน:</strong> 
                        <span style="color: #dc3545; font-weight: bold; text-transform: capitalize;"><?php echo htmlspecialchars($report['reason']); ?></span>
                    </div>
                    <div>
                        <strong>ผู้รายงาน:</strong> 
                        <span><?php echo htmlspecialchars($report['reporter_firstname'] . ' ' . $report['reporter_lastname']); ?> (@<?php echo htmlspecialchars($report['reporter_username']); ?>)</span>
                    </div>
                    <div>
                        <strong>เจ้าของ Snippet:</strong> 
                        <span><?php echo htmlspecialchars($report['owner_firstname'] . ' ' . $report['owner_lastname']); ?> (@<?php echo htmlspecialchars($report['owner_username']); ?>)</span>
                    </div>
                    <div>
                        <strong>วันที่รายงาน:</strong> 
                        <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($report['created_at']))); ?></span>
                    </div>
                    <?php if (!empty($report['resolved_by'])): ?>
                    <div>
                        <strong>จัดการโดย:</strong> 
                        <span style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($report['resolved_by']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($report['resolved_at'])): ?>
                    <div>
                        <strong>วันที่จัดการ:</strong> 
                        <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($report['resolved_at']))); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-color); font-family: 'Noto Sans Thai', 'Lato', sans-serif;">
                    <strong>คำอธิบายรายงาน:</strong>
                    <div style="margin-top: 6px; padding: 12px; background-color: var(--neutral-bg); border-radius: var(--radius-sm); border: 1px solid var(--border-color); white-space: pre-wrap; font-size: 0.9rem; line-height: 1.5; color: #333; word-break: break-word;"><?php echo htmlspecialchars(trim($report['details'] ?: 'ไม่มีคำอธิบายเพิ่มเติม')); ?></div>
                </div>
                
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <span style="font-weight: 600; font-family: 'Noto Sans Thai', 'Lato', sans-serif; font-size: 0.95rem;">สถานะ Snippet: </span>
                        <?php if ((int)($report['snippet_suspended'] ?? 0) === 1): ?>
                            <span style="font-weight: bold; color: #dc2626; padding: 3px 8px; background: #fee2e2; border-radius: 4px; font-size: 0.85rem;">ถูกระงับ</span>
                        <?php elseif ((int)($report['snippet_deleted'] ?? 0) === 1): ?>
                            <span style="font-weight: bold; color: #6b7280; padding: 3px 8px; background: #f3f4f6; border-radius: 4px; font-size: 0.85rem;">ถูกลบ</span>
                        <?php else: ?>
                            <span style="font-weight: bold; color: #15803d; padding: 3px 8px; background: #dcfce7; border-radius: 4px; font-size: 0.85rem;">ปกติ</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <?php if ($report['status'] === 'pending'): ?>
                            <!-- Reject / Dismiss Report Form -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                <button type="submit" class="btn" style="border-color: var(--border-color); color: white; background: #15803D;" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธรายงานนี้?');">ปฏิเสธรายงาน</button>
                            </form>
                        <?php endif; ?>

                        <?php if ((int)($report['snippet_suspended'] ?? 0) === 1): ?>
                            <!-- Unsuspend Snippet Form -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="unsuspend_snippet">
                                <input type="hidden" name="snippet_id" value="<?php echo $report['snippet_id']; ?>">
                                <button type="submit" class="btn" style="background-color: #2563eb; color: white;" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการระงับ Snippet นี้?');">ยกเลิกการระงับ Snippet</button>
                            </form>
                        <?php else: ?>
                            <!-- Suspend Snippet Form -->
                            <form method="POST" style="margin: 0;" onsubmit="return promptSuspend(this, '<?php echo htmlspecialchars(addslashes($report['reason'])); ?>');">
                                <input type="hidden" name="action" value="suspend_snippet">
                                <input type="hidden" name="snippet_id" value="<?php echo $report['snippet_id']; ?>">
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn copy" style="background-color: #d97706; border-color: #d97706; color: white;">ระงับ Snippet</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Suspension History Card -->
            <div class="report-info-card" style="background-color: #fff; border: 1px solid var(--border-color); border-left: 4px solid #2563eb; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; width: 100%; box-sizing: border-box; font-family: 'Noto Sans Thai', 'Lato', sans-serif;">
                <h3 style="margin-top: 0; margin-bottom: 12px; color: #2563eb; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    ประวัติการระงับ Snippet
                </h3>
                <?php if (empty($suspensionHistory)): ?>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; font-style: italic;">ไม่มีประวัติการระงับสำหรับ Snippet นี้</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 8px 12px;">#</th>
                                    <th style="padding: 8px 12px;">ผู้สั่งระงับ</th>
                                    <th style="padding: 8px 12px;">เหตุผล</th>
                                    <th style="padding: 8px 12px;">วันที่ระงับ</th>
                                    <th style="padding: 8px 12px;">ผู้ยกเลิกการระงับ</th>
                                    <th style="padding: 8px 12px;">วันที่ยกเลิกการระงับ</th>
                                    <th style="padding: 8px 12px;">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suspensionHistory as $idx => $sh): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['suspension_id']); ?></td>
                                        <td style="padding: 10px 12px; font-weight: 600;"><?php echo htmlspecialchars($sh['admin_suspended']); ?></td>
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['reason']); ?></td>
                                        <td style="padding: 10px 12px; color: var(--text-muted);"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sh['suspended_at']))); ?></td>
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['admin_unsuspended'] ?: '-'); ?></td>
                                        <td style="padding: 10px 12px; color: var(--text-muted);"><?php echo $sh['unsuspended_at'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($sh['unsuspended_at']))) : '-'; ?></td>
                                        <td style="padding: 10px 12px;">
                                            <?php if (empty($sh['unsuspended_at'])): ?>
                                                <span style="color: #dc2626; font-weight: 600;">กำลังถูกระงับ</span>
                                            <?php else: ?>
                                                <span style="color: #15803d; font-weight: 600;">ยกเลิกการระงับแล้ว</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Code Details Card (Viewcode Layout) -->
            <div style="width: 100%; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.04); box-sizing: border-box; display: flex; flex-direction: column; gap: 24px;">
                <!-- Top Actions Row -->
                <div class="top-actions-row">
                    <div class="right-actions">
                        <!-- Copy Button -->
                        <button class="btn copy" id="copyBtn">คัดลอก</button>
                    </div>
                </div>

                <!-- Top Row: Title & Language -->
                <div class="top-row">
                    <div class="form-group">
                        <label for="title">ชื่อเรื่อง</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($report['snippet_title']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="language">ภาษา</label>
                        <select name="language_id" class="language-dropdown" disabled>
                            <option selected><?php echo htmlspecialchars($report['language_name'] ?? 'ภาษา'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Code Editor Container (Readonly) -->
                <div class="form-group">
                    <label for="source_code">โค้ด</label>
                    <div class="editor-container">
                        <div class="line-numbers" id="line-numbers">1</div>
                        <textarea name="source_code" id="source_code" readonly wrap="off"><?php echo htmlspecialchars($report['source_code']); ?></textarea>
                    </div>
                </div>

                <!-- Description (Readonly) -->
                <div class="form-group">
                    <label for="description">คำอธิบาย</label>
                    <div class="description-view" id="description"><?php echo htmlspecialchars($report['snippet_desc']); ?></div>
                </div>

                <!-- Tags & Visibility Row -->
                <div class="top-row">
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($report['tags'] ?? ''); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="visibility">การมองเห็น</label>
                        <select name="visibility" class="visibility-dropdown" disabled>
                            <option value="public" <?php echo (($report['visibility'] ?? '') === 'public') ? 'selected' : ''; ?>>สาธารณะ (Public)</option>
                            <option value="protected" <?php echo (($report['visibility'] ?? '') === 'protected') ? 'selected' : ''; ?>>เฉพาะสมาชิก (Protected)</option>
                            <option value="private" <?php echo (($report['visibility'] ?? '') === 'private') ? 'selected' : ''; ?>>ส่วนตัว (Private)</option>
                        </select>
                    </div>
                </div>

                <!-- Action Row -->
                <div class="action-row" style="margin-top: 0;">
                    <!-- Left: Show Image directly -->
                    <div class="left-actions">
                        <?php if(!empty($report['image_path']) && file_exists('../' . $report['image_path'])): ?>
                            <img id="image-preview" src="<?php echo '../' . htmlspecialchars($report['image_path']); ?>" alt="Snippet Image" style="display: block;">
                        <?php else: ?>
                            <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">ไม่มีรูปภาพ</span>
                        <?php endif; ?>
                    </div>
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
        // ส่วน JavaScript: ฟังก์ชันควบคุมการแสดงผลและเครื่องมือจัดการรายงาน (Admin View Report)
        // ==============================================================================================

        /**
         * ฟังก์ชัน: showToast
         * ส่วนที่อยู่: Client-side JS - การแจ้งเตือน
         * หน้าที่: แสดงข้อความแจ้งเตือน Toast สั้นๆ ที่ด้านล่างของหน้าจอ และจางหายไปอัตโนมัติ
         * พารามิเตอร์: message (string) - ข้อความที่ต้องการแสดง
         */
        function showToast(message) {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.textContent = message;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2000);
            }
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

        /**
         * ฟังก์ชัน: promptSuspend
         * ส่วนที่อยู่: Client-side JS - แบบฟอร์มระงับ Snippet
         * หน้าที่: แสดงกล่องข้อความ (Prompt) ให้ Admin ระบุเหตุผลในการระงับ Snippet ก่อนส่งฟอร์ม POST
         * พารามิเตอร์: 
         *   - form (HTMLFormElement): ฟอร์มที่กำลังส่ง
         *   - defaultReason (string): เหตุผลเริ่มต้นที่ดึงมาจากข้อหาการรายงาน
         * ส่งกลับ: boolean (true = ดำเนินการส่งฟอร์ม, false = กดยกเลิก)
         */
        function promptSuspend(form, defaultReason) {
            const reason = prompt('กรุณาระบุเหตุผลในการระงับ Snippet นี้:', defaultReason || 'ละเมิดนโยบาย');
            if (reason === null) {
                return false;
            }
            form.querySelector('input[name="reason"]').value = reason.trim() || defaultReason || 'ละเมิดนโยบาย';
            return true;
        }
    </script>
</body>
</html>
