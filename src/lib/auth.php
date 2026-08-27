<?php

require_once __DIR__ . '/../dbconnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ログイン中のユーザー情報を返す。未ログインなら null。
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * ログインを必須とするページの先頭で呼び出す。未ログインならログイン画面へリダイレクトする。
 */
function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

/**
 * セッションにログイン状態を保存する。
 */
function loginAs(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'login_id' => $user['login_id'],
        'display_name' => $user['display_name'],
    ];
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/**
 * フォーム用のCSRFトークンを取得(未発行なら発行)する。
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
