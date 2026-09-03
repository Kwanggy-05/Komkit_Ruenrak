<?php
require_once __DIR__ . '/check_admin.php';

// Get filter inputs
$searchQuery = trim($_GET['q'] ?? '');
$selectedRole = trim($_GET['role'] ?? '');

// Pagination settings
$allowedLimits = [10, 15, 20, 25];
if (isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits)) {
    $itemsPerPage = (int)$_GET['limit'];
} else {
    $itemsPerPage = 10;
}
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get users data
$totalUsers = $user->getTotalUsersCount($searchQuery, $selectedRole);
$users = $user->getUsersPaginated($currentPage, $itemsPerPage, $searchQuery, $selectedRole);

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
                    <a href="../login.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">
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
                </div>

                <!-- Content Header Controls -->
                <div class="content-header" style="margin-bottom: 16px;">
                    <h2 class="page-title">Manage Users</h2>
                </div>

                <!-- Filters & Search Bar -->
                <div class="filters-toolbar">
                    <form method="GET" action="manage_users.php" style="display: flex; gap: 12px; align-items: center; width: 100%; flex-wrap: wrap;">
                        <div class="search-wrapper" style="flex-grow: 1; max-width: 400px; display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: var(--rounded-md); padding: 8px 16px; background-color: var(--tertiary);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; color: var(--text-muted); margin-right: 8px;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="q" class="search-input" placeholder="Search name or username..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="border: none; outline: none; width: 100%; font-size: 0.95rem;">
                        </div>
                        
                        <select name="role" class="select-per-page" onchange="this.form.submit()" style="height: 38px; border-radius: var(--rounded-md); font-weight: 600; padding: 0 12px;">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $selectedRole === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        

                        
                        <?php if ($itemsPerPage !== 10): ?>
                            <input type="hidden" name="limit" value="<?php echo $itemsPerPage; ?>">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn-primary" style="height: 38px; border-radius: var(--rounded-md); padding: 0 16px; font-weight: 600;">Search</button>
                    </form>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="col-id" style="width: 80px;">ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No users found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $row): ?>
                                    <?php 
                                        $roleClass = $row['role'] === 'admin' ? 'badge-admin' : 'badge-user';
                                    ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($row['user_id']); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><span class="badge <?php echo $roleClass; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                        <td style="font-size: 0.9rem; color: var(--text-muted);">
                                            <?php echo !empty($row['created_at']) ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-'; ?>
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
    </script>
</body>
</html>
