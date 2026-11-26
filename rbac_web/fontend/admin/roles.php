
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('manage_roles');

// Lấy danh sách Role, số người dùng và quyền
$sql = "
    SELECT
        r.role_id,
        r.role_name,
        COUNT(DISTINCT ur.user_id) AS total_users,
        GROUP_CONCAT(
            DISTINCT p.permission_name
            ORDER BY p.permission_name
            SEPARATOR ', '
        ) AS permissions
    FROM roles r
    LEFT JOIN user_roles ur
        ON r.role_id = ur.role_id
    LEFT JOIN role_permissions rp
        ON r.role_id = rp.role_id
    LEFT JOIN permissions p
        ON rp.permission_id = p.permission_id
    GROUP BY r.role_id, r.role_name
    ORDER BY r.role_id
";

$roles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Quản lý vai trò - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=42">
</head>

<body class="roles-page">

<main class="roles-container">

    <!-- TIÊU ĐỀ -->
    <div class="roles-header">
        <h1>Role Management</h1>
        
    </div>

    <!-- BẢNG ROLE -->
    <div class="roles-table-wrapper">

        <table class="roles-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Role</th>
                    <th>Number of Users</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($roles as $role): ?>

                    <?php
                    $roleName = $role['role_name'];

                    // Màu sắc cho từng Role
                    $roleClass = 'role-name-employee';

                    if ($roleName === 'Admin') {
                        $roleClass = 'role-name-admin';
                    } elseif ($roleName === 'Manager') {
                        $roleClass = 'role-name-manager';
                    } elseif ($roleName === 'HR') {
                        $roleClass = 'role-name-hr';
                    }

                    // Tách danh sách quyền
                    $permissions = [];

                    if (!empty($role['permissions'])) {
                        $permissions = explode(
                            ', ',
                            $role['permissions']
                        );
                    }
                    ?>

                    <tr>

                        <!-- ID -->
                        <td>
                            <?= (int)$role['role_id'] ?>
                        </td>

                        <!-- ROLE -->
                        <td>
                            <span class="role-name-badge <?= e($roleClass) ?>">
                                <?= e($roleName) ?>
                            </span>
                        </td>

                        <!-- SỐ NGƯỜI DÙNG -->
                        <td>
                            <span class="role-count">
                                <?= (int)$role['total_users'] ?>
                            </span>
                        </td>

                        <!-- PERMISSIONS -->
                        <td>
                            <div class="role-permissions">

                                <?php if (!empty($permissions)): ?>

                                    <?php foreach ($permissions as $permission): ?>
                                        <span class="permission-tag">
                                            <?= e($permission) ?>
                                        </span>
                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <span class="role-locked">
                                        No Permissions Assigned
                                    </span>

                                <?php endif; ?>

                            </div>
                        </td>

                        <!-- THAO TÁC -->
                        <td>
                            <?php if ($roleName !== 'Admin'): ?>

                                <a
                                    class="role-edit-btn"
                                    href="edit_role.php?id=<?= (int)$role['role_id'] ?>"
                                >
                                    Edit Permissions
                                </a>

                            <?php else: ?>

                                <span class="role-locked">
                                    Cannot Edit
                                </span>

                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <!-- QUAY LẠI -->
    <div class="roles-bottom">
        <a href="../dashboard.php">
            Back to Dashboard
        </a>
    </div>

</main>

</body>
</html>
