<?php

require_once 'includes/functions.php';

require_login();

$user_id = current_user()['id'];

$stmt = $conn->prepare(
    "SELECT
        h.*,
        l.name AS list_name
     FROM test_history h
     LEFT JOIN vocabulary_lists l
     ON h.list_id = l.id
     WHERE h.user_id = ?
     ORDER BY h.created_at DESC"
);

$stmt->execute([$user_id]);

$histories = $stmt->fetchAll();

$pageTitle = 'Lịch sử kiểm tra';

require 'includes/header.php';

?>

<h2 class="mb-4">Lịch sử kiểm tra</h2>

<div class="card p-3">

<div class="table-responsive">

<table class="table align-middle">

<thead>

<tr>

<th>Thời gian</th>

<th>Loại kiểm tra</th>

<th>Tổng câu</th>

<th>Đúng</th>

<th>Sai</th>

<th>Thao tác</th>

</tr>

</thead>

<tbody>

<?php foreach ($histories as $item): ?>

<tr>

<td>
    <?= e($item['created_at']) ?>
</td>

<td>

<?php

if ($item['mode'] === 'all') {

    echo 'Tất cả danh sách';

} elseif ($item['mode'] === 'wrong') {

    echo 'Ôn tập câu sai';

} else {

    echo e($item['list_name'] ?? 'Danh sách đã xóa');

}

?>

</td>

<td>
    <?= $item['total_questions'] ?>
</td>

<td class="text-success">
    <?= $item['correct_count'] ?>
</td>

<td class="text-danger">
    <?= $item['wrong_count'] ?>
</td>

<td>

<?php if ($item['wrong_count'] > 0): ?>

<a href="retry_wrong.php?test_id=<?= $item['id'] ?>"
   class="btn btn-sm btn-outline-primary">

    Làm lại câu sai

</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php if (!$histories): ?>

<p class="text-secondary">
    Bạn chưa có lịch sử kiểm tra.
</p>

<?php endif; ?>

</div>

<?php require 'includes/footer.php'; ?>