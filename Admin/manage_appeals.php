<?php
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";

// Handle Actions (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        $appealId = (int)($_POST['appeal_id'] ?? 0);
        $adminUsername = $_SESSION['username'] ?? 'Admin';
        $adminNotes = trim($_POST['admin_notes'] ?? '');

        if ($appealId <= 0) {
            $errorMsg = "Invalid appeal ID.";
        } else {
            if ($_POST['action'] === 'approve') {
                if ($control->approveAppealAdmin($appealId, $adminUsername, $adminNotes)) {
                    $successMsg = "Appeal approved successfully. The snippet has been unsuspended.";
                } else {
                    $errorMsg = "Failed to approve appeal.";
                }
            } elseif ($_POST['action'] === 'reject') {
                if ($control->rejectAppealAdmin($appealId, $adminUsername, $adminNotes)) {
                    $successMsg = "Appeal rejected successfully. The snippet remains suspended.";
                } else {
                    $errorMsg = "Failed to reject appeal.";
                }
            }
        }
    }
}

// Get filter status
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'ALL'])) {
    $statusFilter = 'pending';
}

$appeals = [];
$appealsResult = $control->getAllAppealsAdmin($statusFilter === 'ALL' ? null : $statusFilter);
if ($appealsResult) {
    $appeals = $appealsResult->fetchAll(PDO::FETCH_ASSOC);
}

$pendingCount = $control->getPendingAppealsCountAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appeals - Admin Panel</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .sidebar-badge {
            background-color: #dc2626;
            color: white;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            margin-left: auto;
        }
    </style>
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
                <a href="manage_languages.php" class="menu-item">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>Manage Language</span>
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
                <a href="manage_reports.php" class="menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>Manage Reports</span>
                </a>
                <a href="manage_appeals.php" class="menu-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <span>Manage Appeals</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?php echo $pendingCount; ?></span>
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
                            <span class="user-role">Admin</span>
                        </div>
                    </div>
                    <a href="login.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">
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
                    <h2 class="page-title">Manage Snippet Appeals (คำร้องถอนการระงับโพสต์)</h2>
                    <div class="header-actions">
                        <!-- Filters -->
                        <div style="display: flex; gap: 8px;">
                            <a href="?status=pending" class="btn-action <?php echo $statusFilter === 'pending' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">
                                Pending <?php if($pendingCount > 0): ?>(<?php echo $pendingCount; ?>)<?php endif; ?>
                            </a>
                            <a href="?status=approved" class="btn-action <?php echo $statusFilter === 'approved' ? 'btn-view' : ''; ?>" style="text-decoration: none; padding: 6px 12px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); color: var(--text-main); font-weight: 600;">Approved</a>
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
                                <th>Snippet</th>
                                <th>Owner / Appellant</th>
                                <th>Appeal Reason</th>
                                <th>Submitted At</th>
                                <th style="width: 100px; text-align: center;">Status</th>
                                <th style="width: 220px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appeals)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No appeals found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appeals as $app): ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($app['appeal_id']); ?></td>
                                        <td>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($app['snippet_title']); ?>
                                                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal;">(ID: <?php echo htmlspecialchars($app['snippet_id']); ?>)</span>
                                            </div>
                                            <div style="margin-top: 4px;">
                                                <a href="viewcode.php?view=<?php echo $app['snippet_id']; ?>" target="_blank" style="font-size: 0.78rem; color: #2563eb; text-decoration: underline;">
                                                    View Snippet Code ↗
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.9rem; color: var(--text-main); font-weight: 500;">
                                                <?php echo htmlspecialchars($app['appellant_firstname'] . ' ' . $app['appellant_lastname']); ?>
                                            </span>
                                            <br>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                (@<?php echo htmlspecialchars($app['appellant_username']); ?>)
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.88rem; max-width: 280px; white-space: pre-wrap; word-break: break-word; line-height: 1.4;">
                                                <?php 
                                                    $r = $app['reason'];
                                                    if(mb_strlen($r, 'UTF-8') > 90){
                                                        echo htmlspecialchars(mb_substr($r, 0, 90, 'UTF-8')) . '...';
                                                    } else {
                                                        echo htmlspecialchars($r);
                                                    }
                                                ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($app['created_at']))); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($app['status'] === 'pending'): ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php elseif ($app['status'] === 'approved'): ?>
                                                <span class="badge badge-approved">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-rejected">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons" style="justify-content: center;">
                                                <button type="button" class="btn-action btn-view btn-open-review" 
                                                    data-appeal='<?php echo htmlspecialchars(json_encode($app), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <?php echo $app['status'] === 'pending' ? 'Review Appeal' : 'View Details'; ?>
                                                </button>
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

    <!-- Review Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-card" style="max-width: 580px; width: 92%;">
            <header class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Appeal Details & Review
                </span>
                <button class="modal-close" id="closeReviewModalBtn">&times;</button>
            </header>
            <form method="POST" id="reviewForm">
                <input type="hidden" name="appeal_id" id="modalAppealId" value="">
                <input type="hidden" name="action" id="modalAction" value="">

                <div class="modal-body" style="padding: 20px;">
                    <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);" id="modalSnippetTitle"></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                            Owner: <span id="modalAppellantName" style="font-weight: 600; color: var(--text-main);"></span> 
                            (<span id="modalAppellantUsername"></span>)
                        </div>
                    </div>

                    <!-- Original Suspension Reason -->
                    <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #991b1b; text-transform: uppercase; margin-bottom: 3px;">
                            Original Suspension Reason (เหตุผลที่ระงับ)
                        </div>
                        <div style="font-size: 0.9rem; color: #7f1d1d;" id="modalSuspensionReason"></div>
                    </div>

                    <!-- User's Appeal Reason -->
                    <div style="background-color: #eff6ff; border: 1px solid #dbeafe; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 3px;">
                            User's Appeal Reason (คำชี้แจงของผู้ใช้)
                        </div>
                        <div style="font-size: 0.9rem; color: #1e3a8a; white-space: pre-wrap;" id="modalAppealReason"></div>
                        <div style="font-size: 0.75rem; color: #3b82f6; margin-top: 6px;" id="modalSubmittedTime"></div>
                    </div>

                    <!-- Review Decision Info (if already reviewed) -->
                    <div id="reviewedInfoBox" style="display: none; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                            Review Result (ผลการพิจารณา)
                        </div>
                        <div style="font-size: 0.88rem; color: var(--text-main);" id="modalReviewDetails"></div>
                    </div>

                    <!-- Admin Notes Input (enabled for pending) -->
                    <div id="adminNotesGroup" class="form-group" style="margin-top: 10px;">
                        <label for="adminNotesInput" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                            Admin Notes / Feedback to User (บันทึกหรือเหตุผลจากแอดมิน):
                        </label>
                        <textarea id="adminNotesInput" name="admin_notes" rows="3" placeholder="ระบุเหตุผลหรือข้อความตอบกลับผู้ใช้ (ถ้ามี)..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--rounded-sm); font-size: 0.9rem; resize: vertical; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <footer class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border-color); background-color: #fafafa; border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn-action" id="closeReviewBtn" style="border: 1px solid var(--border-color); background: #fff; padding: 8px 16px;">Close</button>
                    
                    <div id="pendingActionButtons" style="display: flex; gap: 8px;">
                        <button type="button" class="btn-action btn-delete" id="btnRejectAppeal" style="background-color: #dc2626; color: white; padding: 8px 16px; border: none;">
                            Reject Appeal
                        </button>
                        <button type="button" class="btn-action btn-restore" id="btnApproveAppeal" style="background-color: #16a34a; color: white; padding: 8px 16px; border: none;">
                            Approve & Unsuspend
                        </button>
                    </div>
                </footer>
            </form>
        </div>
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

        // Modal Review functionality
        const reviewModal = document.getElementById('reviewModal');
        const closeReviewModalBtn = document.getElementById('closeReviewModalBtn');
        const closeReviewBtn = document.getElementById('closeReviewBtn');
        const reviewForm = document.getElementById('reviewForm');
        const modalAppealId = document.getElementById('modalAppealId');
        const modalAction = document.getElementById('modalAction');
        const modalSnippetTitle = document.getElementById('modalSnippetTitle');
        const modalAppellantName = document.getElementById('modalAppellantName');
        const modalAppellantUsername = document.getElementById('modalAppellantUsername');
        const modalSuspensionReason = document.getElementById('modalSuspensionReason');
        const modalAppealReason = document.getElementById('modalAppealReason');
        const modalSubmittedTime = document.getElementById('modalSubmittedTime');
        const reviewedInfoBox = document.getElementById('reviewedInfoBox');
        const modalReviewDetails = document.getElementById('modalReviewDetails');
        const adminNotesGroup = document.getElementById('adminNotesGroup');
        const adminNotesInput = document.getElementById('adminNotesInput');
        const pendingActionButtons = document.getElementById('pendingActionButtons');
        const btnApproveAppeal = document.getElementById('btnApproveAppeal');
        const btnRejectAppeal = document.getElementById('btnRejectAppeal');

        function closeReview() {
            reviewModal?.classList.remove('open');
            reviewForm?.reset();
        }

        closeReviewModalBtn?.addEventListener('click', closeReview);
        closeReviewBtn?.addEventListener('click', closeReview);
        reviewModal?.addEventListener('click', function(e) {
            if (e.target === reviewModal) {
                closeReview();
            }
        });

        document.querySelectorAll('.btn-open-review').forEach(btn => {
            btn.addEventListener('click', function() {
                const appeal = JSON.parse(this.dataset.appeal);
                modalAppealId.value = appeal.appeal_id;
                modalSnippetTitle.textContent = appeal.snippet_title + ' (Snippet ID: #' + appeal.snippet_id + ')';
                modalAppellantName.textContent = appeal.appellant_firstname + ' ' + appeal.appellant_lastname;
                modalAppellantUsername.textContent = '@' + appeal.appellant_username;
                modalSuspensionReason.textContent = appeal.suspension_reason || 'No suspension reason specified';
                modalAppealReason.textContent = appeal.reason;
                modalSubmittedTime.textContent = 'Submitted on: ' + appeal.created_at;

                if (appeal.status === 'pending') {
                    reviewedInfoBox.style.display = 'none';
                    adminNotesGroup.style.display = 'block';
                    adminNotesInput.removeAttribute('readonly');
                    adminNotesInput.value = '';
                    pendingActionButtons.style.display = 'flex';
                } else {
                    reviewedInfoBox.style.display = 'block';
                    let statusLabel = appeal.status === 'approved' ? '<span style="color:#16a34a; font-weight:700;">APPROVED</span>' : '<span style="color:#dc2626; font-weight:700;">REJECTED</span>';
                    modalReviewDetails.innerHTML = '<strong>Status:</strong> ' + statusLabel + '<br>' +
                                                   '<strong>Reviewed By:</strong> ' + (appeal.reviewed_by || 'Admin') + '<br>' +
                                                   '<strong>Reviewed At:</strong> ' + (appeal.reviewed_at || '-') + '<br>' +
                                                   '<strong>Admin Notes:</strong> ' + (appeal.admin_notes ? appeal.admin_notes : '<em>None</em>');
                    adminNotesGroup.style.display = 'none';
                    pendingActionButtons.style.display = 'none';
                }

                reviewModal?.classList.add('open');
            });
        });

        btnApproveAppeal?.addEventListener('click', function() {
            if (confirm('Are you sure you want to APPROVE this appeal and UNSUSPEND this snippet?')) {
                modalAction.value = 'approve';
                reviewForm.submit();
            }
        });

        btnRejectAppeal?.addEventListener('click', function() {
            if (confirm('Are you sure you want to REJECT this appeal? The snippet will remain suspended.')) {
                modalAction.value = 'reject';
                reviewForm.submit();
            }
        });
    </script>
</body>
</html>
