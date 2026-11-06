
<?php

require_once __DIR__ . '/includes/functions.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra CSRF
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Kiểm tra dữ liệu
    if ($name === '' || $email === '' ||
        $password === '' || $confirm_password === '') {

        $error = 'Vui lòng nhập đầy đủ thông tin.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Email không hợp lệ.';

    } elseif (strlen($password) < 8) {

        $error = 'Mật khẩu phải có ít nhất 8 ký tự.';

    } elseif ($password !== $confirm_password) {

        $error = 'Mật khẩu xác nhận không khớp.';

    } else {

        // Kiểm tra email đã tồn tại
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $error = 'Email này đã được đăng ký.';

        } else {

            // Mã hóa mật khẩu
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Thêm tài khoản
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashed_password
            ]);

            // Tự động đăng nhập sau khi đăng ký
            session_regenerate_id(true);

            $_SESSION['user_id'] = $conn->lastInsertId();

            // Tạo CSRF Token mới
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            flash('success', 'Đăng ký tài khoản thành công!');

            redirect('index.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h3 class="text-center mb-4">
                        Đăng ký tài khoản
                    </h3>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <div class="mb-3">
                            <label class="form-label">
                                Họ và tên
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="<?= e($name) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= e($email) ?>"
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
                                minlength="8"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Xác nhận mật khẩu
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="8"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Đăng ký
                        </button>

                    </form>

                    <p class="text-center mt-3 mb-0">
                        Đã có tài khoản?
                        <a href="<?= BASE_URL ?>/login.php">
                            Đăng nhập
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
