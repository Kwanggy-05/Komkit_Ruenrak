<?php
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// Handle POST actions (Add / Edit / Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $langName = trim($_POST['language_name'] ?? '');
            if ($langName === '') {
                $errorMsg = "กรุณากรอกชื่อภาษา";
            } else {
                if ($control->addLanguage($langName)) {
                    $successMsg = "เพิ่มภาษา \"$langName\" สำเร็จ";
                } else {
                    $errorMsg = "ไม่สามารถเพิ่มภาษาได้ (อาจมีภาษานี้อยู่แล้ว)";
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $langId = (int)($_POST['language_id'] ?? 0);
            $langName = trim($_POST['language_name'] ?? '');
            if ($langId <= 0 || $langName === '') {
                $errorMsg = "ข้อมูลไม่ถูกต้อง";
            } else {
                if ($control->updateLanguage($langId, $langName)) {
                    $successMsg = "อัปเดตข้อมูลภาษาสำเร็จ";
                } else {
                    $errorMsg = "ไม่สามารถอัปเดตข้อมูลภาษาได้";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $langId = (int)($_POST['language_id'] ?? 0);
            if ($langId <= 0) {
                $errorMsg = "ข้อมูลไม่ถูกต้อง";
            } else {
                // Check if language is in use
                if ($control->isLanguageInUse($langId)) {
                    $errorMsg = "ไม่สามารถลบภาษานี้ได้ เนื่องจากมี Snippet ใช้งานภาษานี้อยู่";
                } else {
                    if ($control->deleteLanguage($langId)) {
                        $successMsg = "ลบภาษาสำเร็จ";
                    } else {
                        $errorMsg = "ไม่สามารถลบภาษาได้";
                    }
                }
            }
        }
    }
}

// Get languages list
$searchQuery = trim($_GET['q'] ?? '');
$languages = [];
$languageResult = $control->getLanguage();
if ($languageResult) {
    $rawLanguages = $languageResult->fetchAll(PDO::FETCH_ASSOC);
    if ($searchQuery !== '') {
        foreach ($rawLanguages as $l) {
            if (stripos($l['language_name'], $searchQuery) !== false) {
                $languages[] = $l;
            }
        }
    } else {
        $languages = $rawLanguages;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการภาษา - Admin Panel</title>
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
                <a href="manage_languages.php" class="menu-item active">
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
                    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
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
                    <h2 class="page-title">จัดการภาษา</h2>
                    <div class="header-actions">
                        <!-- Search Bar (Pill shape) -->
                        <form method="GET" class="search-form">
                            <div class="search-wrapper">
                                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </a>
                                <input type="text" name="q" class="search-input" placeholder="ค้นหาภาษา..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                        </form>

                        <!-- Add Button (Circular dark + button) -->
                        <button class="btn-circle" id="btnAddLanguage" title="เพิ่มภาษา">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-id" style="width: 100px;">ID</th>
                                <th>ชื่อภาษา</th>
                                <th style="width: 200px; text-align: center;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($languages)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        ไม่พบข้อมูลภาษา
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($languages as $lang): ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($lang['language_id']); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lang['language_name']); ?></td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <button class="btn-action btn-edit" 
                                                        onclick="openEditModal(<?php echo $lang['language_id']; ?>, '<?php echo htmlspecialchars($lang['language_name'], ENT_QUOTES); ?>')">
                                                    แก้ไข
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบภาษานี้?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="language_id" value="<?php echo $lang['language_id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete">ลบ</button>
                                                </form>
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

    <!-- Add Language Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-card">
            <header class="modal-header">
                <span class="modal-title">เพิ่มภาษาใหม่</span>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </header>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="add_language_name">ชื่อภาษา</label>
                        <input type="text" id="add_language_name" name="language_name" class="form-control" placeholder="เช่น Python, Ruby" required autocomplete="off">
                    </div>
                </div>
                <footer class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึก</button>
                </footer>
            </form>
        </div>
    </div>

    <!-- Edit Language Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <header class="modal-header">
                <span class="modal-title">แก้ไขภาษา</span>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </header>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_language_id" name="language_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_language_name">ชื่อภาษา</label>
                        <input type="text" id="edit_language_name" name="language_name" class="form-control" required autocomplete="off">
                    </div>
                </div>
                <footer class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">ยกเลิก</button>
                    <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
                </footer>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        const btnAddLanguage = document.getElementById('btnAddLanguage');
        
        btnAddLanguage.addEventListener('click', () => {
            addModal.classList.add('open');
            document.getElementById('add_language_name').focus();
        });

        function openEditModal(id, name) {
            document.getElementById('edit_language_id').value = id;
            document.getElementById('edit_language_name').value = name;
            editModal.classList.add('open');
            document.getElementById('edit_language_name').focus();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // Close on clicking overlay background
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('open');
            }
        });

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
    </script>
</body>
</html>
