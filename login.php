<?php

require_once 'includes/functions.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ? LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (
        $user &&
        !empty($user['password']) &&
        password_verify($password, $user['password'])
    ) {

        session_regenerate_id(true);

        // Đồng bộ với functions.php
        $_SESSION['user_id'] = (int)$user['id'];

        // Tạo CSRF token mới
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = $_SESSION['csrf_token'];

        redirect('index.php');

    } else {

        $error = 'Email hoặc mật khẩu không chính xác.';

    }
}

$pageTitle = 'Đăng nhập';

require 'includes/header.php';

?>

<div class="row justify-content-center">

    <div class="col-md-6 col-lg-5">

        <div class="card p-4">

            <h3 class="mb-4">Đăng nhập</h3>

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Mật khẩu
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >

                </div>

                <button class="btn btn-primary w-100">
                    Đăng nhập
                </button>

            </form>

            <p class="text-center mt-3">

                Chưa có tài khoản?

                <a href="register.php">
                    Đăng ký ngay
                </a>

            </p>

        </div>

    </div>

</div>

<?php require 'includes/footer.php'; ?>