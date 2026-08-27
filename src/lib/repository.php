<?php

require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/constants.php';

function findUserByLoginId(PDO $pdo, string $loginId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE login_id = :login_id');
    $stmt->execute(['login_id' => $loginId]);
    $user = $stmt->fetch();
    return $user !== false ? $user : null;
}

function createUser(PDO $pdo, string $loginId, string $displayName, string $passwordHash): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (login_id, display_name, password_hash) VALUES (:login_id, :display_name, :password_hash)'
    );
    $stmt->execute([
        'login_id' => $loginId,
        'display_name' => $displayName,
        'password_hash' => $passwordHash,
    ]);

    return (int) $pdo->lastInsertId();
}

function fetchAllUsers(PDO $pdo): array
{
    return $pdo->query('SELECT id, login_id, display_name FROM users ORDER BY id')->fetchAll();
}

/**
 * @return array<int, array<string, int>> user_id => [language => level]
 */
function fetchSkillsByUser(PDO $pdo): array
{
    $rows = $pdo->query('SELECT user_id, language, level FROM language_skills')->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[(int) $row['user_id']][$row['language']] = (int) $row['level'];
    }

    return $result;
}

function upsertSkill(PDO $pdo, int $userId, string $language, int $level): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO language_skills (user_id, language, level) VALUES (:user_id, :language, :level)
         ON DUPLICATE KEY UPDATE level = VALUES(level)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'language' => $language,
        'level' => $level,
    ]);
}

/**
 * 全メンバーの全タスクを、所有者の表示名付きで取得する(Topページ用)。
 */
function fetchAllTasksWithUsers(PDO $pdo): array
{
    return $pdo->query(
        'SELECT t.*, u.display_name FROM tasks t
         JOIN users u ON u.id = t.user_id
         ORDER BY u.id, t.created_at'
    )->fetchAll();
}

function fetchTasksByUser(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function createTask(PDO $pdo, int $userId, string $title, int $status, bool $hasQuestion): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO tasks (user_id, title, status, has_question) VALUES (:user_id, :title, :status, :has_question)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'title' => $title,
        'status' => $status,
        'has_question' => $hasQuestion ? 1 : 0,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * 自分のタスクのみ更新できるよう user_id も条件に含める。
 */
function updateTaskStatus(PDO $pdo, int $taskId, int $userId, int $status, bool $hasQuestion): void
{
    $stmt = $pdo->prepare(
        'UPDATE tasks SET status = :status, has_question = :has_question
         WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
        'status' => $status,
        'has_question' => $hasQuestion ? 1 : 0,
        'id' => $taskId,
        'user_id' => $userId,
    ]);
}
