<?php

require_once 'includes/functions.php';

require_login();

$test = $_SESSION['active_test'] ?? null;

if (!$test) {
    redirect('history.php');
}

$test_id = $test['id'];

$user_id = current_user()['id'];

/* Thống kê kết quả */

$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(is_correct = 1) AS correct_count,
        SUM(is_correct = 0) AS wrong_count
     FROM test_details
     WHERE test_id = ?"
);

$stmt->execute([$test_id]);

$result = $stmt->fetch();

$total = (int)$result['total'];

$correct = (int)$result['correct_count'];

$wrong = (int)$result['wrong_count'];

$percent = $total > 0
    ? round($correct / $total * 100)
    : 0;

/* Cập nhật lịch sử */

$stmt = $conn->prepare(
    "UPDATE test_history
     SET total_questions = ?,
         correct_count = ?,
         wrong_count = ?
     WHERE id = ? AND user_id = ?"
);

$stmt->execute([
    $total,
    $correct,
    $wrong,
    $test_id,
    $user_id
]);

/* Lấy chi tiết câu hỏi */

$stmt = $conn->prepare(
    "SELECT
        td.*,
        v.english,
        v.part_of_speech,
        v.pronunciation,
        v.meaning
     FROM test_details td
     JOIN vocabularies v
     ON td.vocabulary_id = v.id
     WHERE td.test_id = ?
     ORDER BY td.id"
);

$stmt->execute([$test_id]);

$details = $stmt->fetchAll();

unset($_SESSION['active_test']);

$pageTitle = 'Kết quả kiểm tra';

require 'includes/header.php';

?>

<div class="card p-4 mb-4">

<h2>Kết quả kiểm tra</h2>

<div class="row text-center my-4">

<div class="col-4">

<h2 class="text-success">
    <?= $correct ?>
</h2>

<p>Câu đúng</p>

</div>

<div class="col-4">

<h2 class="text-danger">
    <?= $wrong ?>
</h2>

<p>Câu sai</p>

</div>

<div class="col-4">

<h2>
    <?= $percent ?>%
</h2>

<p>Chính xác</p>

</div>

</div>

<div class="progress mb-4">

<div class="progress-bar bg-success"
     style="width: <?= $percent ?>%">

</div>

</div>

<div class="d-flex flex-wrap gap-2">

<a href="retry_wrong.php?test_id=<?= $test_id ?>"
   class="btn btn-primary">

    Làm lại <?= $wrong ?> câu sai

</a>

<a href="test_setup.php"
   class="btn btn-outline-primary">

    Kiểm tra mới

</a>

<a href="history.php"
   class="btn btn-outline-secondary">

    Lịch sử

</a>

</div>

</div>

<div class="card p-3">

<h5>Chi tiết bài kiểm tra</h5>

<div class="table-responsive">

<table class="table align-middle">

<thead>

<tr>

<th>Từ vựng</th>

<th>Đáp án của bạn</th>

<th>Đáp án đúng</th>

<th>Kết quả</th>

</tr>

</thead>

<tbody>

<?php foreach ($details as $item): ?>

<tr>

<td>

<strong>
    <?= e($item['english']) ?>
</strong>

<br>

<small class="text-secondary">

<?= e($item['part_of_speech']) ?>

·

<?= e($item['pronunciation']) ?>

</small>

</td>

<td>
    <?= e($item['user_answer']) ?>
</td>

<td>
    <?= e($item['correct_answer']) ?>
</td>

<td>

<?php if ($item['is_correct']): ?>

<span class="badge bg-success">
    Đúng
</span>

<?php else: ?>

<span class="badge bg-danger">
    Sai
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<?php require 'includes/footer.php'; ?>