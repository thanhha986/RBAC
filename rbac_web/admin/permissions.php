
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('manage_roles');

// Lấy danh sách Permission và Role được cấp
$sql = "
    SELECT
        p.permission_id,
        p.permission_name,
        GROUP_CONCAT(
            DISTINCT r.role_name
            ORDER BY r.role_name
            SEPARATOR ', '
        ) AS roles
    FROM permissions p
    LEFT JOIN role_permissions rp
        ON p.permission_id = rp.permission_id
    LEFT JOIN roles r
        ON rp.role_id = r.role_id
    GROUP BY
        p.permission_id,
        p.permission_name
    ORDER BY p.permission_id
";

$permissions = $pdo->query($sql)
                   ->fetchAll(PDO::FETCH_ASSOC);

// Mô tả ý nghĩa của từng Permission
$descriptions = [
    'view_users'   => 'View User List',
    'create_users' => 'Create New User Account',
    'edit_users'   => 'Edit User Information',
    'delete_users' => 'Delete User Account',
    'manage_roles' => 'Manage Roles and Permissions',
    'view_profile' => 'View Personal Information'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Quản lý quyền - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=44">
</head>

<body class="permissions-page">

<main class="permissions-container">

    <!-- TIÊU ĐỀ -->
    <div class="permissions-header">
        <h1>Permission Management</h1>
        
    </div>

    <!-- BẢNG PERMISSION -->
    <div class="permissions-table-wrapper">

        <table class="permissions-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Permission</th>
                    <th>Permission Description</th>
                    <th>Assigned Roles</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($permissions as $permission): ?>

                    <?php
                    $permissionName = $permission['permission_name'];

                    $description = $descriptions[$permissionName]
                                   ?? 'No Description Available';

                    // Tách danh sách Role
                    $roleNames = [];

                    if (!empty($permission['roles'])) {
                        $roleNames = explode(
                            ', ',
                            $permission['roles']
                        );
                    }
                    ?>

                    <tr>

                        <!-- ID -->
                        <td>
                            <?= (int)$permission['permission_id'] ?>
                        </td>

                        <!-- PERMISSION -->
                        <td>
                            <span class="permission-name-badge">
                                <?= e($permissionName) ?>
                            </span>
                        </td>

                        <!-- MÔ TẢ -->
                        <td>
                            <?= e($description) ?>
                        </td>

                        <!-- ROLE ĐƯỢC CẤP QUYỀN -->
                        <td>
                            <div class="permission-role-list">

                                <?php if (!empty($roleNames)): ?>

                                    <?php foreach ($roleNames as $roleName): ?>

                                        <?php
                                        $roleClass = 'permission-role-default';

                                        if ($roleName === 'Admin') {
                                            $roleClass = 'permission-role-admin';
                                        } elseif ($roleName === 'Manager') {
                                            $roleClass = 'permission-role-manager';
                                        } elseif ($roleName === 'HR') {
                                            $roleClass = 'permission-role-hr';
                                        } elseif ($roleName === 'Employee') {
                                            $roleClass = 'permission-role-employee';
                                        }
                                        ?>

                                        <span class="permission-role-badge <?= e($roleClass) ?>">
                                            <?= e($roleName) ?>
                                        </span>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <span class="permission-role-default">
                                       Not Assigned
                                    </span>

                                <?php endif; ?>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <!-- QUAY LẠI -->
    <div class="permissions-bottom">
        <a href="../dashboard.php">
            Back to Dashboard
        </a>
    </div>

</main>

</body>
</html>
