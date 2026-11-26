
<?php
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('manage_roles');

$sql = "SELECT a.log_id, u.username,
               a.action, a.description,
               a.ip_address, a.created_at
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        ORDER BY a.created_at DESC, a.log_id DESC
        LIMIT 100";

$logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nhật ký hoạt động - RBAC</title>
    <link rel="stylesheet" href="/rbac_web/assets/css/style.css?v=41">
</head>

<body class="audit-page">

<main class="audit-container">

    <div class="audit-header">
        <div>
            <h1>Audit Logs</h1>
            
        </div>
    </div>

    <div class="audit-toolbar">
        <input
            type="search"
            id="auditSearch"
            placeholder="Search by username, action, description..."
        >

        <select id="auditFilter">
            <option value="">All Actions</option>
            <option value="LOGIN">LOGIN</option>
            <option value="LOGOUT">LOGOUT</option>
            <option value="CREATE_USER">CREATE_USER</option>
            <option value="UPDATE_USER">UPDATE_USER</option>
            <option value="DELETE_USER">DELETE_USER</option>
            <option value="CHANGE_ROLE">CHANGE_ROLE</option>
        </select>
    </div>

    <div class="audit-table-wrapper">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                        $action = $log['action'] ?? '';
                        $actionClass = 'audit-default';

                        if ($action === 'LOGIN') {
                            $actionClass = 'audit-login';
                        } elseif ($action === 'LOGOUT') {
                            $actionClass = 'audit-logout';
                        } elseif ($action === 'CREATE_USER') {
                            $actionClass = 'audit-create';
                        } elseif ($action === 'UPDATE_USER') {
                            $actionClass = 'audit-update';
                        } elseif ($action === 'DELETE_USER') {
                            $actionClass = 'audit-delete';
                        } elseif ($action === 'CHANGE_ROLE') {
                            $actionClass = 'audit-role';
                        }
                    ?>

                    <tr class="audit-data-row"
                        data-action="<?= e($action) ?>"
                        data-search="<?= e(
                            strtolower(
                                ($log['username'] ?? '') . ' ' .
                                $action . ' ' .
                                ($log['description'] ?? '') . ' ' .
                                ($log['ip_address'] ?? '')
                            )
                        ) ?>">

                        <td><?= (int)$log['log_id'] ?></td>

                        <td>
                            <strong><?= e($log['username'] ?? '—') ?></strong>
                        </td>

                        <td>
                            <span class="audit-badge <?= e($actionClass) ?>">
                                <?= e($action) ?>
                            </span>
                        </td>

                        <td><?= e($log['description'] ?? '—') ?></td>

                        <td><?= e($log['ip_address'] ?? '—') ?></td>

                        <td class="audit-time">
                            <?= e($log['created_at'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p id="auditEmpty" class="audit-empty" hidden>
            No matching activity logs found.
        </p>
    </div>

    <div class="audit-footer">
        <span id="auditCount"></span>

        <div class="audit-pagination">
            <button type="button" id="auditPrev">Previous</button>
            <span id="auditPageNumber">1</span>
            <button type="button" id="auditNext">Next</button>
        </div>
    </div>

    <div class="audit-bottom">
        <a href="../dashboard.php">Back to Dashboard</a>
    </div>

</main>

<script>
const auditSearch = document.getElementById('auditSearch');
const auditFilter = document.getElementById('auditFilter');
const auditRows = Array.from(document.querySelectorAll('.audit-data-row'));

const auditCount = document.getElementById('auditCount');
const auditEmpty = document.getElementById('auditEmpty');
const auditPageNumber = document.getElementById('auditPageNumber');
const auditPrev = document.getElementById('auditPrev');
const auditNext = document.getElementById('auditNext');

let auditCurrentPage = 1;
const auditPageSize = 10;

function renderAuditLogs() {
    const keyword = auditSearch.value.toLocaleLowerCase('vi').trim();
    const action = auditFilter.value;

    const filtered = auditRows.filter(row =>
        row.dataset.search.includes(keyword) &&
        (!action || row.dataset.action === action)
    );

    const totalPages = Math.max(1, Math.ceil(filtered.length / auditPageSize));
    auditCurrentPage = Math.min(auditCurrentPage, totalPages);

    auditRows.forEach(row => row.hidden = true);

    const start = (auditCurrentPage - 1) * auditPageSize;
    filtered.slice(start, start + auditPageSize).forEach(row => {
        row.hidden = false;
    });

    auditEmpty.hidden = filtered.length !== 0;

    const from = filtered.length ? start + 1 : 0;
    const to = Math.min(start + auditPageSize, filtered.length);

    
auditCount.textContent =
    `Showing ${from} - ${to} of ${filtered.length} logs`;

    auditPageNumber.textContent = auditCurrentPage;
    auditPrev.disabled = auditCurrentPage === 1;
    auditNext.disabled = auditCurrentPage === totalPages;
}

auditSearch.addEventListener('input', () => {
    auditCurrentPage = 1;
    renderAuditLogs();
});

auditFilter.addEventListener('change', () => {
    auditCurrentPage = 1;
    renderAuditLogs();
});

auditPrev.addEventListener('click', () => {
    if (auditCurrentPage > 1) {
        auditCurrentPage--;
        renderAuditLogs();
    }
});

auditNext.addEventListener('click', () => {
    auditCurrentPage++;
    renderAuditLogs();
});

renderAuditLogs();
</script>

</body>
</html>
