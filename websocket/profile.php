<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO('sqlite:kadai.db', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $sql = 'SELECT id, user_name, password, profile_image FROM users WHERE user_name = :user_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_name' => $_SESSION['user_name']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "ユーザー情報が見つかりません。";
        exit;
    }
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="container">
        <h1>プロフィール</h1>

        <?php if (!empty($user['profile_image'])): ?>
            <img src="uploads/<?php echo htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="プロフィール画像" width="150">
        <?php else: ?>
            <p>プロフィール画像は設定されていません。</p>
        <?php endif; ?>

        <p><strong>ユーザー名:</strong> <?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>パスワード:</strong> ********</p>

        <p><a href="change_profile.php?id=<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>" class="change-password">プロフィールの変更</a></p>
        <p><a href="login.php" class="logout">ログアウト</a></p>
        <p><a href="menu.php" class="menu">戻る</a></p>
    </div>
</body>

</html>
