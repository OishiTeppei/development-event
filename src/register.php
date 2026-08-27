<?php

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/repository.php';

if (currentUser() !== null) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$loginId = '';
$displayName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $loginId = trim($_POST['login_id'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($loginId === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $loginId)) {
            $errors[] = 'IDは半角英数字と ._- のみ、3〜50文字で入力してください。';
        }
        if ($displayName === '' || mb_strlen($displayName) > 50) {
            $errors[] = '表示名は1〜50文字で入力してください。';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'パスワードは8文字以上で入力してください。';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'パスワードが一致しません。';
        }

        if (!$errors) {
            try {
                $pdo = getDbConnection();
                if (findUserByLoginId($pdo, $loginId) !== null) {
                    $errors[] = 'このIDは既に使用されています。';
                } else {
                    $userId = createUser($pdo, $loginId, $displayName, password_hash($password, PASSWORD_DEFAULT));
                    loginAs(['id' => $userId, 'login_id' => $loginId, 'display_name' => $displayName]);
                    header('Location: /mypage.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'DB接続エラー: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = '新規登録';
require __DIR__ . '/includes/auth_layout_start.php';
?>
<h1>新規登録</h1>

<?php if ($errors): ?>
<ul class="errors">
    <?php foreach ($errors as $error): ?>
    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<form method="post" class="form-card">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <label>ID
        <input type="text" name="login_id" value="<?php echo htmlspecialchars($loginId, ENT_QUOTES, 'UTF-8'); ?>" required>
    </label>
    <label>表示名
        <input type="text" name="display_name" value="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" required>
    </label>
    <label>パスワード
        <input type="password" name="password" required minlength="8">
    </label>
    <label>パスワード(確認)
        <input type="password" name="password_confirm" required minlength="8">
    </label>
    <button type="submit">登録する</button>
</form>
<p><a href="/login.php">既にアカウントをお持ちの方はこちら</a></p>
<?php
require __DIR__ . '/includes/auth_layout_end.php';
