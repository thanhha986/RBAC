
<?php
require_once __DIR__ . '/auth.php';

function hasPermission($permission) {
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $sql = "SELECT COUNT(*)
            FROM user_roles ur
            JOIN role_permissions rp
                ON ur.role_id = rp.role_id
            JOIN permissions p
                ON rp.permission_id = p.permission_id
            WHERE ur.user_id = ?
              AND p.permission_name = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'],
        $permission
    ]);

    return $stmt->fetchColumn() > 0;
}

function requirePermission($permission) {
    requireLogin();

    if (!hasPermission($permission)) {
        header('Location: /rbac_web/forbidden.php');
        exit;
    }
}
