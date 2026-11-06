<?php

require_once 'includes/functions.php';

require_login();

$test = $_SESSION['active_test'] ?? null;

if (!$test) {
    redirect('test_setup.php');
}

$index = $test['index'];

$total = count($test['queue']);

if ($index >= $total) {
    redirect('test_result.php');
}

$question = $test['queue'][$index];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    if (
        $action === 'answer' &&
        !$test['answered']
    ) {

        $user_answer = trim($_POST['answer'] ?? '');

        $correct_answer =
            $question['direction'] === 'en_vi'
            ? $question['meaning']
            : $question['english'];

        $accepted_answers = [$correct_answer];

        /*
        Nếu câu hỏi là tiếng Anh -> tiếng Việt,
        lấy thêm các nghĩa tương đương.
        */

        if ($question['direction'] === 'en_vi') {

            $stmt = $conn->prepare(
                "SELECT accepted_answer
                 FROM vocabulary_answers
                 WHERE vocabulary_id = ?"
            );

            $stmt->execute([$question['id']]);

            $accepted_answers = array_merge(
                $accepted_answers,
                $stmt->fetchAll(PDO::FETCH_COLUMN)
            );

        }

        /*
        Chuẩn hóa và kiểm tra đáp án.
        */

        $correct = false;

        $normalized = normalize_answer($user_answer);

        if ($normalized !== '') {

            foreach ($accepted_answers as $answer) {

                if (
                    $normalized ===
                    normalize_answer($answer)
                ) {

                    $correct = true;

                    break;

                }

            }

        }

        /*
        Lưu chi tiết câu trả lời.
        */

        $stmt = $conn->prepare(
            "INSERT INTO test_details
            (
                test_id,
                vocabulary_id,
                direction,
                user_answer,
                correct_answer,
                is_correct
            )
            VALUES(?,?,?,?,?,?)"
        );

        $stmt->execute([

            $test['id'],

            $question['id'],

            $question['direction'],

            $user_answer,

            $correct_answer,

            $correct ? 1 : 0

        ]);

        $_SESSION['active_test']['answered'] = true;

        $_SESSION['active_test']['feedback'] = [

            'correct' => $correct,

            'user_answer' => $user_answer,

            'correct_answer' => $correct_answer

        ];

    }

    elseif (
        $action === 'next' &&
        $test['answered']
    ) {

        $_SESSION['active_test']['index']++;

        $_SESSION['active_test']['answered'] = false;

        $_SESSION['active_test']['feedback'] = null;

        redirect('test_question.php');

    }

}

$test = $_SESSION['active_test'];

$feedback = $test['feedback'];

$answered = $test['answered'];

$question = $test['queue'][$index];

$prompt = $question['direction'] === 'en_vi'
    ? $question['english']
    : $question['meaning'];

$placeholder = $question['direction'] === 'en_vi'
    ? 'Nhập nghĩa tiếng Việt'
    : 'Nhập từ tiếng Anh';

$pageTitle = 'Câu hỏi ' . ($index + 1);

require 'includes/header.php';

?>

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card p-4">

<div class="d-flex justify-content-between mb-3">

<span>
    Câu <?= $index + 1 ?> / <?= $total ?>
</span>

<span>
    <?= round(($index / $total) * 100) ?>%
</span>

</div>

<div class="progress mb-4">

<div class="progress-bar"
     style="width: <?= ($index / $total) * 100 ?>%">

</div>

</div>

<div class="text-center py-4">

<p class="text-secondary">

<?= $question['direction'] === 'en_vi'
    ? 'Dịch sang tiếng Việt'
    : 'Dịch sang tiếng Anh' ?>

</p>

<div class="word">

<?= e($prompt) ?>

</div>

<div class="mt-3 text-secondary">

<?= e($question['part_of_speech']) ?>

&nbsp; · &nbsp;

<?= e($question['pronunciation']) ?>

</div>

</div>

<?php if (!$answered): ?>

<form method="POST">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<input type="hidden"
       name="action"
       value="answer">

<input type="text"
       name="answer"
       class="form-control form-control-lg mb-3"
       placeholder="<?= e($placeholder) ?>"
       autocomplete="off"
       required
       autofocus>

<button class="btn btn-primary w-100">

    Kiểm tra đáp án

</button>

</form>

<?php else: ?>

<div class="alert alert-<?= $feedback['correct']
    ? 'success'
    : 'danger' ?>">

<strong>

<?= $feedback['correct']
    ? 'Chính xác!'
    : 'Chưa chính xác!' ?>

</strong>

<hr>

Đáp án của bạn:

<strong>
    <?= e($feedback['user_answer']) ?>
</strong>

<br>

Đáp án đúng:

<strong>
    <?= e($feedback['correct_answer']) ?>
</strong>

</div>

<form method="POST">

<input type="hidden"
       name="csrf"
       value="<?= csrf_token() ?>">

<input type="hidden"
       name="action"
       value="next">

<button class="btn btn-primary w-100">

<?= $index + 1 === $total
    ? 'Xem kết quả'
    : 'Câu tiếp theo' ?>

</button>

</form>

<?php endif; ?>

</div>

</div>

</div>

<?php require 'includes/footer.php'; ?>