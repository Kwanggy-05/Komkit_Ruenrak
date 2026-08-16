<?php
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// Handle POST Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        
        if ($_POST['action'] === 'reject') {
            if ($reportId <= 0) {
                $errorMsg = "ข้อมูลไม่ถูกต้อง / Invalid report ID.";
            } else {
                if ($control->rejectReportAdmin($reportId)) {
                    $successMsg = "ปฏิเสธรายงานแล้ว / Report rejected.";
                } else {
                    $errorMsg = "ปฏิเสธรายงานไม่สำเร็จ / Failed to reject report.";
                }
            }
        } elseif ($_POST['action'] === 'delete_snippet') {
            if ($snippetId <= 0) {
                $errorMsg = "ข้อมูลไม่ถูกต้อง / Invalid snippet ID.";
            } else {
                // Soft delete snippet
                if ($control->softDeleteSnippetAdmin($snippetId)) {
                    // Resolve all reports for this snippet
                    $control->resolveReportsForSnippet($snippetId);
                    $successMsg = "ลบโค้ดและจัดการรายงานเรียบร้อย / Snippet deleted and reports resolved.";
                } else {
                    $errorMsg = "ลบโค้ดไม่สำเร็จ / Failed to delete snippet.";
                }
            }
        }
    }
}

// Get filter status (lowercase matches original enum: 'pending', 'removed', 'rejected')
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
    <title>Manage Reports - Admin Panel</title>
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
                <a href="manage_snippets.php" class="menu-item">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Manage Snippets</span>
                </a>
                <a href="manage_reports.php" class="menu-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>Manage Reports</span>
                </a>
                <a href="manage_users.php" class="menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Manage Users</span>
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
                    <h2 class="page-title">Manage Reports</h2>
                    <div class="header-actions">
                        <!-- Filters (lowercase parameters) -->
                        <div style="display: flex; gap: 8px;">
                            <a href="?status=pending" class="btn-action <?php echo $statusFilter === 'pending' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">Pending</a>
                            <a href="?status=removed" class="btn-action <?php echo $statusFilter === 'removed' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">Removed</a>
                            <a href="?status=rejected" class="btn-action <?php echo $statusFilter === 'rejected' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">Rejected</a>
                            <a href="?status=ALL" class="btn-action <?php echo $statusFilter === 'ALL' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">All</a>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Snippet Title</th>
                                <th>Reason</th>
                                <th>Reporter</th>
                                <th>Created At</th>
                                <th style="width: 110px; text-align: center;">Status</th>
                                <th style="width: 250px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No reports found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $rep): ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($rep['report_id']); ?></td>
                                        <td style="font-weight: 600;">
                                            <?php echo htmlspecialchars($rep['snippet_title']); ?>
                                            <?php if ((int)$rep['snippet_deleted'] === 1): ?>
                                                <span class="badge badge-deleted" style="margin-left: 5px;">Deleted</span>
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
                                                <span class="badge badge-active" style="background-color: #ffeeba; color: #856404; border-color: #ffeeba; text-transform: capitalize;">Pending</span>
                                            <?php elseif ($rep['status'] === 'removed'): ?>
                                                <span class="badge badge-active" style="background-color: #d4edda; color: #155724; border-color: #d4edda; text-transform: capitalize;">Removed</span>
                                            <?php else: ?>
                                                <span class="badge badge-deleted" style="text-transform: capitalize;">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <!-- View Details Trigger -->
                                                <button class="btn-action btn-view" 
                                                        onclick="viewReportDetails(<?php echo htmlspecialchars(json_encode([
                                                            'report_id' => $rep['report_id'],
                                                            'snippet_title' => $rep['snippet_title'],
                                                            'reason' => $rep['reason'],
                                                            'details' => $rep['details'],
                                                            'snippet_code' => $rep['source_code'] ?? 'N/A',
                                                            'owner' => $rep['owner_firstname'] . ' ' . $rep['owner_lastname'] . ' (@' . $rep['owner_username'] . ')'
                                                        ])); ?>)">
                                                    Details
                                                </button>
                                                
                                                <?php if ($rep['status'] === 'pending'): ?>
                                                    <!-- Reject Report Action -->
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('คุณต้องการปฏิเสธคำร้องเรียนนี้ใช่หรือไม่?');">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="report_id" value="<?php echo $rep['report_id']; ?>">
                                                        <button type="submit" class="btn-action btn-restore" style="background-color: #6C757D; color: white;">Dismiss</button>
                                                    </form>
                                                    
                                                    <?php if ((int)$rep['snippet_deleted'] !== 1): ?>
                                                        <!-- Delete Snippet Action -->
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('คุณต้องการอนุมัติคำร้องและลบ Snippet นี้ใช่หรือไม่? (การดำเนินการนี้จะลบ Snippet ออกจากระบบ)');">
                                                            <input type="hidden" name="action" value="delete_snippet">
                                                            <input type="hidden" name="snippet_id" value="<?php echo $rep['snippet_id']; ?>">
                                                            <button type="submit" class="btn-action btn-delete">Delete Code</button>
                                                        </form>
                                                    <?php endif; ?>
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

    <!-- View Report Details Modal -->
    <div class="modal-overlay" id="reportDetailModal">
        <div class="modal-card" style="max-width: 800px; display: flex; flex-direction: column; max-height: 90vh;">
            <header class="modal-header">
                <span class="modal-title" id="modalReportTitle">Report Details</span>
                <button class="modal-close" onclick="closeModal('reportDetailModal')">&times;</button>
            </header>
            <div class="modal-body" style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 15px;">
                <div style="font-size: 0.95rem; line-height: 1.5;">
                    <strong>Snippet Title:</strong> <span id="detailSnippetTitle"></span><br>
                    <strong>Owner:</strong> <span id="detailSnippetOwner"></span>
                </div>
                <div style="font-size: 0.95rem;">
                    <strong>Reported Reason:</strong> <span id="detailReportReason" style="color: var(--danger); font-weight: bold; text-transform: capitalize;"></span>
                </div>
                <div>
                    <strong>Report Description:</strong>
                    <div id="detailReportDesc" style="padding: 12px; background-color: var(--neutral); border-radius: var(--rounded-sm); border: 1px solid var(--border-color); white-space: pre-wrap; font-size: 0.9rem; margin-top: 5px; line-height: 1.4; color: #333;"></div>
                </div>
                <div style="flex-grow: 1; display: flex; flex-direction: column; min-height: 200px;">
                    <strong>Source Code Preview:</strong>
                    <div style="margin-top: 5px; flex-grow: 1; position: relative;">
                        <pre id="detailSnippetCode" style="margin: 0; padding: 12px; background-color: #1e1e1e; color: #d4d4d4; border-radius: var(--rounded-sm); font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; overflow: auto; max-height: 250px; border: 1px solid #333; line-height: 1.5;"></pre>
                    </div>
                </div>
            </div>
            <footer class="modal-footer" style="padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn-primary" style="background-color: var(--primary); border: none; color: white; padding: 8px 18px; border-radius: var(--rounded-sm); font-weight: 600; cursor: pointer;" onclick="closeModal('reportDetailModal')">Close</button>
            </footer>
        </div>
    </div>

    <script>
        function viewReportDetails(data) {
            document.getElementById('modalReportTitle').textContent = 'Report Details #' + data.report_id;
            document.getElementById('detailSnippetTitle').textContent = data.snippet_title;
            document.getElementById('detailSnippetOwner').textContent = data.owner;
            document.getElementById('detailReportReason').textContent = data.reason;
            document.getElementById('detailReportDesc').textContent = data.details || 'ไม่มีคำอธิบายเพิ่มเติม / No description';
            document.getElementById('detailSnippetCode').textContent = data.snippet_code;
            
            document.getElementById('reportDetailModal').classList.add('open');
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
