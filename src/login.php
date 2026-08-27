<?php

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/repository.php';

if (currentUser() !== null) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$loginId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $loginId = trim($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $pdo = getDbConnection();
            $user = findUserByLoginId($pdo, $loginId);
            if ($user !== null && password_verify($password, $user['password_hash'])) {
                loginAs($user);
                header('Location: /index.php');
                exit;
            }
            $errors[] = 'IDまたはパスワードが正しくありません。';
        } catch (PDOException $e) {
            $errors[] = 'DB接続エラー: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'ログイン';
require __DIR__ . '/includes/auth_layout_start.php';
?>
<h1>ログイン</h1>

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
    <label>パスワード
        <input type="password" name="password" required>
    </label>
    <button type="submit">ログイン</button>
</form>
<p><a href="/register.php">新規登録はこちら</a></p>
<?php
require __DIR__ . '/includes/auth_layout_end.php';
