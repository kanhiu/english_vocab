<?php

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'VocabUp';
$user = current_user();

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="<?= BASE_URL ?>/assets/style.css"
        rel="stylesheet">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">

        <a class="navbar-brand fw-bold"
           href="<?= BASE_URL ?>/index.php">
            VocabUp
        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbar">

            <?php if ($user): ?>

                <div class="navbar-nav me-auto">

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/index.php">
                        Trang chủ
                    </a>

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/lists.php">
                        Danh sách từ
                    </a>

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/test_setup.php">
                        Kiểm tra
                    </a>

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/history.php">
                        Lịch sử
                    </a>

                </div>

                <div class="navbar-nav align-items-lg-center">

                    <span class="navbar-text me-3">
                        Xin chào, <?= e($user['name']) ?>
                    </span>

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/logout.php">
                        Đăng xuất
                    </a>

                </div>

            <?php else: ?>

                <div class="navbar-nav ms-auto">

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/login.php">
                        Đăng nhập
                    </a>

                    <a class="nav-link"
                       href="<?= BASE_URL ?>/register.php">
                        Đăng ký
                    </a>

                </div>

            <?php endif; ?>

        </div>
    </div>
</nav>

<main class="container py-4">

<?php if (!empty($_SESSION['flash'])): ?>

    <?php foreach ($_SESSION['flash'] as $type => $message): ?>

        <?php
        $allowedTypes = ['success', 'danger', 'warning', 'info'];

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'info';
        }
        ?>

        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show"
             role="alert">

            <?= e($message) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    <?php endforeach; ?>

    <?php unset($_SESSION['flash']); ?>

<?php endif; ?>