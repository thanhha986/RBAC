
<?php
require_once __DIR__ . '/includes/rbac.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT password_hash FROM users WHERE user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($new) < 12) {
        $error = 'Mật khẩu mới phải có ít nhất 12 ký tự.';
    } elseif ($new !== $confirm) {
        $error = 'Xác nhận mật khẩu không khớp.';
    } elseif (password_verify($new, $hash)) {
        $error = 'Mật khẩu mới phải khác mật khẩu cũ.';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "UPDATE users SET password_hash = ?
             WHERE user_id = ?"
        );
        $stmt->execute([
            $newHash,
            $_SESSION['user_id']
        ]);

        writeAuditLog(
            $_SESSION['user_id'],
            'CHANGE_PASSWORD',
            'User changed their password.'
        );

        $success = 'Password changed successfully!';
    }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Đổi mật khẩu - RBAC</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=70">
</head>

<body class="change-password-page">

<main class="change-password-container">

    <div class="change-password-header">
        <h1>Change Password</h1>
        
        
    </div>

    <?php if (!empty($error)): ?>
        <div class="change-password-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="change-password-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="change-password-form">

        <input type="hidden"
               name="csrf_token"
               value="<?= e(csrfToken()) ?>">

        <div class="change-password-group">
            <label for="current_password">
                Current Password
            </label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                placeholder="Enter current password..."
                autocomplete="current-password"
                required
            >
        </div>

        <div class="change-password-group">
            <label for="new_password">
                New Password
            </label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Enter new password (at least 8 characters)..."
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="change-password-group">
            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm new password..."
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="change-password-submit">
            Change Password
        </button>

    </form>

    <div class="change-password-footer">
        <a href="dashboard.php">
            Back to Dashboard
        </a>
    </div>

</main>

</body>
</html>
