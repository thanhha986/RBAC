
<?php
require_once __DIR__ . '/includes/rbac.php';
require_once __DIR__ . '/includes/functions.php';

$user = currentUser();

if (!$user) {
    header('Location: login.php');
    exit;
}

$username = $user['username'] ?? 'User';
$userId = (int)$user['user_id'];

// Check permissions
$canViewUsers = hasPermission('view_users');
$canManageRoles = hasPermission('manage_roles');
$canViewProfile = hasPermission('view_profile');

// Get current user's roles
$stmt = $pdo->prepare("
    SELECT r.role_name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ?
    ORDER BY r.role_id
");
$stmt->execute([$userId]);
$userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

$isAdmin = in_array('Admin', $userRoles, true);

// Get current user's permissions
$stmt = $pdo->prepare("
    SELECT DISTINCT p.permission_name
    FROM user_roles ur
    JOIN role_permissions rp
        ON ur.role_id = rp.role_id
    JOIN permissions p
        ON rp.permission_id = p.permission_id
    WHERE ur.user_id = ?
    ORDER BY p.permission_id
");
$stmt->execute([$userId]);
$userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Permission labels
$permissionLabels = [
    'view_profile' => 'View Profile',
    'view_users'   => 'View Users',
    'create_users' => 'Create User',
    'edit_users'   => 'Edit User',
    'delete_users' => 'Delete User',
    'manage_roles' => 'Manage Roles and Permissions'
];

// Admin statistics
$adminStats = [];
$roleStats = [];

if ($isAdmin && $canManageRoles) {

    $adminStats['users'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();

    $adminStats['roles'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM roles"
    )->fetchColumn();

    $adminStats['permissions'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM permissions"
    )->fetchColumn();

    $adminStats['logs'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM audit_logs"
    )->fetchColumn();

    $roleStats = $pdo->query("
        SELECT
            r.role_name,
            COUNT(DISTINCT ur.user_id) AS total_users
        FROM roles r
        LEFT JOIN user_roles ur
            ON r.role_id = ur.role_id
        GROUP BY r.role_id, r.role_name
        ORDER BY r.role_id
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Manager / HR statistics
$userStats = [];

if ($canViewUsers && !$isAdmin) {

    $userStats['users'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();

    $userStats['roles'] = (int)$pdo->query(
        "SELECT COUNT(DISTINCT role_id) FROM user_roles"
    )->fetchColumn();
}

$roleColors = [
    'Admin'    => 'role-blue',
    'Manager'  => 'role-green',
    'HR'       => 'role-purple',
    'Employee' => 'role-orange'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>RBAC Dashboard</title>

    <!-- Main CSS -->
    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=112">

    <!-- Gemini AI CSS -->
    <link rel="stylesheet"
          href="/rbac_web/assets/css/gemini_chat.css?v=2">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="rbac-page">

<div class="rbac-layout">

    <!-- SIDEBAR -->
    <aside class="rbac-sidebar">

        <div class="rbac-logo">
            <div>
                <strong>User Accounts &amp; RBAC</strong>
            </div>
        </div>

        <nav class="rbac-nav">

            <a class="active" href="dashboard.php">
                Dashboard
            </a>

            <?php if ($canViewUsers): ?>
                <a href="admin/users.php">
                    User Management
                </a>
            <?php endif; ?>

            <?php if ($canManageRoles): ?>

                <a href="admin/audit_logs.php">
                    Activity Logs
                </a>

                <a href="admin/roles.php">
                    Role Management
                </a>

                <a href="admin/permissions.php">
                    Permission Management
                </a>

            <?php endif; ?>

            <?php if ($canViewProfile): ?>
                <a href="employee/profile.php">
                    My Profile
                </a>
            <?php endif; ?>

            <a href="change_password.php">
                Change Password
            </a>

        </nav>

        <a class="rbac-logout" href="logout.php">
            Logout
        </a>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="rbac-main">

        <div class="rbac-content">

            <!-- WELCOME -->
            <section class="rbac-welcome">

                <div>
                    <h1>
                        Welcome, <?= e($username) ?>!
                    </h1>

                    
                </div>

               

            </section>

            <!-- ADMIN DASHBOARD -->
            <?php if ($isAdmin && $canManageRoles): ?>

                <section class="admin-overview">

                    <div class="admin-overview-heading">
                        <h2>System Overview</h2>
                    </div>

                    <!-- STATISTICS -->
                    <div class="admin-stats-grid">

                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-blue">
                                <?= $adminStats['users'] ?>
                            </div>

                            <div class="admin-stat-label">
                                Total Users
                            </div>

                        </div>

                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-green">
                                <?= $adminStats['roles'] ?>
                            </div>

                            <div class="admin-stat-label">
                                Total Roles
                            </div>

                        </div>

                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-purple">
                                <?= $adminStats['permissions'] ?>
                            </div>

                            <div class="admin-stat-label">
                                Total Permissions
                            </div>

                        </div>

                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-orange">
                                <?= $adminStats['logs'] ?>
                            </div>

                            <div class="admin-stat-label">
                                Activity Logs
                            </div>

                        </div>

                    </div>

                    <!-- ROLE DISTRIBUTION -->
                    <div class="admin-role-section">

                        <h3>Role Distribution</h3>

                        <div class="admin-role-grid">

                            <?php foreach ($roleStats as $role): ?>

                                <?php
                                $roleClass = $roleColors[
                                    $role['role_name']
                                ] ?? 'role-blue';
                                ?>

                                <div class="admin-role-card <?= e($roleClass) ?>">

                                    <div class="admin-role-name">
                                        <?= e($role['role_name']) ?>
                                    </div>

                                    <div class="admin-role-number">
                                        <?= (int)$role['total_users'] ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <!-- ADMIN PERMISSIONS -->
                    <div class="dashboard-access-panel dashboard-permissions-card">

                        <h3>Your Permissions</h3>

                        <div class="dashboard-permission-list">

                            <?php foreach ($userPermissions as $permission): ?>

                                <span class="dashboard-permission-badge">
                                    <?= e(
                                        $permissionLabels[$permission]
                                        ?? $permission
                                    ) ?>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </section>

            <!-- MANAGER / HR DASHBOARD -->
            <?php elseif ($canViewUsers): ?>

                <section class="admin-overview">

                    <div class="admin-overview-heading">
                        <h2>User Overview</h2>
                    </div>

                    <div class="admin-stats-grid overview-two-columns">

                        <!-- TOTAL USERS -->
                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-blue">
                                <?= (int)$userStats['users'] ?>
                            </div>

                            <div class="admin-stat-label">
                                Total Users
                            </div>

                        </div>

                        <!-- YOUR ROLE -->
                        <div class="admin-stat-card">

                            <div class="admin-stat-number stat-green">
                                <?= e(
                                    implode(', ', $userRoles)
                                    ?: 'No Role'
                                ) ?>
                            </div>

                            <div class="admin-stat-label">
                                Your Role
                            </div>

                        </div>

                    </div>

                    <!-- MANAGER / HR PERMISSIONS -->
                    <div class="dashboard-access-panel">

                        <h3>Your Permissions</h3>

                        <div class="dashboard-permission-list">

                            <?php foreach ($userPermissions as $permission): ?>

                                <span class="dashboard-permission-badge">
                                    <?= e(
                                        $permissionLabels[$permission]
                                        ?? $permission
                                    ) ?>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </section>

            <!-- EMPLOYEE DASHBOARD -->
            <?php else: ?>

                <section class="admin-overview">

                    <div class="dashboard-access-panel">

                        <h2>System Overview</h2>

                        <!-- YOUR ROLE -->
                        <div class="dashboard-account-row">

                            <strong>Your Role</strong>

                            <span>
                                <?= e(
                                    implode(', ', $userRoles)
                                    ?: 'No Role'
                                ) ?>
                            </span>

                        </div>

                        <!-- YOUR PERMISSIONS -->
                        <div class="dashboard-account-row">

                            <strong>Your Permissions</strong>

                            <div class="dashboard-permission-list">

                                <?php foreach ($userPermissions as $permission): ?>

                                    <span class="dashboard-permission-badge">
                                        <?= e(
                                            $permissionLabels[$permission]
                                            ?? $permission
                                        ) ?>
                                    </span>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                </section>

            <?php endif; ?>

        </div>

    </main>

</div>

<!-- GEMINI AI CHATBOT -->
<?php if ($canViewProfile): ?>

    <?php require __DIR__ . '/includes/gemini_widget.php'; ?>

    <script src="/rbac_web/assets/js/gemini_chat.js?v=2"></script>

<?php endif; ?>

</body>
</html>
