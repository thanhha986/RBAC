<?php
require_once __DIR__ . '/config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 8) {
        $message = 'Mat khau phai co it nhat 8 ky tu!';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO users
                 (username, password_hash, full_name, email)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                'admin',
                password_hash($password, PASSWORD_DEFAULT),
                'System Administrator',
                'admin@example.test'
            ]);

            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO user_roles (user_id, role_id)
                 SELECT ?, role_id
                 FROM roles
                 WHERE role_name = 'Admin'"
            );

            $stmt->execute([$userId]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('Khong tim thay role Admin');
            }

            $pdo->commit();
            $message = 'Tao tai khoan Admin thanh cong!';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = 'Khong the tao tai khoan. Kiem tra username Admin da ton tai chua.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Create Admin</title>
</head>
<body>
    <h2>Tạo tài khoản Admin</h2>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <form method="POST">
        <p>Username: <strong>admin</strong></p>

        <label>Nhập mật khẩu mới:</label>
        <input type="password" name="password"
               minlength="8" required>

        <button type="submit">Tạo Admin</button>
    </form>
</body>
</html>