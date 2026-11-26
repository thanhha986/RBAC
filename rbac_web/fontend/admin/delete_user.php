
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/csrf.php';

// Kiểm tra quyền xóa người dùng
requirePermission('delete_users');

// Chỉ cho phép phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Kiểm tra CSRF token
verifyCsrf();

// Lấy ID từ form users.php
$userId = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId || $userId <= 0) {
    http_response_code(400);
    exit('Invalid ID.');
}

// Không cho phép tự xóa tài khoản
if ((int)$userId === (int)$_SESSION['user_id']) {
    http_response_code(403);
    exit('You cannot delete your own account.');
}

try {
    $pdo->beginTransaction();

    // Lấy thông tin tài khoản cần xóa
    $stmt = $pdo->prepare(
        "SELECT username
         FROM users
         WHERE user_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$userId]);
    $username = $stmt->fetchColumn();

    if ($username === false) {
        $pdo->rollBack();

        http_response_code(404);
        exit('User not found.');
    }

    // Xóa các liên kết Role của tài khoản
    $stmt = $pdo->prepare(
        "DELETE FROM user_roles
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);

    // Xóa tài khoản
    $stmt = $pdo->prepare(
        "DELETE FROM users
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);

    // Ghi nhật ký hoạt động
    writeAuditLog(
        $_SESSION['user_id'],
        'DELETE_USER',
        'User deleted: ' . $username
    );

    $pdo->commit();

    // Quay về trang quản lý người dùng
    header('Location: users.php');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit('There was an error while deleting the user.');
}
