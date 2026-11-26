
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /rbac_web/login.php');
        exit;
    }
}

function currentUser() {
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, username, full_name, email
         FROM users WHERE user_id = ?"
    );

    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
