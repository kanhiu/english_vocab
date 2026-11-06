<?php

require_once 'includes/functions.php';

require_login();

$list_id = (int)($_GET['id'] ?? 0);

if (!user_owns_list($list_id)) {
    exit('Danh sách không hợp lệ.');
}

$user_id = current_user()['id'];

$stmt = $conn->prepare(
    "SELECT * FROM vocabulary_lists
     WHERE id = ? AND user_id = ?"
);

$stmt->execute([$list_id, $user_id]);

$list = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        $english = trim($_POST['english']);
        $pos = trim($_POST['part_of_speech']);
        $pron = trim($_POST['pronunciation']);
        $meaning = trim($_POST['meaning']);

        $alternatives = trim($_POST['alternatives'] ?? '');

        if (
            $english !== '' &&
            $pos !== '' &&
            $pron !== '' &&
            $meaning !== ''
        ) {

            $stmt = $conn->prepare(
                "INSERT INTO vocabularies
                 (list_id,english,part_of_speech,
                  pronunciation,meaning)
                 VALUES(?,?,?,?,?)"
            );

            $stmt->execute([
                $list_id,
                $english,
                $pos,
                $pron,
                $meaning
            ]);

            $vocab_id = $conn->lastInsertId();

            $answers = preg_split(
                '/[,;\n]+/u',
                $alternatives,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            $insert = $conn->prepare(
                "INSERT INTO vocabulary_answers
                 (vocabulary_id,accepted_answer)
                 VALUES(?,?)"
            );

            foreach ($answers as $answer) {

                $answer = trim($answer);

                if ($answer !== '') {
                    $insert->execute([
                        $vocab_id,
                        $answer
                    ]);
                }

            }

            flash('Thêm từ vựng thành công.');

        } else {

            flash('Vui lòng nhập đủ 4 trường.', 'danger');

        }

    }

    if ($action === 'delete_word') {

        $stmt = $conn->prepare(
            "DELETE v FROM vocabularies v
             JOIN vocabulary_lists l
             ON v.list_id = l.id
             WHERE v.id = ?
             AND l.user_id = ?"
        );

        $stmt->execute([
            (int)$_POST['vocab_id'],
            $user_id
        ]);

        flash('Đã xóa từ vựng.');

    }

    if ($action === 'delete_list') {

        $stmt = $conn->prepare(
            "DELETE FROM vocabulary_lists
             WHERE id = ? AND user_id = ?"
        );

        $stmt->execute([
            $list_id,
            $user_id
        ]);

        redirect('lists.php');

    }

    redirect('list.php?id=' . $list_id);

}

$stmt = $conn->prepare(
    "SELECT * FROM vocabularies
     WHERE list_id = ?
     ORDER BY id DESC"
);

$stmt->execute([$list_id]);

$words = $stmt->fetchAll();

$pageTitle = $list['name'];

require 'includes/header.php';

?>

<div class="d-flex justify-content-between mb-4">

<h2><?= e($list['name']) ?></h2>

<a href="test_setup.php?list_id=<?= $list_id ?>"
   class="btn btn-primary">

    Kiểm tra list này

</a>

</div>

<div class="card p-4 mb-4">

<h5>Thêm từ vựng</h5>

<form method="POST">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<input type="hidden"
       name="action"
       value="add">

<div class="row g-3">

<div class="col-md-6">

<label class="form-label">Từ tiếng Anh</label>

<input name="english"
       class="form-control"
       required>

</div>

<div class="mb-3">
    <label class="form-label">Loại từ</label>

    <select name="part_of_speech" class="form-select" required>
        <option value="">-- Chọn loại từ --</option>
        <option value="noun"> (Noun)</option>
        <option value="verb">(Verb)</option>
        <option value="adjective"> (Adjective)</option>
        <option value="adverb"> (Adverb)</option>
        
    </select>
</div>

<div class="col-md-6">

<label class="form-label">Phát âm</label>

<input name="pronunciation"
       class="form-control"
       placeholder="/.../"
       required>

</div>

<div class="col-md-6">

<label class="form-label">Nghĩa tiếng Việt</label>

<input name="meaning"
       class="form-control"
       required>

</div>

<div class="col-12">

<label class="form-label">
    Nghĩa tương đương được chấp nhận
</label>

<input name="alternatives"
       class="form-control"
       placeholder="Ví dụ: xinh xắn, đẹp">

<small class="text-secondary">
    Các nghĩa cách nhau bằng dấu phẩy.
</small>

</div>

</div>

<button class="btn btn-primary mt-3">
    Thêm từ
</button>

</form>

</div>

<div class="card p-3">

<h5>Các từ trong danh sách</h5>

<div class="table-responsive">

<table class="table align-middle">

<thead>

<tr>
    <th>Tiếng Anh</th>
    <th>Loại từ</th>
    <th>Phát âm</th>
    <th>Nghĩa</th>
    <th>Thao tác</th>
</tr>

</thead>

<tbody>

<?php foreach ($words as $word): ?>

<tr>

<td><?= e($word['english']) ?></td>

<td><?= e($word['part_of_speech']) ?></td>

<td><?= e($word['pronunciation']) ?></td>

<td><?= e($word['meaning']) ?></td>

<td>

<a href="edit_word.php?id=<?= $word['id'] ?>"
   class="btn btn-sm btn-outline-primary">

    Sửa

</a>

<form method="POST" class="d-inline"
      onsubmit="return confirm('Xóa từ này?')">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<input type="hidden"
       name="action"
       value="delete_word">

<input type="hidden"
       name="vocab_id"
       value="<?= $word['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
    Xóa
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<form method="POST"
      onsubmit="return confirm('Xóa toàn bộ danh sách?')">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<input type="hidden"
       name="action"
       value="delete_list">

<button class="btn btn-outline-danger">
    Xóa danh sách
</button>

</form>

</div>

<?php require 'includes/footer.php'; ?>