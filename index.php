<?php

require_once 'includes/functions.php';

require_login();

$user_id = (int) current_user()['id'];

/*
|--------------------------------------------------------------------------
| ĐẾM DANH SÁCH
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM vocabulary_lists
     WHERE is_public = 1 OR user_id = ?"
);

$stmt->execute([$user_id]);

$list_count = (int)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| ĐẾM TỪ VỰNG
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM vocabularies v
     INNER JOIN vocabulary_lists l
        ON v.list_id = l.id
     WHERE l.is_public = 1 OR l.user_id = ?"
);

$stmt->execute([$user_id]);

$word_count = (int)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| ĐẾM LƯỢT KIỂM TRA
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM test_history
     WHERE user_id = ?"
);

$stmt->execute([$user_id]);

$test_count = (int)$stmt->fetchColumn();

$pageTitle = 'Trang chủ';

require 'includes/header.php';

?>

<div class="hero p-5 mb-4">

    <h1>Welcome to VocabUp!</h1>

    <p>
        Học từ vựng tiếng Anh theo cách của bạn.
        Sử dụng bộ Complete TOEIC có sẵn hoặc tự tạo
        danh sách từ vựng riêng.
    </p>

    <a
        href="lists.php"
        class="btn btn-light"
    >
        Quản lý từ vựng
    </a>

    <a
        href="test_setup.php"
        class="btn btn-outline-light ms-2"
    >
        Bắt đầu kiểm tra
    </a>

</div>


<div class="row g-3">

    <div class="col-md-4">

        <div class="card p-4">

            <h6>Danh sách từ vựng</h6>

            <h2>
                <?= $list_count ?>
            </h2>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card p-4">

            <h6>Tổng số từ</h6>

            <h2>
                <?= $word_count ?>
            </h2>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card p-4">

            <h6>Lượt kiểm tra</h6>

            <h2>
                <?= $test_count ?>
            </h2>

        </div>

    </div>

</div>


<?php require 'includes/footer.php'; ?>