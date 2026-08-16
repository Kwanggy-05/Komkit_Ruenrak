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
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
