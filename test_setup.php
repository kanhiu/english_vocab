
<?php

require_once 'includes/functions.php';

require_login();

$user_id = (int) current_user()['id'];

/*
|--------------------------------------------------------------------------
| LẤY LIST CÔNG KHAI VÀ LIST CÁ NHÂN
|--------------------------------------------------------------------------
| Complete TOEIC được sắp xếp theo số thứ tự List 1 -> List 23.
| List cá nhân được xếp theo tên A-Z.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, name, is_public
     FROM vocabulary_lists
     WHERE is_public = 1 OR user_id = ?

     ORDER BY
        CASE
            WHEN is_public = 1 THEN 0
            ELSE 1
        END ASC,

        CASE
            WHEN is_public = 1
                 AND name LIKE 'Complete TOEIC - List %'
            THEN CAST(
                SUBSTRING_INDEX(name, 'List ', -1)
                AS UNSIGNED
            )
            ELSE 999999
        END ASC,

        name ASC"
);

$stmt->execute([$user_id]);

$lists = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| KIỂM TRA LIST ĐƯỢC CHỌN
|--------------------------------------------------------------------------
*/

$selected = (int)($_GET['list_id'] ?? 0);

$valid_ids = array_map(
    function ($list) {
        return (int)$list['id'];
    },
    $lists
);

if (
    $selected > 0 &&
    !in_array($selected, $valid_ids, true)
) {
    $selected = 0;
}

$pageTitle = 'Kiểm tra từ vựng';

require 'includes/header.php';

?>

<div class="row justify-content-center">

    <div class="col-lg-7">

        <div class="card p-4">

            <h2>Kiểm tra từ vựng</h2>

            <p class="text-secondary">

                Chọn danh sách muốn kiểm tra.

                Hệ thống sẽ trộn câu hỏi và chọn ngẫu nhiên
                chiều kiểm tra Anh–Việt hoặc Việt–Anh.

            </p>

            <?php if (empty($lists)): ?>

                <div class="alert alert-warning">
                    Chưa có danh sách từ vựng.
                </div>

            <?php else: ?>

                <form method="POST" action="test_start.php">

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <label class="form-label">
                        Chọn danh sách
                    </label>

                    <select
                        name="list_id"
                        class="form-select mb-3"
                    >

                        <option value="0">
                            Tất cả danh sách
                        </option>

                        <?php
                        $showPersonalLabel = false;
                        ?>

                        <?php foreach ($lists as $list): ?>

                            <?php if (
                                (int)$list['is_public'] !== 1
                                && !$showPersonalLabel
                            ): ?>

                                <option disabled>
                                    ─── Danh sách cá nhân ───
                                </option>

                                <?php
                                $showPersonalLabel = true;
                                ?>

                            <?php endif; ?>

                            <option
                                value="<?= (int)$list['id'] ?>"
                                <?= $selected === (int)$list['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e($list['name']) ?>

                                <?= (int)$list['is_public'] === 1
                                    ? ' - Bộ đề có sẵn'
                                    : ' - Danh sách của tôi'
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Bắt đầu kiểm tra
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php require 'includes/footer.php'; ?>
