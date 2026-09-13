<?php
require_once __DIR__ . '/connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        snippet_id INT NOT NULL,
        user_id INT NOT NULL,
        reason ENUM('spam', 'inappropriate', 'malicious_code', 'other') NOT NULL,
        details TEXT,
        status ENUM('pending', 'removed', 'rejected') DEFAULT 'pending',
        resolved_by INT NULL,
        resolved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_snippet_user (snippet_id, user_id),
        CONSTRAINT fk_reports_snippet FOREIGN KEY (snippet_id) REFERENCES snippet(snippet_id) ON DELETE CASCADE,
        CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Database migrated successfully. Table 'reports' is created or already exists.\n";

    // Add account_status column to user table if it does not exist
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `user` LIKE 'account_status'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE `user` ADD COLUMN `account_status` ENUM('active', 'banned') NOT NULL DEFAULT 'active';");
        echo "Column 'account_status' added to 'user' table.\n";
    }

    // Create user_bans table if it does not exist
    $sqlBans = "CREATE TABLE IF NOT EXISTS `user_bans` (
        `ban_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `admin_banned` VARCHAR(100) NOT NULL,
        `reason` TEXT NOT NULL,
        `banned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `unbanned_at` DATETIME NULL DEFAULT NULL,
        `admin_unbanned` VARCHAR(100) NULL DEFAULT NULL,
        CONSTRAINT `fk_user_bans_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlBans);
    echo "Table 'user_bans' is created or already exists.\n";

    // Drop legacy FK on banned_by if exists
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_bans' 
          AND COLUMN_NAME IN ('banned_by', 'admin_banned', 'unbanned_by', 'admin_unbanned')
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fks as $fk) {
        $pdo->exec("ALTER TABLE `user_bans` DROP FOREIGN KEY `$fk`");
    }

    // Rename/modify banned_by to admin_banned VARCHAR(100)
    $hasBannedBy = $pdo->query("SHOW COLUMNS FROM `user_bans` LIKE 'banned_by'")->fetch();
    if ($hasBannedBy) {
        $pdo->exec("ALTER TABLE `user_bans` CHANGE `banned_by` `admin_banned` VARCHAR(100) NOT NULL;");
        $pdo->exec("UPDATE `user_bans` ub JOIN `user` u ON ub.admin_banned = CAST(u.user_id AS CHAR) SET ub.admin_banned = u.username;");
        echo "Column 'banned_by' renamed to 'admin_banned' VARCHAR(100).\n";
    }

    // Add unbanned_at column to user_bans table if it does not exist
    $checkUnbannedAt = $pdo->query("SHOW COLUMNS FROM `user_bans` LIKE 'unbanned_at'")->fetch();
    if (!$checkUnbannedAt) {
        $pdo->exec("ALTER TABLE `user_bans` ADD COLUMN `unbanned_at` DATETIME NULL DEFAULT NULL AFTER `banned_at`;");
        echo "Column 'unbanned_at' added to 'user_bans' table.\n";
    }

    // Rename/modify unbanned_by to admin_unbanned VARCHAR(100)
    $hasUnbannedBy = $pdo->query("SHOW COLUMNS FROM `user_bans` LIKE 'unbanned_by'")->fetch();
    if ($hasUnbannedBy) {
        $pdo->exec("ALTER TABLE `user_bans` CHANGE `unbanned_by` `admin_unbanned` VARCHAR(100) NULL DEFAULT NULL;");
        $pdo->exec("UPDATE `user_bans` ub JOIN `user` u ON ub.admin_unbanned = CAST(u.user_id AS CHAR) SET ub.admin_unbanned = u.username WHERE ub.admin_unbanned IS NOT NULL AND ub.admin_unbanned != '';");
        echo "Column 'unbanned_by' renamed to 'admin_unbanned' VARCHAR(100).\n";
    }

    // Ensure admin_unbanned column exists
    $hasAdminUnbanned = $pdo->query("SHOW COLUMNS FROM `user_bans` LIKE 'admin_unbanned'")->fetch();
    if (!$hasAdminUnbanned) {
        $pdo->exec("ALTER TABLE `user_bans` ADD COLUMN `admin_unbanned` VARCHAR(100) NULL DEFAULT NULL AFTER `unbanned_at`;");
        echo "Column 'admin_unbanned' added to 'user_bans' table.\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
