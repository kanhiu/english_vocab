
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user_id = (int) current_user()['id'];

// Tạo list cá nhân hoặc xử lý yêu cầu xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO vocabulary_lists (user_id, name, description)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $name, $description]);

        flash('success', 'Tạo danh sách thành công.');
    } else {
        flash('error', 'Vui lòng nhập tên danh sách.');
    }

    redirect('lists.php');
}

// Lấy 23 list Complete TOEIC, sắp xếp theo số thứ tự
$stmt = $conn->query(
    "SELECT l.*, COUNT(v.id) AS total_words
     FROM vocabulary_lists l
     LEFT JOIN vocabularies v ON v.list_id = l.id
     WHERE l.is_public = 1
       AND l.name LIKE 'Complete TOEIC - List %'
     GROUP BY l.id
     ORDER BY CAST(SUBSTRING_INDEX(l.name, 'List ', -1) AS UNSIGNED) ASC"
);
$completeLists = $stmt->fetchAll();

// Lấy các list riêng của người dùng
$stmt = $conn->prepare(
    "SELECT l.*, COUNT(v.id) AS total_words
     FROM vocabulary_lists l
     LEFT JOIN vocabularies v ON v.list_id = l.id
     WHERE l.user_id = ?
     GROUP BY l.id
     ORDER BY l.created_at DESC"
);
$stmt->execute([$user_id]);
$myLists = $stmt->fetchAll();

$pageTitle = 'Danh sách từ vựng';
require __DIR__ . '/includes/header.php';

$success = flash('success');
$error = flash('error');
?>

<h2 class="mb-4">Danh sách từ vựng</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <h5>Tạo danh sách riêng</h5>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="name"
                       class="form-control"
                       placeholder="Tên danh sách" required>
            </div>

            <div class="col-md-5">
                <input type="text" name="description"
                       class="form-control"
                       placeholder="Mô tả">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    Tạo list
                </button>
            </div>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Complete TOEIC</h4>
    <span class="text-secondary">
        <?= count($completeLists) ?>/23 danh sách
    </span>
</div>

<div class="row g-3 mb-5">
    <?php foreach ($completeLists as $list): ?>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h5><?= e($list['name']) ?></h5>

                <p class="text-secondary">
                    <?= e($list['description']) ?>
                </p>

                <p class="mb-3">
                    Số từ: <strong><?= (int)$list['total_words'] ?></strong>
                </p>

                <div class="mt-auto">
                    <a href="test_setup.php?list_id=<?= (int)$list['id'] ?>"
                       class="btn btn-primary w-100">
                        Làm bài kiểm tra
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Danh sách của tôi</h4>

    <a href="import_study4.php" class="btn btn-success btn-sm">
        Import từ Study4
    </a>
</div>

<?php if (!$myLists): ?>
    <div class="alert alert-info">
        Bạn chưa có danh sách riêng. Hãy tạo list mới ở phía trên.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($myLists as $list): ?>
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5><?= e($list['name']) ?></h5>

                    <p class="text-secondary">
                        <?= e($list['description']) ?>
                    </p>

                    <p>
                        Số từ:
                        <strong><?= (int)$list['total_words'] ?></strong>
                    </p>

                    <div class="mt-auto d-flex gap-2">
                        <a href="list.php?id=<?= (int)$list['id'] ?>"
                           class="btn btn-primary flex-fill">
                            Quản lý
                        </a>

                        <form method="POST"
                              action="list.php?id=<?= (int)$list['id'] ?>"
                              onsubmit="return confirm('Bạn chắc chắn muốn xóa list này và toàn bộ từ vựng bên trong?')">

                            <input type="hidden" name="csrf_token"
                                   value="<?= e(csrf_token()) ?>">

                            <input type="hidden" name="action"
                                   value="delete_list">

                            <button type="submit"
                                    class="btn btn-outline-danger">
                                Xóa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
