<?php

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/repository.php';

$user = requireLogin();

$errors = [];
$notice = null;
$dbError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $pdo = getDbConnection();

            if ($action === 'update_skills') {
                $levels = [];
                foreach (array_keys(LANGUAGES) as $lang) {
                    $level = (int) ($_POST['level'][$lang] ?? 0);
                    if ($level < 1 || $level > 10) {
                        $errors[] = '理解度は1〜10の範囲で選択してください。';
                        break;
                    }
                    $levels[$lang] = $level;
                }
                if (!$errors) {
                    foreach ($levels as $lang => $level) {
                        upsertSkill($pdo, $user['id'], $lang, $level);
                    }
                    $notice = '理解度を更新しました。';
                }
            } elseif ($action === 'add_task') {
                $title = trim($_POST['title'] ?? '');
                $status = (int) ($_POST['status'] ?? 0);
                $hasQuestion = ($_POST['has_question'] ?? '0') === '1';

                if ($title === '' || mb_strlen($title) > 255) {
                    $errors[] = 'タスク名は1〜255文字で入力してください。';
                } elseif ($status < 1 || $status > 5) {
                    $errors[] = 'タスクの状態を選択してください。';
                } else {
                    createTask($pdo, $user['id'], $title, $status, $hasQuestion);
                    $notice = 'タスクを追加しました。';
                }
            } elseif ($action === 'update_task') {
                $taskId = (int) ($_POST['task_id'] ?? 0);
                $status = (int) ($_POST['status'] ?? 0);
                $hasQuestion = ($_POST['has_question'] ?? '0') === '1';

                if ($status < 1 || $status > 5) {
                    $errors[] = 'タスクの状態を選択してください。';
                } else {
                    updateTaskStatus($pdo, $taskId, $user['id'], $status, $hasQuestion);
                    $notice = $status >= SOS_STATUS_THRESHOLD
                        ? 'タスクを更新しました。ヘルプ通知としてTopページに表示されます。'
                        : 'タスクを更新しました。';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'DB接続エラー: ' . $e->getMessage();
        }
    }
}

$skills = [];
$tasks = [];
try {
    $pdo = getDbConnection();
    $allSkills = fetchSkillsByUser($pdo);
    $skills = $allSkills[$user['id']] ?? [];
    $tasks = fetchTasksByUser($pdo, $user['id']);
} catch (PDOException $e) {
    $dbError = 'DB接続エラー: ' . $e->getMessage();
}

$pageTitle = 'マイページ';
require __DIR__ . '/includes/app_layout_start.php';
?>
<h1>マイページ</h1>

<?php if ($dbError): ?>
<p class="error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($notice): ?>
<p class="notice"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($errors): ?>
<ul class="errors">
    <?php foreach ($errors as $error): ?>
    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<section class="skills-section">
    <h2>1. 各言語の理解度(10段階)</h2>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="update_skills">
        <?php foreach (LANGUAGES as $key => $label): ?>
        <label class="skill-field">
            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            <select name="level[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo (($skills[$key] ?? null) === $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <?php endforeach; ?>
        <button type="submit">理解度を保存</button>
    </form>
</section>

<section class="tasks-section">
    <h2>2. 自分のタスク</h2>

    <h3>タスクを追加</h3>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="add_task">
        <label>タスク名
            <input type="text" name="title" required maxlength="255">
        </label>
        <label>状態
            <select name="status" required>
                <option value="">選択してください</option>
                <?php foreach (STATUS_LABELS as $value => $label): ?>
                <option value="<?php echo $value; ?>"><?php echo $value; ?>: <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <fieldset>
            <legend>質問</legend>
            <label><input type="radio" name="has_question" value="1"> 質問あり</label>
            <label><input type="radio" name="has_question" value="0" checked> 質問なし</label>
        </fieldset>
        <button type="submit">タスクを追加</button>
    </form>

    <h3>保有タスク一覧</h3>
    <?php if (!$tasks): ?>
    <p class="muted">タスクはまだありません。</p>
    <?php else: ?>
    <ul class="task-edit-list">
        <?php foreach ($tasks as $task): ?>
        <li class="status-<?php echo (int) $task['status']; ?>">
            <form method="post" class="task-edit-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                <span class="task-title"><?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <select name="status">
                    <?php foreach (STATUS_LABELS as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo ((int) $task['status'] === $value) ? 'selected' : ''; ?>><?php echo $value; ?>: <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="question-toggle">
                    <label><input type="radio" name="has_question" value="1" <?php echo $task['has_question'] ? 'checked' : ''; ?>> 質問あり</label>
                    <label><input type="radio" name="has_question" value="0" <?php echo !$task['has_question'] ? 'checked' : ''; ?>> 質問なし</label>
                </span>
                <button type="submit">更新</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
<?php
require __DIR__ . '/includes/app_layout_end.php';
