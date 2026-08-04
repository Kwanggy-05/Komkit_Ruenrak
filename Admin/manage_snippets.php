<?php
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// Handle Actions (Soft Delete / Restore / Hard Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        if ($snippetId <= 0) {
            $errorMsg = "ข้อมูลไม่ถูกต้อง";
        } else {
            if ($_POST['action'] === 'soft_delete') {
                if ($control->softDeleteSnippetAdmin($snippetId)) {
                    $successMsg = "ย้าย Snippet ไปยังถังขยะเรียบร้อยแล้ว";
                } else {
                    $errorMsg = "ไม่สามารถย้าย Snippet ไปยังถังขยะได้";
                }
            } elseif ($_POST['action'] === 'restore') {
                if ($control->restoreSnippetAdmin($snippetId)) {
                    $successMsg = "คืนค่า Snippet เรียบร้อยแล้ว";
                } else {
                    $errorMsg = "ไม่สามารถคืนค่า Snippet ได้";
                }
            } elseif ($_POST['action'] === 'hard_delete') {
                if ($control->deleteSnippetAdmin($snippetId)) {
                    $successMsg = "ลบ Snippet ถาวรเรียบร้อยแล้ว";
                } else {
                    $errorMsg = "ไม่สามารถลบ Snippet ถาวรได้";
                }
            }
        }
    }
}

// Get snippets list
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
    <title>Manage Snippets - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../logo/Logo_snippetBox.png" alt="Snippet Box Logo">
                <h1>Snippet Box</h1>
            </div>
            <nav class="sidebar-menu">
                <a href="manage_languages.php" class="menu-item">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>Manage Language</span>
                </a>
                <a href="manage_snippets.php" class="menu-item active">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Manage Snippets</span>
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
                            <span class="user-role">Admin</span>
                        </div>
                    </div>
                    <a href="../login.php" class="btn-logout" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Log out
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
                    <h2 class="page-title">Manage Snippets</h2>
                    <div class="header-actions">
                        <!-- Search Bar -->
                        <form method="GET" class="search-form">
                            <div class="search-wrapper">
                                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" name="q" class="search-input" placeholder="ค้นหาตามชื่อ, เจ้าของ..." value="<?php echo htmlspecialchars($searchQuery); ?>">
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
                                <th>Title</th>
                                <th>Language</th>
                                <th>Owner</th>
                                <th>Created At</th>
                                <th style="width: 110px; text-align: center;">Status</th>
                                <th style="width: 280px; text-align: center;">Action</th>
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
                                            <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; padding: 2px 6px; background-color: var(--neutral); border-radius: var(--rounded-sm); border: 1px solid var(--border-color);">
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
                                            <?php if ((int)($snip['deleted'] ?? 0) === 1): ?>
                                                <span class="badge badge-deleted">Trash</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <!-- View Code Trigger -->
                                                <button class="btn-action btn-view" 
                                                        onclick="viewCode('<?php echo htmlspecialchars($snip['title'], ENT_QUOTES); ?>', <?php echo htmlspecialchars(json_encode($snip['source_code'])); ?>)">
                                                    View Code
                                                </button>
                                                
                                                <?php if ((int)($snip['deleted'] ?? 0) === 1): ?>
                                                    <!-- Restore Action -->
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="restore">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $snip['snippet_id']; ?>">
                                                        <button type="submit" class="btn-action btn-restore">Restore</button>
                                                    </form>
                                                    <!-- Permanent Hard Delete Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คำเตือน: คุณต้องการลบ Snippet นี้ถาวรใช่หรือไม่? การกระทำนี้ไม่สามารถเรียกคืนได้');">
                                                        <input type="hidden" name="action" value="hard_delete">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $snip['snippet_id']; ?>">
                                                        <button type="submit" class="btn-action btn-delete">Force Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Soft Delete Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คุณต้องการย้าย Snippet นี้ไปที่ถังขยะหรือไม่?');">
                                                        <input type="hidden" name="action" value="soft_delete">
                                                        <input type="hidden" name="snippet_id" value="<?php echo $snip['snippet_id']; ?>">
                                                        <button type="submit" class="btn-action btn-delete">Delete</button>
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

    <!-- View Code Modal -->
    <div class="modal-overlay" id="viewCodeModal">
        <div class="modal-card" style="max-width: 800px;">
            <header class="modal-header">
                <span class="modal-title" id="codeModalTitle">Snippet Source Code</span>
                <button class="modal-close" onclick="closeModal('viewCodeModal')">&times;</button>
            </header>
            <div class="modal-body">
                <div class="code-viewer-container">
                    <pre class="code-viewer" id="codeViewerBlock"></pre>
                </div>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeModal('viewCodeModal')">Close</button>
            </footer>
        </div>
    </div>

    <script>
        const viewCodeModal = document.getElementById('viewCodeModal');
        const codeModalTitle = document.getElementById('codeModalTitle');
        const codeViewerBlock = document.getElementById('codeViewerBlock');

        function viewCode(title, code) {
            codeModalTitle.textContent = 'View Code: ' + title;
            codeViewerBlock.textContent = code;
            viewCodeModal.classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // Close modal on clicking outside
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
