
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('manage_roles');

// Lấy Role ID từ URL
$roleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$roleId || $roleId <= 0) {
    http_response_code(400);
    exit('Invalid Role ID.');
}

// Lấy thông tin Role
$stmt = $pdo->prepare("
    SELECT role_id, role_name
    FROM roles
    WHERE role_id = ?
");
$stmt->execute([$roleId]);

$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    http_response_code(404);
    exit('Role not found.');
}

// Không cho phép chỉnh sửa quyền Admin
if ($role['role_name'] === 'Admin') {
    http_response_code(403);
    exit('You are not allowed to edit Admin permissions.');
}

// Lấy tất cả Permission trong database
$permissions = $pdo->query("
    SELECT permission_id, permission_name
    FROM permissions
    ORDER BY permission_id
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy Permission hiện tại của Role
$stmt = $pdo->prepare("
    SELECT permission_id
    FROM role_permissions
    WHERE role_id = ?
");
$stmt->execute([$roleId]);

$assigned = array_map(
    'intval',
    $stmt->fetchAll(PDO::FETCH_COLUMN)
);

$error = '';

// Mặc định tích những quyền đã được cấp
$selected = $assigned;

// Xử lý khi nhấn Lưu Permission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $selected = $_POST['permissions'] ?? [];

    if (!is_array($selected)) {
        $selected = [];
    }

    // Chuyển Permission ID thành số nguyên
    $selected = array_values(
        array_unique(
            array_map('intval', $selected)
        )
    );

    // Danh sách Permission ID hợp lệ
    $validIds = array_map(
        'intval',
        array_column($permissions, 'permission_id')
    );

    if (array_diff($selected, $validIds)) {

        $error = 'There are invalid permissions.';

    } else {

        try {

            // Kiểm tra xem quyền có thay đổi không
            $changed = (
                !empty(array_diff($selected, $assigned)) ||
                !empty(array_diff($assigned, $selected))
            );

            if ($changed) {

                $pdo->beginTransaction();

                // Xóa quyền cũ của Role
                $stmt = $pdo->prepare("
                    DELETE FROM role_permissions
                    WHERE role_id = ?
                ");
                $stmt->execute([$roleId]);

                // Thêm các quyền mới
                $stmt = $pdo->prepare("
                    INSERT INTO role_permissions
                    (role_id, permission_id)
                    VALUES (?, ?)
                ");

                foreach ($selected as $permissionId) {
                    $stmt->execute([
                        $roleId,
                        $permissionId
                    ]);
                }

                $pdo->commit();

                // Ghi nhật ký thay đổi quyền
                try {
                    writeAuditLog(
                        $_SESSION['user_id'],
                        'UPDATE_PERMISSIONS',
                        'Đã cập nhật quyền của Role: ' .
                        $role['role_name']
                    );
                } catch (Throwable $auditError) {
                    error_log($auditError->getMessage());
                }
            }

            // Quay về trang Role Management
            header('Location: roles.php');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'Unable to update permissions. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Phân quyền Role - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=43">
</head>

<body class="edit-role-page">

<main class="edit-role-container">

    <!-- TIÊU ĐỀ -->
    <div class="edit-role-header">

        <h1>
            Assign Permissions to Role:
            <?= e($role['role_name']) ?>
        </h1>

        

    </div>

    <!-- THÔNG BÁO LỖI -->
    <?php if ($error): ?>

        <div class="edit-role-error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- FORM PHÂN QUYỀN -->
    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <div class="edit-role-permissions">

            <?php foreach ($permissions as $permission): ?>

                <label class="edit-role-permission">

                    <input
                        type="checkbox"
                        name="permissions[]"
                        value="<?= (int)$permission['permission_id'] ?>"
                        <?= in_array(
                            (int)$permission['permission_id'],
                            $selected,
                            true
                        ) ? 'checked' : '' ?>
                    >

                    <span>
                        <?= e($permission['permission_name']) ?>
                    </span>

                </label>

            <?php endforeach; ?>

        </div>

        <!-- NÚT LƯU -->
        <div class="edit-role-actions">

            <button
                type="submit"
                class="edit-role-save"
            >
                Save Permissions
            </button>

        </div>

    </form>

    <!-- QUAY LẠI -->
    <div class="edit-role-bottom">
        <a href="roles.php">
            Back to Role Management
        </a>
    </div>

</main>

</body>
</html>
