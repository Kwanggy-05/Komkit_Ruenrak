<?php
require_once __DIR__ . '/check_admin.php';

// Handle AJAX requests for ban history
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_GET['ajax'] === 'user_history') {
        $targetUserId = (int)($_GET['user_id'] ?? 0);
        $history = $user->getUserBanHistory($targetUserId);
        echo json_encode(['success' => true, 'data' => $history]);
        exit;
    }
    if ($_GET['ajax'] === 'all_history') {
        $history = $user->getAllBanHistory();
        echo json_encode(['success' => true, 'data' => $history]);
        exit;
    }
}

$successMsg = "";
$errorMsg = "";

// Handle POST actions (Ban User)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'ban_user') {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $reasonPreset = trim($_POST['reason_preset'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $adminId = (int)$userId;

    if ($reasonPreset === '') {
        $errorMsg = "Please select a reason for banning.";
    } elseif ($reasonPreset === 'Other' && $details === '') {
        $errorMsg = "Please specify additional details for the 'Other' reason.";
    } else {
        if ($reasonPreset === 'Other') {
            $reason = $details !== '' ? "Other: " . $details : "Other";
        } else {
            $reason = $details !== '' ? $reasonPreset . " - " . $details : $reasonPreset;
        }

        $banResult = $user->banUser($targetUserId, $adminId, $reason);
        if ($banResult['success']) {
            $successMsg = $banResult['message'];
        } else {
            $errorMsg = $banResult['message'];
        }
    }
}

// Handle POST actions (Unban User)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'unban_user') {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $adminId = (int)$userId;

    $unbanResult = $user->unbanUser($targetUserId, $adminId);
    if ($unbanResult['success']) {
        $successMsg = $unbanResult['message'];
    } else {
        $errorMsg = $unbanResult['message'];
    }
}

// Get filter inputs
$searchQuery = trim($_GET['q'] ?? '');
$selectedRole = trim($_GET['role'] ?? '');
$selectedStatus = trim($_GET['status'] ?? '');

// Pagination settings
$allowedLimits = [10, 15, 20, 25];
if (isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits)) {
    $itemsPerPage = (int)$_GET['limit'];
} else {
    $itemsPerPage = 10;
}
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get users data
$totalUsers = $user->getTotalUsersCount($searchQuery, $selectedRole, $selectedStatus);
$users = $user->getUsersPaginated($currentPage, $itemsPerPage, $searchQuery, $selectedRole, $selectedStatus);

$totalPages = ceil($totalUsers / $itemsPerPage);
if ($totalPages === 0) $totalPages = 1;

// Get stats
$stats = $user->getUserStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* Custom User Management specific styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background-color: var(--tertiary);
            border-radius: var(--rounded-md);
            border: 1px solid var(--border-color);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-card);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary);
        }
        .stat-icon.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #b45309;
        }
        .stat-icon.success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--surface);
        }
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary);
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        
        .badge-admin {
            background-color: rgba(0, 123, 255, 0.15);
            color: var(--primary);
        }
        .badge-user {
            background-color: rgba(108, 117, 125, 0.15);
            color: var(--text-muted);
        }
        
        .filters-toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-item {
            flex-grow: 1;
            min-width: 200px;
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
                <a href="manage_users.php" class="menu-item active">
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
                    <?php if (!empty($pendingReportsCount) && $pendingReportsCount > 0): ?>
                        <span class="sidebar-dot" title="Pending Reports"></span>
                    <?php endif; ?>
                </a>
                <a href="manage_appeals.php" class="menu-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <span>Manage Appeals</span>
                    <?php if (!empty($pendingAppealsCount) && $pendingAppealsCount > 0): ?>
                        <span class="sidebar-dot" title="Pending Appeals"></span>
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
                <!-- Summary Cards Section -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $stats['total']; ?></span>
                            <span class="stat-label">Total Users</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $stats['today']; ?></span>
                            <span class="stat-label">New Users Today</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger" style="background-color: rgba(220, 53, 69, 0.1); color: var(--danger);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $stats['banned']; ?></span>
                            <span class="stat-label">Banned Users</span>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($successMsg !== ""): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><?php echo htmlspecialchars($successMsg); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg !== ""): ?>
                    <div class="alert alert-danger">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo htmlspecialchars($errorMsg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Content Header Controls -->
                <div class="content-header" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <h2 class="page-title">Manage Users</h2>
                    <button type="button" class="btn-secondary" onclick="openAllHistoryModal()" style="display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        All Ban History
                    </button>
                </div>

                <!-- Filters & Search Bar -->
                <div class="filters-toolbar">
                    <form method="GET" action="manage_users.php" style="display: flex; gap: 12px; align-items: center; width: 100%; flex-wrap: wrap;">
                        <div class="search-wrapper" style="flex-grow: 1; max-width: 360px; display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: var(--rounded-md); padding: 8px 16px; background-color: var(--tertiary);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; color: var(--text-muted); margin-right: 8px;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="q" class="search-input" placeholder="Search name or username..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="border: none; outline: none; width: 100%; font-size: 0.95rem;">
                        </div>
                        
                        <select name="role" class="select-per-page" onchange="this.form.submit()" style="height: 38px; border-radius: var(--rounded-md); font-weight: 600; padding: 0 12px; border: 1px solid var(--border-color); background: white;">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $selectedRole === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>

                        <select name="status" class="select-per-page" onchange="this.form.submit()" style="height: 38px; border-radius: var(--rounded-md); font-weight: 600; padding: 0 12px; border: 1px solid var(--border-color); background: white;">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $selectedStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="banned" <?php echo $selectedStatus === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                        
                        <?php if ($itemsPerPage !== 10): ?>
                            <input type="hidden" name="limit" value="<?php echo $itemsPerPage; ?>">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn-primary" style="height: 38px; border-radius: var(--rounded-md); padding: 0 16px; font-weight: 600;">Search</button>
                        <?php if ($searchQuery !== '' || $selectedRole !== '' || $selectedStatus !== ''): ?>
                            <a href="manage_users.php" class="btn-secondary" style="height: 38px; line-height: 20px; display: inline-flex; align-items: center; text-decoration: none;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-id" style="width: 70px;">ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th style="width: 100px;">Role</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 170px;">Registered At</th>
                                <th style="width: 200px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No users found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $row): ?>
                                    <?php 
                                        $roleClass = $row['role'] === 'admin' ? 'badge-admin' : 'badge-user';
                                        $isBanned = isset($row['account_status']) && $row['account_status'] === 'banned';
                                        $statusBadgeClass = $isBanned ? 'badge-status-banned' : 'badge-status-active';
                                        $statusLabel = $isBanned ? 'Banned' : 'Active';
                                    ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($row['user_id']); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><span class="badge <?php echo $roleClass; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                        <td><span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $statusLabel; ?></span></td>
                                        <td style="font-size: 0.9rem; color: var(--text-muted);">
                                            <?php echo !empty($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-'; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($row['role'] === 'admin'): ?>
                                                <span style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">-</span>
                                            <?php elseif (!$isBanned): ?>
                                                <button type="button" class="btn-ban" onclick="openBanModal(<?php echo (int)$row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username']), ENT_QUOTES); ?>')">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                    </svg>
                                                    Ban Account
                                                </button>
                                            <?php else: ?>
                                                 <div style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                                    <button type="button" class="btn-unban" onclick="openUnbanModal(<?php echo (int)$row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username']), ENT_QUOTES); ?>')">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                            <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                                        </svg>
                                                        Unban
                                                    </button>
                                                    <button type="button" class="btn-history" onclick="openUserHistoryModal(<?php echo (int)$row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username']), ENT_QUOTES); ?>')">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <polyline points="12 6 12 12 16 14"></polyline>
                                                        </svg>
                                                        View History
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php
                                $pageUrl = 'manage_users.php?page=' . $i;
                                if ($searchQuery !== '') $pageUrl .= '&q=' . urlencode($searchQuery);
                                if ($selectedRole !== '') $pageUrl .= '&role=' . urlencode($selectedRole);
                                if ($selectedStatus !== '') $pageUrl .= '&status=' . urlencode($selectedStatus);
                                if ($itemsPerPage !== 10) $pageUrl .= '&limit=' . $itemsPerPage;
                            ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="active" style="background: var(--primary); color: white; padding: 8px 12px; border-radius: var(--rounded-sm); font-weight: 700;"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $pageUrl; ?>" style="background: white; border: 1px solid var(--border-color); color: var(--text-main); padding: 8px 12px; border-radius: var(--rounded-sm); text-decoration: none; font-weight: 600;"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal 1: Confirmation Form for Banning User (Permanent Ban) -->
    <div class="modal-overlay" id="banModalOverlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Ban Account</h3>
                <button type="button" class="modal-close" onclick="closeBanModal()">&times;</button>
            </div>
            <form method="POST" action="manage_users.php" id="banForm" onsubmit="return validateBanForm();">
                <div class="modal-body">
                    <input type="hidden" name="action" value="ban_user">
                    <input type="hidden" name="user_id" id="banUserId" value="">
                    
                    <table class="ban-info-table">
                        <tr>
                            <td class="ban-info-label">Username:</td>
                            <td class="ban-info-val" id="banUsernameDisplay">-</td>
                        </tr>
                        <tr>
                            <td class="ban-info-label">Status:</td>
                            <td>
                                <span class="badge badge-status-active">Active</span>
                                <span style="margin: 0 6px; font-weight: bold; color: var(--text-muted);">→</span>
                                <span class="badge badge-status-banned">Banned</span>
                            </td>
                        </tr>
                    </table>

                    <div class="form-group" style="margin-top: 16px;">
                        <label class="form-label" style="margin-bottom: 8px; font-weight: 600;">Ban Reason: <span style="color: var(--danger);">*</span></label>
                        <div class="ban-reasons-list" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px;">
                            <label class="ban-reason-option">
                                <input type="radio" name="reason_preset" value="Spam / Misleading" required onchange="handleReasonPresetChange(this)">
                                <span>Spam / Misleading</span>
                            </label>
                            <label class="ban-reason-option">
                                <input type="radio" name="reason_preset" value="Inappropriate Content / Violent / Harassment" onchange="handleReasonPresetChange(this)">
                                <span>Inappropriate Content / Violent / Harassment</span>
                            </label>
                            <label class="ban-reason-option">
                                <input type="radio" name="reason_preset" value="Malicious Code / Malware" onchange="handleReasonPresetChange(this)">
                                <span>Malicious Code / Malware</span>
                            </label>
                            <label class="ban-reason-option">
                                <input type="radio" name="reason_preset" value="Other" onchange="handleReasonPresetChange(this)">
                                <span>Other</span>
                            </label>
                        </div>

                        <label for="banDetails" class="form-label" style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">
                            Additional Details <span id="banDetailsRequired" style="color: var(--danger); display: none;">*</span>:
                        </label>
                        <textarea name="details" id="banDetails" class="form-control" rows="3" style="height: auto; resize: vertical; padding: 10px; font-size: 0.9rem;" placeholder="Specify additional details (optional)..."></textarea>
                        <div id="banReasonError" style="color: var(--danger); font-size: 0.85rem; margin-top: 6px; display: none;">Please specify a ban reason</div>
                    </div>

                    <div class="ban-warning-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <span>⚠ This action will permanently ban the account</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeBanModal()">Cancel</button>
                    <button type="submit" class="btn-ban" style="padding: 10px 20px; font-size: 0.95rem;">Confirm Ban</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Confirmation Form for Unbanning User -->
    <div class="modal-overlay" id="unbanModalOverlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Unban Account</h3>
                <button type="button" class="modal-close" onclick="closeUnbanModal()">&times;</button>
            </div>
            <form method="POST" action="manage_users.php" id="unbanForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="unban_user">
                    <input type="hidden" name="user_id" id="unbanUserId" value="">
                    
                    <table class="ban-info-table">
                        <tr>
                            <td class="ban-info-label">Username:</td>
                            <td class="ban-info-val" id="unbanUsernameDisplay">-</td>
                        </tr>
                        <tr>
                            <td class="ban-info-label">Status:</td>
                            <td>
                                <span class="badge badge-status-banned">Banned</span>
                                <span style="margin: 0 6px; font-weight: bold; color: var(--text-muted);">→</span>
                                <span class="badge badge-status-active">Active</span>
                            </td>
                        </tr>
                    </table>

                    <div style="background-color: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); border-radius: var(--rounded-sm); padding: 12px 16px; margin-top: 16px; color: #1e7e34; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>Once unbanned, this account will be able to log in and use the system normally.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeUnbanModal()">Cancel</button>
                    <button type="submit" class="btn-unban" style="padding: 10px 20px; font-size: 0.95rem;">Confirm Unban</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: User Ban History Modal -->
    <div class="modal-overlay" id="userHistoryModalOverlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header">
                <h3 class="modal-title">Ban History: <span id="userHistoryUsername" style="color: var(--primary);"></span></h3>
                <button type="button" class="modal-close" onclick="closeUserHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="userHistoryLoading" style="text-align: center; padding: 24px; color: var(--text-muted);">
                    Loading data...
                </div>
                <div id="userHistoryContent" style="display: none; max-height: 400px; overflow-y: auto;">
                    <table class="history-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 60px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">ID</th>
                                <th style="width: 130px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Banned By</th>
                                <th style="padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Reason</th>
                                <th style="width: 150px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Banned At</th>
                                <th style="width: 150px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Unbanned At</th>
                            </tr>
                        </thead>
                        <tbody id="userHistoryTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="userHistoryEmpty" style="text-align: center; padding: 24px; color: var(--text-muted); display: none;">
                    No ban history found for this account.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUserHistoryModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal 3: All Ban History Modal -->
    <div class="modal-overlay" id="allHistoryModalOverlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header">
                <h3 class="modal-title">All Ban History in System</h3>
                <button type="button" class="modal-close" onclick="closeAllHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="allHistoryLoading" style="text-align: center; padding: 24px; color: var(--text-muted);">
                    Loading data...
                </div>
                <div id="allHistoryContent" style="display: none; max-height: 400px; overflow-y: auto;">
                    <table class="history-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 60px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">ID</th>
                                <th style="width: 120px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Banned User</th>
                                <th style="width: 120px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Banned By</th>
                                <th style="padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Reason</th>
                                <th style="width: 150px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Banned At</th>
                                <th style="width: 150px; padding: 10px 14px; background: var(--neutral); text-align: left; border-bottom: 1px solid var(--border-color);">Unbanned At</th>
                            </tr>
                        </thead>
                        <tbody id="allHistoryTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="allHistoryEmpty" style="text-align: center; padding: 24px; color: var(--text-muted); display: none;">
                    No ban history found in system.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeAllHistoryModal()">Close</button>
            </div>
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

        function handleReasonPresetChange(radio) {
            const reqSpan = document.getElementById('banDetailsRequired');
            const detailsTextarea = document.getElementById('banDetails');
            const errorElem = document.getElementById('banReasonError');
            errorElem.style.display = 'none';

            if (radio.value === 'Other') {
                reqSpan.style.display = 'inline';
                detailsTextarea.placeholder = 'Please specify details for ban reason...';
                detailsTextarea.focus();
            } else {
                reqSpan.style.display = 'none';
                detailsTextarea.placeholder = 'Specify additional details (optional)...';
            }
        }

        // Modal 1: Ban Confirmation Modal Controls
        function openBanModal(userId, username) {
            document.getElementById('banUserId').value = userId;
            document.getElementById('banUsernameDisplay').textContent = username;
            
            // Reset reason radio buttons and details
            document.querySelectorAll('input[name="reason_preset"]').forEach(r => r.checked = false);
            document.getElementById('banDetails').value = '';
            document.getElementById('banDetailsRequired').style.display = 'none';
            document.getElementById('banDetails').placeholder = 'Specify additional details (optional)...';
            document.getElementById('banReasonError').style.display = 'none';

            document.getElementById('banModalOverlay').classList.add('open');
        }

        function closeBanModal() {
            document.getElementById('banModalOverlay').classList.remove('open');
        }

        function validateBanForm() {
            const selectedPreset = document.querySelector('input[name="reason_preset"]:checked');
            const details = document.getElementById('banDetails').value.trim();
            const errorElem = document.getElementById('banReasonError');

            if (!selectedPreset) {
                errorElem.textContent = 'Please select a reason for banning.';
                errorElem.style.display = 'block';
                return false;
            }

            if (selectedPreset.value === 'Other' && details === '') {
                errorElem.textContent = 'Please specify additional details for the Other reason.';
                errorElem.style.display = 'block';
                document.getElementById('banDetails').focus();
                return false;
            }

            errorElem.style.display = 'none';
            return confirm('Are you sure you want to ban this user?');
        }

        // Modal: Unban Confirmation Modal Controls
        function openUnbanModal(userId, username) {
            document.getElementById('unbanUserId').value = userId;
            document.getElementById('unbanUsernameDisplay').textContent = username;
            document.getElementById('unbanModalOverlay').classList.add('open');
        }

        function closeUnbanModal() {
            document.getElementById('unbanModalOverlay').classList.remove('open');
        }

        // Modal 2: User Ban History Modal Controls
        function openUserHistoryModal(userId, username) {
            document.getElementById('userHistoryUsername').textContent = username;
            document.getElementById('userHistoryLoading').style.display = 'block';
            document.getElementById('userHistoryContent').style.display = 'none';
            document.getElementById('userHistoryEmpty').style.display = 'none';
            document.getElementById('userHistoryModalOverlay').classList.add('open');

            fetch('manage_users.php?ajax=user_history&user_id=' + encodeURIComponent(userId))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('userHistoryLoading').style.display = 'none';
                    const tbody = document.getElementById('userHistoryTableBody');
                    tbody.innerHTML = '';

                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(item => {
                            const tr = document.createElement('tr');
                            const unbanAdminText = item.admin_unbanned ? ` <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">(by ${escapeHtml(item.admin_unbanned)})</span>` : '';
                            const unbanHtml = item.unbanned_at 
                                ? `<span style="color: var(--surface); font-weight: 600;">${escapeHtml(item.unbanned_at)}</span>${unbanAdminText}` 
                                : `<span style="color: var(--danger); font-size: 0.8rem; font-weight: 600; background: rgba(220,53,69,0.1); padding: 3px 8px; border-radius: var(--rounded-sm);">Still Banned</span>`;

                            tr.innerHTML = `
                                <td style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.ban_id)}</td>
                                <td style="padding: 10px 14px; font-weight: 600; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.admin_banned || item.admin_username)}</td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.reason)}</td>
                                <td style="padding: 10px 14px; font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border-color);">${escapeHtml(item.banned_at)}</td>
                                <td style="padding: 10px 14px; font-size: 0.85rem; border-bottom: 1px solid var(--border-color);">${unbanHtml}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                        document.getElementById('userHistoryContent').style.display = 'block';
                    } else {
                        document.getElementById('userHistoryEmpty').style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('userHistoryLoading').textContent = 'An error occurred while loading data.';
                });
        }

        function closeUserHistoryModal() {
            document.getElementById('userHistoryModalOverlay').classList.remove('open');
        }

        // Modal 3: All Ban History Modal Controls
        function openAllHistoryModal() {
            document.getElementById('allHistoryLoading').style.display = 'block';
            document.getElementById('allHistoryContent').style.display = 'none';
            document.getElementById('allHistoryEmpty').style.display = 'none';
            document.getElementById('allHistoryModalOverlay').classList.add('open');

            fetch('manage_users.php?ajax=all_history')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('allHistoryLoading').style.display = 'none';
                    const tbody = document.getElementById('allHistoryTableBody');
                    tbody.innerHTML = '';

                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(item => {
                            const tr = document.createElement('tr');
                            const unbanAdminText = item.admin_unbanned ? ` <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">(by ${escapeHtml(item.admin_unbanned)})</span>` : '';
                            const unbanHtml = item.unbanned_at 
                                ? `<span style="color: var(--surface); font-weight: 600;">${escapeHtml(item.unbanned_at)}</span>${unbanAdminText}` 
                                : `<span style="color: var(--danger); font-size: 0.8rem; font-weight: 600; background: rgba(220,53,69,0.1); padding: 3px 8px; border-radius: var(--rounded-sm);">Still Banned</span>`;

                            tr.innerHTML = `
                                <td style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.ban_id)}</td>
                                <td style="padding: 10px 14px; font-weight: 600; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.banned_username)}</td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.admin_banned || item.banned_by_username)}</td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">${escapeHtml(item.reason)}</td>
                                <td style="padding: 10px 14px; font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border-color);">${escapeHtml(item.banned_at)}</td>
                                <td style="padding: 10px 14px; font-size: 0.85rem; border-bottom: 1px solid var(--border-color);">${unbanHtml}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                        document.getElementById('allHistoryContent').style.display = 'block';
                    } else {
                        document.getElementById('allHistoryEmpty').style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('allHistoryLoading').textContent = 'An error occurred while loading data.';
                });
        }

        function closeAllHistoryModal() {
            document.getElementById('allHistoryModalOverlay').classList.remove('open');
        }

        // Close modal on Escape key press or overlay click
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeBanModal();
                closeUnbanModal();
                closeUserHistoryModal();
                closeAllHistoryModal();
            }
        });

        [document.getElementById('banModalOverlay'), document.getElementById('unbanModalOverlay'), document.getElementById('userHistoryModalOverlay'), document.getElementById('allHistoryModalOverlay')].forEach(overlay => {
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        overlay.classList.remove('open');
                    }
                });
            }
        });

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
