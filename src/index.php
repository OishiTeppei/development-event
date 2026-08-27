<?php

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/repository.php';

$user = requireLogin();

$dbError = null;
$users = [];
$skillsByUser = [];
$tasksByUser = [];
$sosTasks = [];
$questionTasks = [];

try {
    $pdo = getDbConnection();
    $users = fetchAllUsers($pdo);
    $skillsByUser = fetchSkillsByUser($pdo);

    foreach (fetchAllTasksWithUsers($pdo) as $task) {
        $tasksByUser[(int) $task['user_id']][] = $task;
        if ((int) $task['status'] >= SOS_STATUS_THRESHOLD) {
            $sosTasks[] = $task;
        }
        if ((int) $task['has_question'] === 1) {
            $questionTasks[] = $task;
        }
    }
} catch (PDOException $e) {
    $dbError = 'DB接続エラー: ' . $e->getMessage();
}

$pageTitle = 'Top';
require __DIR__ . '/includes/app_layout_start.php';
?>
<h1>Top</h1>

<?php if ($dbError): ?>
<p class="error"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></p>
<?php else: ?>

<?php if ($sosTasks): ?>
<section class="sos-section">
    <h2>🆘 ヘルプ通知(状態「やばい」「かなりやばい」のタスク)</h2>
    <ul>
        <?php foreach ($sosTasks as $task): ?>
        <li>
            <strong><?php echo htmlspecialchars($task['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong>:
            <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?>
            (<?php echo htmlspecialchars(STATUS_LABELS[(int) $task['status']], ENT_QUOTES, 'UTF-8'); ?>)
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($questionTasks): ?>
<section class="question-section">
    <h2>❓ 質問ありのタスク</h2>
    <ul>
        <?php foreach ($questionTasks as $task): ?>
        <li>
            <strong><?php echo htmlspecialchars($task['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong>:
            <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="members-section">
    <h2>メンバー状況</h2>
    <div class="member-cards">
        <?php foreach ($users as $member): ?>
        <div class="member-card">
            <h3>
                <?php echo htmlspecialchars($member['display_name'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ((int) $member['id'] === (int) $user['id']): ?><span class="badge-self">自分</span><?php endif; ?>
            </h3>

            <h4>言語理解度</h4>
            <table class="skill-table">
                <?php foreach (LANGUAGES as $key => $label): ?>
                <tr>
                    <th><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></th>
                    <td>
                        <?php echo isset($skillsByUser[$member['id']][$key])
                            ? $skillsByUser[$member['id']][$key] . ' / 10'
                            : '未設定'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h4>タスク状態</h4>
            <?php if (!empty($tasksByUser[$member['id']])): ?>
            <ul class="task-list">
                <?php foreach ($tasksByUser[$member['id']] as $task): ?>
                <li class="status-<?php echo (int) $task['status']; ?>">
                    <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?>
                    - <?php echo htmlspecialchars(STATUS_LABELS[(int) $task['status']], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($task['has_question']): ?><span class="badge">質問あり</span><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="muted">タスクなし</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php endif; ?>
<?php
require __DIR__ . '/includes/app_layout_end.php';
