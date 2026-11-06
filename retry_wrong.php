<?php

require_once 'includes/functions.php';

require_login();

$test_id = (int)($_GET['test_id'] ?? 0);

$user_id = (int)current_user()['id'];

/*
|--------------------------------------------------------------------------
| KIỂM TRA BÀI KIỂM TRA THUỘC NGƯỜI DÙNG
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT *
     FROM test_history
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);

$stmt->execute([
    $test_id,
    $user_id
]);

$original = $stmt->fetch();

if (!$original) {

    flash(
        'danger',
        'Bài kiểm tra không hợp lệ.'
    );

    redirect('history.php');
}

/*
|--------------------------------------------------------------------------
| LẤY CÁC TỪ TRẢ LỜI SAI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT DISTINCT
        v.*
     FROM test_details td
     INNER JOIN vocabularies v
        ON td.vocabulary_id = v.id
     INNER JOIN vocabulary_lists l
        ON v.list_id = l.id
     WHERE td.test_id = ?
       AND td.is_correct = 0
       AND (l.is_public = 1 OR l.user_id = ?)"
);

$stmt->execute([
    $test_id,
    $user_id
]);

$words = $stmt->fetchAll();

if (empty($words)) {

    flash(
        'warning',
        'Không có câu sai để làm lại.'
    );

    redirect('history.php');
}

/*
|--------------------------------------------------------------------------
| TRỘN CÂU HỎI
|--------------------------------------------------------------------------
*/

shuffle($words);

$questions = [];

foreach ($words as $word) {

    $questions[] = [

        'id' => (int)$word['id'],

        'english' => $word['english'],

        'part_of_speech' => $word['part_of_speech'],

        'pronunciation' => $word['pronunciation'],

        'meaning' => $word['meaning'],

        'direction' => random_int(0, 1)
            ? 'en_vi'
            : 'vi_en'

    ];
}

/*
|--------------------------------------------------------------------------
| TẠO LỊCH SỬ ÔN TẬP
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "INSERT INTO test_history
    (
        user_id,
        list_id,
        mode,
        parent_test_id,
        total_questions
    )
    VALUES (?, ?, 'wrong', ?, ?)"
);

$stmt->execute([

    $user_id,

    $original['list_id'],

    $test_id,

    count($questions)

]);

$new_test_id = (int)$conn->lastInsertId();

/*
|--------------------------------------------------------------------------
| LƯU SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['active_test'] = [

    'id' => $new_test_id,

    'queue' => $questions,

    'index' => 0,

    'answered' => false,

    'feedback' => null

];

redirect('test_question.php');