
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('edit_users');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Invalid ID.');
}

// Lấy thông tin người dùng
$stmt = $pdo->prepare("
    SELECT user_id, username, full_name, email
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

$canManageRoles = hasPermission('manage_roles');

// Lấy danh sách Role
$roles = $pdo->query("
    SELECT role_id, role_name
    FROM roles
    ORDER BY role_id
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy Role hiện tại
$stmt = $pdo->prepare("
    SELECT role_id
    FROM user_roles
    WHERE user_id = ?
");
$stmt->execute([$id]);

$currentRoleIds = array_map(
    'intval',
    $stmt->fetchAll(PDO::FETCH_COLUMN)
);

// Lấy tên Role hiện tại
$currentRoleNames = [];

foreach ($roles as $role) {
    if (in_array(
        (int)$role['role_id'],
        $currentRoleIds,
        true
    )) {
        $currentRoleNames[] = $role['role_name'];
    }
}

$error = '';

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Giữ dữ liệu đã nhập nếu có lỗi
    $user['full_name'] = $fullName;
    $user['email'] = $email;

    if (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = 'Invalid email address.';
    } else {

        try {
            $roleId = null;

            // Kiểm tra Role trước khi cập nhật
            if ($canManageRoles) {

                $roleId = filter_var(
                    $_POST['role_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $validRoleIds = array_map(
                    'intval',
                    array_column($roles, 'role_id')
                );

                if (!in_array($roleId, $validRoleIds, true)) {
                    throw new Exception('Invalid role.');
                }

                // Không cho đổi Role của chính mình
                if ((int)$id === (int)$_SESSION['user_id']) {
                    if (
                        count($currentRoleIds) !== 1 ||
                        $currentRoleIds[0] !== $roleId
                    ) {
                        throw new Exception(
                            'Unable to change your own role.'
                        );
                    }
                }
            }

            $pdo->beginTransaction();

            // Cập nhật thông tin
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $fullName !== '' ? $fullName : null,
                $email !== '' ? $email : null,
                $id
            ]);

            // Chỉ người có quyền manage_roles
            // mới được thay đổi Role
            $roleChanged = false;

            if (
                $canManageRoles &&
                (int)$id !== (int)$_SESSION['user_id']
            ) {

                if ($currentRoleIds !== [$roleId]) {

                    $stmt = $pdo->prepare("
                        DELETE FROM user_roles
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$id]);

                    $stmt = $pdo->prepare("
                        INSERT INTO user_roles (user_id, role_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$id, $roleId]);

                    $roleChanged = true;
                }
            }

            $pdo->commit();

            // Ghi Audit Logs
            writeAuditLog(
                $_SESSION['user_id'],
                'UPDATE_USER',
                'Updated account information: ' .
                $user['username']
            );

            if ($roleChanged) {
                writeAuditLog(
                    $_SESSION['user_id'],
                    'CHANGE_ROLE',
                    'Changed role for account: ' .
                    $user['username']
                );
            }

            header('Location: users.php');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'Unable to update information. Please try again.';
        }
    }
}

// Role đang được chọn
$selectedRoleId = $currentRoleIds[0] ?? 0;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $canManageRoles &&
    isset($_POST['role_id'])
) {
    $selectedRoleId = (int)$_POST['role_id'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sửa thông tin người dùng - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=35">
</head>

<body class="edit-user-page">

<main class="edit-user-container">

    <div class="edit-user-header">
        <h1>Edit User Information</h1>
       
    </div>

    <?php if ($error): ?>
        <div class="edit-user-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="edit-user-form">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <!-- USERNAME -->
        <div class="edit-form-group">
            <label for="username">Username</label>

            <div class="edit-input-box">
                <input
                    type="text"
                    id="username"
                    value="<?= e($user['username']) ?>"
                    readonly
                >
            </div>
        </div>

        <!-- FULL NAME -->
        <div class="edit-form-group">
            <label for="full_name">Full Name</label>

            <div class="edit-input-box">
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= e($user['full_name'] ?? '') ?>"
                    placeholder="Enter full name..."
                >
            </div>
        </div>

        <!-- EMAIL -->
        <div class="edit-form-group">
            <label for="email">Email</label>

            <div class="edit-input-box">
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($user['email'] ?? '') ?>"
                    placeholder="Enter email (optional)..."
                >
            </div>
        </div>

        <!-- ROLE -->
        <div class="edit-form-group">
            <label for="role_id">Role</label>

            <div class="edit-input-box">

                <?php if ($canManageRoles): ?>

                    <select
                        id="role_id"
                        name="role_id"
                        required
                    >
                        <?php foreach ($roles as $role): ?>
                            <option
                                value="<?= (int)$role['role_id'] ?>"
                                <?= (int)$role['role_id'] === $selectedRoleId
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php else: ?>

                    <input
                        type="text"
                        id="role_id"
                        value="<?= e(
                            implode(', ', $currentRoleNames)
                            ?: 'Not assigned'
                        ) ?>"
                        readonly
                    >

                <?php endif; ?>

            </div>
        </div>

        <!-- NÚT LƯU -->
        <div class="edit-form-actions">
            <button
                type="submit"
                class="edit-submit-btn"
            >
                Save Changes
            </button>
        </div>

    </form>

    <div class="edit-user-footer">
        <a href="users.php">Back to Users</a>
    </div>

</main>

</body>
</html>
