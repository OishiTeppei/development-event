<?php
/** @var string $pageTitle */
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - development-event</title>
    <link rel="stylesheet" href="/assets/styles/common.css">
</head>
<body>
<div class="app-layout">
    <nav class="sidebar">
        <p class="sidebar-user"><?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?> さん</p>
        <ul class="sidebar-nav">
            <li><a href="/index.php">Top</a></li>
            <li><a href="/mypage.php">マイページ</a></li>
        </ul>
        <form method="post" action="/logout.php">
            <button type="submit" class="link-button">ログアウト</button>
        </form>
    </nav>
    <main class="main-content">
