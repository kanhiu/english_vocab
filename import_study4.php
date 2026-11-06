
<?php

require_once __DIR__ . '/includes/functions.php';

require_login();

$pageTitle = 'Import từ Study4';

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $conn->prepare(
    "SELECT id, name
     FROM vocabulary_lists
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$userId]);
$lists = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $listId = (int)($_POST['list_id'] ?? 0);
    $json = trim($_POST['vocabulary_json'] ?? '');

    if (!user_owns_list($listId)) {

        $error = 'Danh sách từ vựng không hợp lệ.';

    } elseif ($json === '') {

        $error = 'Vui lòng dán dữ liệu JSON từ Study4.';

    } else {

        $words = json_decode($json, true);

        if (!is_array($words) || json_last_error() !== JSON_ERROR_NONE) {

            $error = 'Dữ liệu JSON không hợp lệ.';

        } else {

            $typeMap = [
                'n' => 'noun',
                'v' => 'verb',
                'adj' => 'adjective',
                'adv' => 'adverb',
                'pron' => 'pronoun',
                'prep' => 'preposition',
                'conj' => 'conjunction',
                'interj' => 'interjection'
            ];

            $inserted = 0;
            $skipped = 0;

            try {

                $conn->beginTransaction();

                $check = $conn->prepare(
                    "SELECT id
                     FROM vocabularies
                     WHERE list_id = ?
                     AND LOWER(english) = LOWER(?)
                     LIMIT 1"
                );

                $insert = $conn->prepare(
                    "INSERT INTO vocabularies
                    (list_id, english, part_of_speech, pronunciation, meaning)
                    VALUES (?, ?, ?, ?, ?)"
                );

                foreach ($words as $word) {

                    if (!is_array($word)) {
                        $skipped++;
                        continue;
                    }

                    $english = trim($word['english'] ?? '');
                    $partOfSpeech = trim($word['part_of_speech'] ?? 'phrase');
                    $pronunciation = trim($word['pronunciation'] ?? '');
                    $meaning = trim($word['meaning'] ?? '');

                    if ($english === '' || $meaning === '') {
                        $skipped++;
                        continue;
                    }

                    $partOfSpeech = $typeMap[$partOfSpeech]
                        ?? $partOfSpeech;

                    $check->execute([
                        $listId,
                        $english
                    ]);

                    if ($check->fetch()) {
                        $skipped++;
                        continue;
                    }

                    $insert->execute([
                        $listId,
                        $english,
                        $partOfSpeech,
                        $pronunciation,
                        $meaning
                    ]);

                    $inserted++;
                }

                $conn->commit();

                $success = "Đã nhập thành công $inserted từ. Bỏ qua $skipped từ.";

            } catch (Throwable $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $error = 'Không thể nhập dữ liệu: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<div class="row justify-content-center">
    <div class="col-lg-9">

        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h3 class="mb-3">Import từ vựng từ Study4</h3>

                <p class="text-muted">
                    Sao chép dữ liệu từ Study4 rồi dán vào ô bên dưới
                    để nhập nhiều từ vựng cùng lúc.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($lists)): ?>

                    <div class="alert alert-warning">
                        Bạn chưa có danh sách từ vựng.
                        Hãy tạo danh sách trước khi import.
                    </div>

                    <a href="<?= BASE_URL ?>/lists.php"
                       class="btn btn-primary">
                        Tạo danh sách từ
                    </a>

                <?php else: ?>

                    <form method="POST">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e(csrf_token()) ?>">

                        <div class="mb-3">

                            <label class="form-label">
                                Chọn danh sách cần nhập
                            </label>

                            <select name="list_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    -- Chọn danh sách --
                                </option>

                                <?php foreach ($lists as $list): ?>

                                    <option value="<?= (int)$list['id'] ?>">
                                        <?= e($list['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Dữ liệu từ Study4 (JSON)
                            </label>

                            <textarea
                                name="vocabulary_json"
                                class="form-control"
                                rows="12"
                                placeholder="Dán dữ liệu JSON đã sao chép từ Study4 vào đây..."
                                required></textarea>

                        </div>

                        <button type="submit"
                                class="btn btn-primary">
                            Import từ vựng
                        </button>

                        <a href="<?= BASE_URL ?>/lists.php"
                           class="btn btn-outline-secondary">
                            Quay lại
                        </a>

                    </form>

                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
