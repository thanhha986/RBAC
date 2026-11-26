
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('view_profile');

// Lấy thông tin người dùng đang đăng nhập
$currentUser = currentUser();

if (!$currentUser) {
    header('Location: ../logout.php');
    exit;
}

// Lấy thông tin mới nhất từ database
$stmt = $pdo->prepare("
    SELECT user_id, username, full_name, email
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$currentUser['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

// Lấy các Role của người dùng
$stmt = $pdo->prepare("
    SELECT r.role_name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ?
    ORDER BY r.role_name
");
$stmt->execute([$user['user_id']]);

$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Xác định màu của Role
$roleClasses = [
    'Admin'    => 'profile-role-admin',
    'Manager'  => 'profile-role-manager',
    'HR'       => 'profile-role-hr',
    'Employee' => 'profile-role-employee'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Thông tin cá nhân - RBAC</title>

    <link rel="stylesheet"
          href="../assets/css/style.css?v=50">
</head>

<body class="profile-page">

<main class="profile-container">

    <!-- TIÊU ĐỀ -->
    <div class="profile-header">
        <h1>Personal Information</h1>
        
    </div>

    <!-- THÔNG TIN NGƯỜI DÙNG -->
    <div class="profile-info">

        <!-- USER ID -->
        <div class="profile-field">
            <span class="profile-field-label">
                User ID
            </span>

            <span class="profile-field-value">
                <?= (int)$user['user_id'] ?>
            </span>
        </div>

        <!-- USERNAME -->
        <div class="profile-field">
            <span class="profile-field-label">
                Username
            </span>

            <span class="profile-field-value">
                <?= e($user['username']) ?>
            </span>
        </div>

        <!-- FULL NAME -->
        <div class="profile-field">
            <span class="profile-field-label">
                Full Name
            </span>

            <span class="profile-field-value">
                <?= e($user['full_name'] ?: 'Chưa cập nhật') ?>
            </span>
        </div>

        <!-- EMAIL -->
        <div class="profile-field">
            <span class="profile-field-label">
                Email
            </span>

            <span class="profile-field-value">
                <?= e($user['email'] ?: 'Not updated yet.') ?>
            </span>
        </div>

        <!-- ROLE -->
        <div class="profile-field">
            <span class="profile-field-label">
                Role
            </span>

            <div class="profile-role-list">

                <?php if (!empty($roles)): ?>

                    <?php foreach ($roles as $roleName): ?>

                        <span class="profile-role-badge <?= e(
                            $roleClasses[$roleName] ?? ''
                        ) ?>">
                            <?= e($roleName) ?>
                        </span>

                    <?php endforeach; ?>

                <?php else: ?>

                    <span class="profile-field-value">
                        Not assigned a role yet.
                    </span>

                <?php endif; ?>

            </div>
        </div>

    </div>

    <!-- QUAY LẠI -->
    <div class="profile-bottom">
        <a href="../dashboard.php">
            Back to Dashboard
        </a>
    </div>

</main>

</body>
</html>
