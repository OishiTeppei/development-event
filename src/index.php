<?php

require_once __DIR__ . '/dbconnect.php';

$dbStatus = '';
try {
    $pdo = getDbConnection();
    $dbStatus = 'DB接続に成功しました。';
} catch (PDOException $e) {
    $dbStatus = 'DB接続エラー: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>development-event</title>
</head>
<body>
    <h1>development-event</h1>
    <p><?php echo htmlspecialchars($dbStatus, ENT_QUOTES, 'UTF-8'); ?></p>
</body>
</html>
