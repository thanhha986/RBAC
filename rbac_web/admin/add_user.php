
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('create_users');

$error = '';
$canManageRoles = hasPermission('manage_roles');

$roles = [];

if ($canManageRoles) {
    $roles = $pdo->query("
        SELECT role_id, role_name
        FROM roles
        ORDER BY role_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$username = '';
$fullName = '';
$email = '';
$selectedRoleId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $selectedRoleId = filter_var(
        $_POST['role_id'] ?? null,
        FILTER_VALIDATE_INT
    ) ?: 0;

    if ($username === '') {
        $error = 'Please enter a username.';
    } elseif (strlen($password) < 12) {
        $error = 'Password must be at least 12 characters long.';
    } elseif ($fullName === '') {
        $error = 'Please enter your full name.';
    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = 'Invalid email address.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, password_hash, full_name, email)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $fullName,
                $email !== '' ? $email : null
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            if ($canManageRoles) {
                $validRoleIds = array_map(
                    'intval',
                    array_column($roles, 'role_id')
                );

                if (!in_array(
                    $selectedRoleId,
                    $validRoleIds,
                    true
                )) {
                    throw new Exception('Invalid role.');
                }

                $roleId = $selectedRoleId;
            } else {
                // HR không có quyền manage_roles:
                // tài khoản mới mặc định là Employee.
                $stmt = $pdo->prepare("
                    SELECT role_id
                    FROM roles
                    WHERE role_name = 'Employee'
                    LIMIT 1
                ");
                $stmt->execute();
                $roleId = $stmt->fetchColumn();

                if (!$roleId) {
                    throw new Exception(
                        'Employee role not found.'
                    );
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$newUserId, $roleId]);

            $pdo->commit();

            try {
                writeAuditLog(
                    $_SESSION['user_id'],
                    'CREATE_USER',
                    'Đã tạo tài khoản: ' . $username
                );
            } catch (Throwable $auditError) {
                error_log($auditError->getMessage());
            }

            header('Location: users.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'Unable to add the account. Please check the username, email, and role.';
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

    <title>Thêm người dùng - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=36">
</head>

<body class="add-user-page">

<main class="add-user-container">

    <div class="add-user-header">
        <div>
            <h1>Add User</h1>
            
        </div>
    </div>

    <?php if ($error): ?>
        <div class="add-user-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="add-user-form">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <!-- USERNAME -->
        <div class="add-form-group">
            <label for="username">
                Username <span>*</span>
            </label>

            <div class="add-input-box">
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($username) ?>"
                    placeholder="Enter username..."
                    autocomplete="username"
                    required
                >
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="add-form-group">
            <label for="password">
                Password <span>*</span>
            </label>

            <div class="add-input-box">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password..."
                    minlength="12"
                    autocomplete="new-password"
                    required
                >
            </div>
        </div>

        <!-- FULL NAME -->
        <div class="add-form-group">
            <label for="full_name">
                Full Name <span>*</span>
            </label>

            <div class="add-input-box">
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= e($fullName) ?>"
                    placeholder="Enter full name..."
                    required
                >
            </div>
        </div>

        <!-- EMAIL -->
        <div class="add-form-group">
            <label for="email">Email</label>

            <div class="add-input-box">
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="Enter email (optional)..."
                    autocomplete="email"
                >
            </div>
        </div>

        <!-- ROLE -->
        <div class="add-form-group">
            <label for="role_id">Role</label>

            <div class="add-input-box">

                <?php if ($canManageRoles): ?>

                    <select
                        id="role_id"
                        name="role_id"
                        required
                    >
                        <option value="">
                            -- Select Role --
                        </option>

                        <?php foreach ($roles as $role): ?>
                            <option
                                value="<?= (int)$role['role_id'] ?>"
                                <?= $selectedRoleId === (int)$role['role_id']
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
                        value="Employee"
                        readonly
                    >

                <?php endif; ?>

            </div>
        </div>

        <!-- NÚT THÊM -->
        <div class="add-form-actions">
            <button
                type="submit"
                class="add-submit-btn"
            >
                Add Account
            </button>
        </div>

    </form>

    <div class="add-user-footer">
        <a href="users.php">Back to Users</a>
    </div>

</main>

</body>
</html>
