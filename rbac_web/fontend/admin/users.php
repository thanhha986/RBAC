
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('view_users');

// Lấy danh sách người dùng và vai trò
$stmt = $pdo->query("
    SELECT
        u.user_id,
        u.username,
        u.full_name,
        u.email,
        GROUP_CONCAT(
            DISTINCT r.role_name
            ORDER BY r.role_name
            SEPARATOR ', '
        ) AS roles
    FROM users u
    LEFT JOIN user_roles ur
        ON u.user_id = ur.user_id
    LEFT JOIN roles r
        ON ur.role_id = r.role_id
    GROUP BY
        u.user_id,
        u.username,
        u.full_name,
        u.email
    ORDER BY u.user_id ASC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$canCreate = hasPermission('create_users');
$canEdit = hasPermission('edit_users');
$canDelete = hasPermission('delete_users');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Quản lý người dùng - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=50">
</head>

<body class="users-page">

<main class="users-container">

    <!-- TIÊU ĐỀ -->
    <div class="users-header">

        <div class="users-heading">
            <h1>User Management</h1>
        </div>

        <?php if ($canCreate): ?>
            <a class="users-add-btn" href="add_user.php">
                Add User
            </a>
        <?php endif; ?>

    </div>

    <!-- TÌM KIẾM VÀ LỌC -->
    <div class="users-toolbar">

        <div class="users-search">
            <input
                type="search"
                id="userSearch"
                placeholder="Search by name, username, email..."
            >
        </div>

        <div class="users-filter">
            <select id="roleFilter">
                <option value="">All Roles</option>
                <option value="Admin">Admin</option>
                <option value="Manager">Manager</option>
                <option value="HR">HR</option>
                <option value="Employee">Employee</option>
            </select>
        </div>

    </div>

    <!-- BẢNG NGƯỜI DÙNG -->
    <div class="users-table-wrapper">

        <table class="users-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody id="usersTableBody">

                <?php foreach ($users as $user): ?>

                    <?php
                    $roles = $user['roles'] ?: 'Chưa phân quyền';

                    $roleClass = 'role-default';

                    if ($roles === 'Admin') {
                        $roleClass = 'role-admin';
                    } elseif ($roles === 'Manager') {
                        $roleClass = 'role-manager';
                    } elseif ($roles === 'HR') {
                        $roleClass = 'role-hr';
                    } elseif ($roles === 'Employee') {
                        $roleClass = 'role-employee';
                    }
                    ?>

                    <tr
                        class="user-data-row"
                        data-search="<?= e(
                            mb_strtolower(
                                $user['username'] . ' ' .
                                ($user['full_name'] ?? '') . ' ' .
                                ($user['email'] ?? ''),
                                'UTF-8'
                            )
                        ) ?>"
                        data-role="<?= e($roles) ?>"
                    >

                        <!-- ID -->
                        <td>
                            <?= (int)$user['user_id'] ?>
                        </td>

                        <!-- USERNAME -->
                        <td>
                            <strong>
                                <?= e($user['username']) ?>
                            </strong>
                        </td>

                        <!-- FULL NAME -->
                        <td>
                            <?= e($user['full_name'] ?: '—') ?>
                        </td>

                        <!-- EMAIL -->
                        <td>
                            <?= e($user['email'] ?: '—') ?>
                        </td>

                        <!-- ROLE -->
                        <td>
                            <span class="role-badge <?= e($roleClass) ?>">
                                <?= e($roles) ?>
                            </span>
                        </td>

                        <!-- THAO TÁC -->
                        <td>
                            <div class="users-actions">

                                <!-- NÚT SỬA -->
                                <?php if ($canEdit): ?>

                                    <a
                                        class="action-edit"
                                        href="edit_user.php?id=<?= (int)$user['user_id'] ?>"
                                    >
                                        Edit
                                    </a>

                                <?php endif; ?>

                                <!-- NÚT XÓA BẰNG POST -->
                                <?php if (
                                    $canDelete &&
                                    (int)$user['user_id'] !==
                                    (int)$_SESSION['user_id']
                                ): ?>

                                    <form
                                        method="POST"
                                        action="delete_user.php"
                                        class="users-delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this account? <?= e($user['username']) ?> không?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$user['user_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-delete"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <?php if (!$canEdit && !$canDelete): ?>
                                    <span class="no-actions">
                                        View Only
                                    </span>
                                <?php endif; ?>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <p id="usersEmpty" class="users-empty" hidden>
            No matching users found.
        </p>

    </div>

    <!-- PHÂN TRANG -->
    <div class="users-footer">

        <span id="usersCount"></span>

        <div class="users-pagination">

            <button type="button" id="prevPage">
                Previous
            </button>

            <span id="pageNumber">1</span>

            <button type="button" id="nextPage">
                Next
            </button>

        </div>

    </div>

    <!-- QUAY LẠI DASHBOARD -->
    <div class="users-bottom">
        <a href="../dashboard.php">
            Back to Dashboard
        </a>
    </div>

</main>

<script>
const searchInput = document.getElementById('userSearch');
const roleFilter = document.getElementById('roleFilter');

const rows = Array.from(
    document.querySelectorAll('.user-data-row')
);

const countLabel = document.getElementById('usersCount');
const emptyLabel = document.getElementById('usersEmpty');
const pageNumber = document.getElementById('pageNumber');
const prevPage = document.getElementById('prevPage');
const nextPage = document.getElementById('nextPage');

const pageSize = 10;
let currentPage = 1;

function renderUsers() {

    const keyword = searchInput.value
        .toLocaleLowerCase('vi')
        .trim();

    const role = roleFilter.value;

    const filtered = rows.filter(row => {

        const text = row.dataset.search;

        const roles = row.dataset.role
            .split(',')
            .map(r => r.trim());

        return text.includes(keyword) &&
               (!role || roles.includes(role));
    });

    const totalPages = Math.max(
        1,
        Math.ceil(filtered.length / pageSize)
    );

    currentPage = Math.min(currentPage, totalPages);

    // Ẩn tất cả các dòng
    rows.forEach(row => {
        row.hidden = true;
    });

    // Lấy dòng của trang hiện tại
    const start = (currentPage - 1) * pageSize;

    const visible = filtered.slice(
        start,
        start + pageSize
    );

    // Hiển thị các dòng phù hợp
    visible.forEach(row => {
        row.hidden = false;
    });

    // Thông báo khi không có kết quả
    emptyLabel.hidden = filtered.length !== 0;

    const from = filtered.length ? start + 1 : 0;

    const to = Math.min(
        start + pageSize,
        filtered.length
    );

    countLabel.textContent =
        `Showing ${from} - ${to} of ${filtered.length} users`;

    pageNumber.textContent = currentPage;

    prevPage.disabled = currentPage === 1;
    nextPage.disabled = currentPage === totalPages;
}

// Tìm kiếm
searchInput.addEventListener('input', () => {
    currentPage = 1;
    renderUsers();
});

// Lọc Role
roleFilter.addEventListener('change', () => {
    currentPage = 1;
    renderUsers();
});

// Trang trước
prevPage.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        renderUsers();
    }
});

// Trang sau
nextPage.addEventListener('click', () => {
    currentPage++;
    renderUsers();
});

// Hiển thị ban đầu
renderUsers();
</script>

</body>
</html>
