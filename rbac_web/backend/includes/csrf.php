
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf() {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('403 - CSRF token không hợp lệ.');
    }
}
