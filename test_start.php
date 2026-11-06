<?php

require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('test_setup.php');
}

verify_csrf();

$user_id = (int) current_user()['id'];

$list_id = (int)($_POST['list_id'] ?? 0);

try {

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA QUYỀN TRUY CẬP
    |--------------------------------------------------------------------------
    */

    if ($list_id > 0) {

        $stmt = $conn->prepare(
            "SELECT id
             FROM vocabulary_lists
             WHERE id = ?
             AND (is_public = 1 OR user_id = ?)
             LIMIT 1"
        );

        $stmt->execute([
            $list_id,
            $user_id
        ]);

        if (!$stmt->fetch()) {

            flash(
                'danger',
                'Danh sách không hợp lệ.'
            );

            redirect('test_setup.php');
        }

        /*
        |--------------------------------------------------------------------------
        | LẤY TỪ TRONG LIST ĐƯỢC CHỌN
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "SELECT
                v.id,
                v.english,
                v.part_of_speech,
                v.pronunciation,
                v.meaning
             FROM vocabularies v
             INNER JOIN vocabulary_lists l
                ON v.list_id = l.id
             WHERE v.list_id = ?
             AND (l.is_public = 1 OR l.user_id = ?)"
        );

        $stmt->execute([
            $list_id,
            $user_id
        ]);

        $mode = 'list';

        $history_list_id = $list_id;

    } else {

        /*
        |--------------------------------------------------------------------------
        | LẤY TẤT CẢ LIST CÔNG KHAI VÀ LIST CÁ NHÂN
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "SELECT
                v.id,
                v.english,
                v.part_of_speech,
                v.pronunciation,
                v.meaning
             FROM vocabularies v
             INNER JOIN vocabulary_lists l
                ON v.list_id = l.id
             WHERE l.is_public = 1
                OR l.user_id = ?"
        );

        $stmt->execute([$user_id]);

        $mode = 'all';

        $history_list_id = null;
    }

    $words = $stmt->fetchAll();

    if (empty($words)) {

        flash(
            'warning',
            'Danh sách này chưa có từ vựng.'
        );

        redirect('test_setup.php');
    }

    /*
    |--------------------------------------------------------------------------
    | TRỘN TỪ
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
    | TẠO LỊCH SỬ KIỂM TRA
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO test_history
        (
            user_id,
            list_id,
            mode,
            total_questions
        )
        VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([

        $user_id,

        $history_list_id,

        $mode,

        count($questions)

    ]);

    $test_id = (int)$conn->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | LƯU SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['active_test'] = [

        'id' => $test_id,

        'queue' => $questions,

        'index' => 0,

        'answered' => false,

        'feedback' => null

    ];

    redirect('test_question.php');

} catch (Throwable $e) {

    error_log(
        'test_start error: ' . $e->getMessage()
    );

    flash(
        'danger',
        'Không thể tạo bài kiểm tra. Vui lòng thử lại.'
    );

    redirect('test_setup.php');
}