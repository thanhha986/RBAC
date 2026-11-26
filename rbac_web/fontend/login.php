
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/audit.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT user_id, password_hash
         FROM users WHERE username = ?"
    );
    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify(
        $password,
        $user['password_hash']
    )) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        writeAuditLog(
    $user['user_id'],
    'LOGIN',
    'Login successful'
);

        header('Location: dashboard.php');
        exit;
    }

    
writeAuditLog(
    null,
    'LOGIN_FAILED',
    'Login failed'
);

$error = 'Invalid username or password!';

}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>LOGIN</title>

    <link rel="stylesheet"
          href="/rbac_web/assets/css/style.css?v=10">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="login-glass-page">

    <div class="glass-login">

        <div class="login-shield">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h1>LOGIN</h1>
        <p class="login-subtitle">Account Login</p>

        <?php if (!empty($error)): ?>
            <div class="login-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="glass-field">
               
                <input type="text"
                       name="username"
                       placeholder="Username"
                       autocomplete="username"
                       required>
            </div>

            <div class="glass-field">
                
                <input type="password"
                       name="password"
                       id="loginPassword"
                       placeholder="Password"
                       autocomplete="current-password"
                       required>

                <button type="button"
                        class="toggle-password"
                        aria-label="Hiện mật khẩu"
                        aria-pressed="false"
                        onclick="togglePassword(this)">
                    <i class="fa-regular fa-eye-slash"></i>
                </button>
            </div>

            

            <button type="submit" class="glass-submit">
                
                Login
            </button>

        </form>
    </div>

    <script>
        function togglePassword(button) {
            const input = document.getElementById('loginPassword');
            const visible = input.type === 'password';

            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute(
                'aria-label',
                visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
            );

            button.querySelector('i').className =
                visible
                    ? 'fa-regular fa-eye'
                    : 'fa-regular fa-eye-slash';
        }
    </script>

</body>
</html>
