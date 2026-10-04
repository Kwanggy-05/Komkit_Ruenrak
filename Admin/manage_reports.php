<?php
/**
 * ==============================================================================
 * ไฟล์: Admin/manage_reports.php
 * หน้าที่: หน้าจัดการรายงานเนื้อหาที่ไม่เหมาะสมสำหรับผู้ดูแลระบบ (Admin Reports Management)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดระบบตรวจสอบสิทธิ์ผู้ดูแลระบบ (check_admin.php)
 *   2. ประมวลผลคำสั่ง POST จากแอดมิน:
 *      - action='reject'            : ปฏิเสธรายงาน (ยกฟ้อง) ($control->rejectReportAdmin())
 *      - action='suspend_snippet'   : สั่งระงับ Snippet พร้อมระบุเหตุผล ($control->suspendSnippetAdmin())
 *      - action='unsuspend_snippet' : ยกเลิกการระงับ Snippet ($control->unsuspendSnippetAdmin())
 *   3. ตัวกรองสถานะรายงาน (pending, removed, rejected, ALL) และดึงข้อมูล ($control->getAllReportsAdmin())
 *   4. ส่วนแสดงผลตารางรายการรายงาน และปุ่มตรวจสอบ/ดำเนินการ
 *   5. สคริปต์ JavaScript: promptSuspend() ถามเหตุผลก่อนสั่งระงับ
 * ==============================================================================
 */

// --- ส่วนที่ 1: ตรวจสอบสิทธิ์ผู้ดูแลระบบ ---
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// --- ส่วนที่ 2: ประมวลผลคำสั่ง POST จัดการรายงาน ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        $adminUsername = $_SESSION['username'] ?? 'Admin';
        
        // กรณีแอดมินปฏิเสธรายงาน (รายงานไม่เป็นความจริง หรือไม่ผิดกฎ)
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
        // กรณีแอดมินสั่งระงับการแสดงผลของ Snippet ตามที่ได้รับรายงาน
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
        // กรณีแอดมินยกเลิกการระงับ Snippet ให้กลับมาแสดงผลปกติ
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

// --- ส่วนที่ 3: รับค่าตัวกรองสถานะรายงาน และดึงรายการรายงานจากฐานข้อมูล ---
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
if (!in_array($statusFilter, ['pending', 'removed', 'rejected', 'ALL'])) {
    $statusFilter = 'pending';
}

$reports = [];
$reportsResult = $control->getAllReportsAdmin($statusFilter === 'ALL' ? null : $statusFilter);
if ($reportsResult) {
    $reports = $reportsResult->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายงาน - Admin Panel</title>
    <link rel="stylesheet" href="../style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../logo/Logo_snippetBox2.png" alt="Snippet Box Logo">
                <h1>Snippet Box</h1>
            </div>
            <nav class="sidebar-menu">
                <a href="manage_snippets.php" class="menu-item">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>จัดการ Snippet</span>
                </a>
                <a href="manage_languages.php" class="menu-item">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>จัดการภาษา</span>
                </a>
                <a href="manage_users.php" class="menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>จัดการผู้ใช้</span>
                </a>
                <a href="manage_reports.php" class="menu-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>จัดการรายงาน</span>
                    <?php if (!empty($pendingReportsCount) && $pendingReportsCount > 0): ?>
                        <span class="sidebar-dot" title="รายงานที่รอดำเนินการ"></span>
                    <?php endif; ?>
                </a>
                <a href="manage_appeals.php" class="menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <span>จัดการคำร้อง</span>
                    <?php if (!empty($pendingAppealsCount) && $pendingAppealsCount > 0): ?>
                        <span class="sidebar-dot" title="คำร้องที่รอดำเนินการ"></span>
                    <?php endif; ?>
                </a>
            </nav>
        </aside>

        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-bar">
                <div class="top-left">
                    <button class="menu-toggle" id="menuToggle" aria-label="เปิด/ปิดเมนู">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <span class="top-bar-title">Admin Panel</span>
                </div>
                
                <div class="top-right">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                            <span class="user-role">ผู้ดูแลระบบ</span>
                        </div>
                    </div>
                    <a href="login.php" class="btn-logout" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?');">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        ออกจากระบบ
                    </a>
                </div>
            </header>

            <!-- Page Body -->
            <div class="content-body">
                <!-- Status Alerts -->
                <?php if ($successMsg !== ''): ?>
                    <div class="alert alert-success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><?php echo htmlspecialchars($successMsg); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($errorMsg !== ''): ?>
                    <div class="alert alert-danger">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span><?php echo htmlspecialchars($errorMsg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Content Header Controls -->
                <div class="content-header">
                    <h2 class="page-title">จัดการรายงาน</h2>
                    <div class="header-actions">
                        <!-- Filters (lowercase parameters) -->
                        <div style="display: flex; gap: 8px;">
                            <a href="?status=pending" class="btn-action <?php echo $statusFilter === 'pending' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                รอดำเนินการ<?php if (!empty($pendingReportsCount) && $pendingReportsCount > 0): ?><span class="sidebar-dot" style="margin-left: 2px;" title="รายงานใหม่"></span><?php endif; ?>
                            </a>
                            <a href="?status=removed" class="btn-action <?php echo $statusFilter === 'removed' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">จัดการแล้ว</a>
                            <a href="?status=rejected" class="btn-action <?php echo $statusFilter === 'rejected' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">ปฏิเสธแล้ว</a>
                            <a href="?status=ALL" class="btn-action <?php echo $statusFilter === 'ALL' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">ทั้งหมด</a>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Snippet & ID</th>
                                <th>เหตุผล</th>
                                <th>ผู้รายงาน</th>
                                <th>วันที่รายงาน</th>
                                <th style="width: 110px; text-align: center;">สถานะ</th>
                                <th style="width: 250px; text-align: center;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        ไม่พบรายงาน
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $rep): ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($rep['report_id']); ?></td>
                                        <td style="font-weight: 600;">
                                            <?php echo htmlspecialchars($rep['snippet_title']); ?> (ID: <?php echo htmlspecialchars($rep['snippet_id']); ?>)
                                            <?php if ((int)($rep['snippet_suspended'] ?? 0) === 1): ?>
                                                <span class="badge badge-suspended" style="margin-left: 5px;">ถูกระงับ</span>
                                            <?php elseif ((int)$rep['snippet_deleted'] === 1): ?>
                                                <span class="badge badge-deleted" style="margin-left: 5px;">ถูกลบ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color: var(--danger); font-weight: 600; text-transform: capitalize;">
                                                <?php echo htmlspecialchars($rep['reason']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.9rem; color: var(--text-main); font-weight: 500;">
                                                <?php echo htmlspecialchars($rep['reporter_firstname'] . ' ' . $rep['reporter_lastname']); ?>
                                            </span>
                                            <br>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                (@<?php echo htmlspecialchars($rep['reporter_username']); ?>)
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rep['created_at']))); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($rep['status'] === 'pending'): ?>
                                                <span class="badge badge-active" style="background-color: #ffeeba; color: #856404; border-color: #ffeeba;">รอดำเนินการ</span>
                                            <?php elseif ($rep['status'] === 'removed'): ?>
                                                <span class="badge badge-active" style="background-color: #d4edda; color: #155724; border-color: #d4edda;">จัดการแล้ว</span>
                                                <?php if (!empty($rep['resolved_by'])): ?>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px;">โดย <?php echo htmlspecialchars($rep['resolved_by']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-deleted">ปฏิเสธแล้ว</span>
                                                <?php if (!empty($rep['resolved_by'])): ?>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px;">โดย <?php echo htmlspecialchars($rep['resolved_by']); ?></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <!-- View Details Trigger -->
                                                <a href="viewreport.php?id=<?php echo $rep['report_id']; ?>" class="btn-action btn-view">
                                                    รายละเอียด
                                                </a>
                                                
                                                <?php if ($rep['status'] === 'pending'): ?>
                                                    <!-- Reject / Dismiss Report Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปฏิเสธรายงานนี้?');">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="report_id" value="<?php echo $rep['report_id']; ?>">
                                                        <button type="submit" class="btn-action btn-restore" style="background-color: #15803D; color: white;">ปฏิเสธ</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ((int)($rep['snippet_suspended'] ?? 0) === 1): ?>
                                                    <!-- Unsuspend Snippet Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการระงับ Snippet นี้?');">
                                                        <input type="hidden" name="action" value="unsuspend_snippet">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $rep['snippet_id']; ?>">
                                                        <button type="submit" class="btn-action btn-restore" style="background-color: #2563eb; color: white;">ยกเลิกการระงับ</button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Suspend Snippet Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return promptSuspend(this, '<?php echo htmlspecialchars(addslashes($rep['reason'])); ?>');">
                                                        <input type="hidden" name="action" value="suspend_snippet">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $rep['snippet_id']; ?>">
                                                        <input type="hidden" name="reason" value="">
                                                        <button type="submit" class="btn-action btn-delete" style="background-color: #d97706; color: white;">ระงับ</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle functionality
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                if (window.innerWidth > 768) {
                    sidebar.classList.toggle('collapsed');
                } else {
                    sidebar.classList.toggle('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('active');
                    }
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        /**
         * ฟังก์ชัน: promptSuspend
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: กล่องข้อความกรอกเหตุผลการระงับ]
         * หน้าที่: แสดงกล่องข้อความ Prompt ให้แอดมินพิมพ์เหตุผลในการระงับ Snippet ก่อนส่งฟอร์ม
         * พารามิเตอร์: form (องค์ประกอบฟอร์มที่ส่ง), defaultReason (เหตุผลเริ่มต้น)
         * คืนค่า: bool (true เมื่อยืนยันการส่ง, false เมื่อกดยกเลิก)
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
