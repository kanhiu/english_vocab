<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Chuyển hướng
function redirect($url) {
    header('Location: ' . BASE_URL . '/' . ltrim($url, '/'));
    exit;
}

// Kiểm tra đăng nhập
function require_login() {
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Lấy người dùng hiện tại
function current_user() {
    global $conn;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = false;

    if ($user === false) {
        $stmt = $conn->prepare(
            "SELECT id, name, email
             FROM users
             WHERE id = ?"
        );

        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }

    return $user;
}

// Chống XSS
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Tạo CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (!empty($_SESSION['csrf'])) {
            $_SESSION['csrf_token'] = $_SESSION['csrf'];
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    // Đồng bộ tên cũ
    $_SESSION['csrf'] = $_SESSION['csrf_token'];

    return $_SESSION['csrf_token'];
}

// Kiểm tra CSRF token
function verify_csrf() {
    $token = $_POST['csrf_token']
        ?? $_POST['csrf']
        ?? '';

    $sessionToken = $_SESSION['csrf_token']
        ?? $_SESSION['csrf']
        ?? '';

    if (
        !is_string($token) ||
        !is_string($sessionToken) ||
        $token === '' ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $token)
    ) {
        http_response_code(403);
        die('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
}

// Chuẩn hóa câu trả lời
function normalize_answer($answer) {
    $answer = mb_strtolower(trim($answer), 'UTF-8');
    $answer = preg_replace('/\s+/', ' ', $answer);

    return $answer;
}

// Kiểm tra danh sách thuộc người dùng
function user_owns_list($list_id) {
    global $conn;

    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $conn->prepare(
        "SELECT id
         FROM vocabulary_lists
         WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([
        $list_id,
        $_SESSION['user_id']
    ]);

    return $stmt->fetch() !== false;
}

// Lưu thông báo
function flash($type, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return;
    }

    if (isset($_SESSION['flash'][$type])) {
        $value = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);

        return $value;
    }

    return null;
}