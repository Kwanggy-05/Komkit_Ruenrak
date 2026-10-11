<?php
/**
 * ==============================================================================
 * ไฟล์: Admin/manage_snippets.php
 * หน้าที่: หน้าจัดการ Snippet ทั้งหมดในระบบสำหรับผู้ดูแลระบบ (Admin Snippet Management)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดระบบตรวจสอบสิทธิ์ผู้ดูแลระบบ (check_admin.php)
 *   2. ประมวลผลคำสั่ง POST จากแอดมิน:
 *      - action='suspend'     : สั่งระงับ Snippet พร้อมระบุเหตุผล ($control->suspendSnippetAdmin())
 *      - action='unsuspend'   : ยกเลิกการระงับ Snippet ($control->unsuspendSnippetAdmin())
 *   3. ดึงรายการ Snippet ทั้งหมดในระบบ รวมทั้งที่ถูกลบและถูกระงับ ($control->getAllSnippetsAdmin())
 *   4. ส่วนแสดงผลตารางรายการโค้ด สถานะ ป้ายกำกับ และปุ่มดำเนินการ
 *   5. สคริปต์ JavaScript: promptSuspend() รับเหตุผลการระงับ
 * ==============================================================================
 */

// --- ส่วนที่ 1: ตรวจสอบสิทธิ์ผู้ดูแลระบบ ---
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// --- ส่วนที่ 2: ประมวลผลคำสั่ง POST จัดการสถานะของ Snippet ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        if ($snippetId <= 0) {
            $errorMsg = "ข้อมูลไม่ถูกต้อง";
        } else {
            // สั่งระงับการแสดงผล Snippet (Suspend)
            if ($_POST['action'] === 'suspend') {
                $reason = trim($_POST['reason'] ?? 'ทำผิดข้อกำหนดชุมชน');
                if ($reason === '') {
                    $reason = 'ทำผิดข้อกำหนดชุมชน';
                }
                $adminUsername = $_SESSION['username'] ?? 'Admin';
                if ($control->suspendSnippetAdmin($snippetId, $adminUsername, $reason)) {
                    $successMsg = "ระงับ Snippet สำเร็จ";
                } else {
                    $errorMsg = "ไม่สามารถระงับ Snippet ได้";
                }
            // ยกเลิกการระงับ Snippet (Unsuspend)
            } elseif ($_POST['action'] === 'unsuspend') {
                $adminUsername = $_SESSION['username'] ?? 'Admin';
                if ($control->unsuspendSnippetAdmin($snippetId, $adminUsername)) {
                    $successMsg = "ยกเลิกการระงับ Snippet สำเร็จ";
                } else {
                    $errorMsg = "ไม่สามารถยกเลิกการระงับ Snippet ได้";
                }
            }
        }
    }
}

// --- ส่วนที่ 3: ดึงข้อมูล Snippet ทั้งหมดในระบบสำหรับผู้ดูแลระบบ ---
$searchQuery = trim($_GET['q'] ?? '');
$snippets = [];
$snippetResult = $control->getAllSnippetsAdmin($searchQuery);
if ($snippetResult) {
    $snippets = $snippetResult->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Snippet - Admin Panel</title>
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
                <a href="manage_snippets.php" class="menu-item active">
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
                <a href="manage_reports.php" class="menu-item">
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
                    <span class="top-bar-title">แผงควบคุมผู้ดูแลระบบ</span>
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
                    <h2 class="page-title">จัดการ Snippet</h2>
                    <div class="header-actions">
                        <!-- Search Bar -->
                        <form method="GET" class="search-form">
                            <div class="search-wrapper">
                                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" name="q" class="search-input" placeholder="ค้นหาชื่อเรื่อง, เจ้าของ..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-id" style="width: 80px;">ID</th>
                                <th>ชื่อเรื่อง</th>
                                <th>ภาษา</th>
                                <th>เจ้าของ</th>
                                <th>วันที่สร้าง</th>
                                <th style="width: 110px; text-align: center;">สถานะ</th>
                                <th style="width: 220px; text-align: center;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($snippets)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        ไม่พบข้อมูล Snippet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($snippets as $snip): ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($snip['snippet_id']); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($snip['title']); ?></td>
                                        <td>
                                            <span style="font-family: 'Noto Sans Thai', 'Lato', sans-serif; font-size: 0.85rem; padding: 2px 6px; background-color: var(--neutral); border-radius: var(--rounded-sm); border: 1px solid var(--border-color);">
                                                <?php echo htmlspecialchars($snip['language_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.9rem; color: var(--text-main); font-weight: 500;">
                                                <?php echo htmlspecialchars($snip['firstname'] . ' ' . $snip['lastname']); ?>
                                            </span>
                                            <br>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                (@<?php echo htmlspecialchars($snip['username']); ?>)
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($snip['created_at']))); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ((int)($snip['is_suspended'] ?? 0) === 1): ?>
                                                <span class="badge badge-suspended">ถูกระงับ</span>
                                                <?php 
                                                    $snipAppeal = $control->getPendingAppealBySnippet($snip['snippet_id']);
                                                    if ($snipAppeal): 
                                                ?>
                                                    <br>
                                                    <a href="manage_appeals.php?status=pending" style="display: inline-block; margin-top: 4px; font-size: 0.72rem; padding: 2px 6px; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 4px; text-decoration: none; font-weight: 700;" title="คลิกเพื่อพิจารณาคำร้อง">
                                                        รอพิจารณาคำร้อง ↗
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ((int)($snip['deleted'] ?? 0) === 1): ?>
                                                <span class="badge badge-deleted">ถังขยะ</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">ปกติ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <!-- View Code Trigger -->
                                                <a href="viewcode.php?view=<?php echo $snip['snippet_id']; ?>" class="btn-action btn-view">
                                                    ดูโค้ด
                                                </a>

                                                <?php if ((int)($snip['is_suspended'] ?? 0) === 1): ?>
                                                    <!-- Unsuspend Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการระงับ Snippet นี้?');">
                                                        <input type="hidden" name="action" value="unsuspend">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $snip['snippet_id']; ?>">
                                                        <button type="submit" class="btn-action btn-restore" style="background-color: #2563eb; color: white;">ยกเลิกการระงับ</button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Suspend Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return promptSuspend(this);">
                                                        <input type="hidden" name="action" value="suspend">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $snip['snippet_id']; ?>">
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
         * หน้าที่: แสดงกล่องข้อความ Prompt ให้แอดมินกรอกเหตุผลในการระงับ Snippet ก่อนส่งฟอร์ม
         * พารามิเตอร์: form (องค์ประกอบของฟอร์ม)
         * คืนค่า: bool (true เมื่อยืนยัน, false เมื่องดส่ง)
         */
        function promptSuspend(form) {
            const reason = prompt('กรุณาระบุเหตุผลในการระงับ Snippet นี้:', 'ละเมิดหลักเกณฑ์ของชุมชน');
            if (reason === null) {
                return false;
            }
            form.querySelector('input[name="reason"]').value = reason.trim() || 'ละเมิดหลักเกณฑ์ของชุมชน';
            return true;
        }
    </script>
</body>
</html>
