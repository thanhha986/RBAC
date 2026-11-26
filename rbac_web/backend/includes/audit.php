
<?php
require_once __DIR__ . '/../config/database.php';

function writeAuditLog($userId, $action, $description = '') {
    global $pdo;

    $stmt = $pdo->prepare(
        "INSERT INTO audit_logs
         (user_id, action, description, ip_address)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $userId,
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}
