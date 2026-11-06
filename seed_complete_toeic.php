<?php

/*
|--------------------------------------------------------------------------
| SEED COMPLETE TOEIC
|--------------------------------------------------------------------------
|
| Chạy bằng terminal:
|
| php seed_complete_toeic.php
|
| File complete_toeic_23_lists.json phải nằm cùng thư mục.
|
| Chỉ cho phép chạy bằng PHP CLI.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/functions.php';

if (PHP_SAPI !== 'cli') {

    http_response_code(403);

    exit("Forbidden\n");
}

$json_path = __DIR__ . '/complete_toeic_23_lists.json';

if (!is_file($json_path) || !is_readable($json_path)) {

    fwrite(
        STDERR,
        "Không tìm thấy hoặc không đọc được file JSON.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| ĐỌC JSON
|--------------------------------------------------------------------------
*/

$json = file_get_contents($json_path);

$data = json_decode($json, true);

if (
    !is_array($data) ||
    !isset($data['lists']) ||
    !is_array($data['lists'])
) {

    fwrite(
        STDERR,
        "Dữ liệu JSON không đúng cấu trúc.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| CHUYỂN LOẠI TỪ
|--------------------------------------------------------------------------
*/

$type_map = [

    '(n)' => 'noun',

    '(v)' => 'verb',

    '(adj)' => 'adjective',

    '(adv)' => 'adverb',

    '(pron)' => 'pronoun',

    '(prep)' => 'preposition',

    '(conj)' => 'conjunction',

    '(interj)' => 'interjection'

];

$created_lists = 0;

$inserted_words = 0;

$skipped_words = 0;

try {

    $conn->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | TÌM LIST ĐÃ CÓ
    |--------------------------------------------------------------------------
    */

    $find_list = $conn->prepare(
        "SELECT id
         FROM vocabulary_lists
         WHERE user_id IS NULL
           AND name = ?
         LIMIT 1"
    );

    /*
    |--------------------------------------------------------------------------
    | TẠO LIST
    |--------------------------------------------------------------------------
    */

    $create_list = $conn->prepare(
        "INSERT INTO vocabulary_lists
        (
            user_id,
            name,
            description,
            is_public
        )
        VALUES (NULL, ?, ?, 1)"
    );

    /*
    |--------------------------------------------------------------------------
    | CẬP NHẬT LIST CŨ
    |--------------------------------------------------------------------------
    */

    $update_list = $conn->prepare(
        "UPDATE vocabulary_lists
         SET is_public = 1,
             description = ?
         WHERE id = ?
           AND user_id IS NULL"
    );

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA TỪ ĐÃ TỒN TẠI
    |--------------------------------------------------------------------------
    */

    $find_word = $conn->prepare(
        "SELECT id
         FROM vocabularies
         WHERE list_id = ?
           AND LOWER(english) = LOWER(?)
         LIMIT 1"
    );

    /*
    |--------------------------------------------------------------------------
    | THÊM TỪ
    |--------------------------------------------------------------------------
    */

    $insert_word = $conn->prepare(
        "INSERT INTO vocabularies
        (
            list_id,
            english,
            part_of_speech,
            pronunciation,
            meaning
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    /*
    |--------------------------------------------------------------------------
    | DUYỆT CÁC LIST
    |--------------------------------------------------------------------------
    */

    foreach ($data['lists'] as $list_data) {

        if (!is_array($list_data)) {
            continue;
        }

        $name = trim(
            (string)($list_data['name'] ?? '')
        );

        $description = trim(
            (string)($list_data['description'] ?? '')
        );

        if ($name === '') {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | TÌM HOẶC TẠO LIST
        |--------------------------------------------------------------------------
        */

        $find_list->execute([$name]);

        $list_id = $find_list->fetchColumn();

        if (!$list_id) {

            $create_list->execute([

                $name,

                $description

            ]);

            $list_id = (int)$conn->lastInsertId();

            $created_lists++;

        } else {

            $list_id = (int)$list_id;

            $update_list->execute([

                $description,

                $list_id

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | DUYỆT TỪ TRONG LIST
        |--------------------------------------------------------------------------
        */

        $list_words = $list_data['words'] ?? [];

        if (!is_array($list_words)) {
            continue;
        }

        foreach ($list_words as $word) {

            if (!is_array($word)) {

                $skipped_words++;

                continue;
            }

            $english = trim(
                (string)($word['english'] ?? '')
            );

            $pos = trim(
                (string)($word['part_of_speech'] ?? '')
            );

            $pronunciation = trim(
                (string)($word['pronunciation'] ?? '')
            );

            $meaning = trim(
                (string)($word['meaning'] ?? '')
            );

            if ($english === '' || $meaning === '') {

                $skipped_words++;

                continue;
            }

            $pos = $type_map[strtolower($pos)]
                ?? ($pos !== '' ? $pos : 'phrase');

            /*
            |--------------------------------------------------------------------------
            | BỎ QUA TỪ ĐÃ TỒN TẠI
            |--------------------------------------------------------------------------
            */

            $find_word->execute([

                $list_id,

                $english

            ]);

            if ($find_word->fetchColumn()) {

                $skipped_words++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | INSERT TỪ MỚI
            |--------------------------------------------------------------------------
            */

            $insert_word->execute([

                $list_id,

                $english,

                $pos,

                $pronunciation,

                $meaning

            ]);

            $inserted_words++;

        }

    }

    $conn->commit();

    echo "====================================\n";

    echo "HOÀN TẤT CÀI ĐẶT COMPLETE TOEIC\n";

    echo "====================================\n";

    echo "List mới: {$created_lists}\n";

    echo "Từ mới: {$inserted_words}\n";

    echo "Từ bỏ qua: {$skipped_words}\n";

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    fwrite(
        STDERR,
        "Lỗi khi nhập dữ liệu: " . $e->getMessage() . "\n"
    );

    exit(1);
}