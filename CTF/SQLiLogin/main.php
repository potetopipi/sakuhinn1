<?php
// セッションの開始
session_start();

// セッション変数が設定されていない場合、ログインページにリダイレクト
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE HTML>
<html lang="ja">
<head>
    <title>メインページ</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css"> <!-- CSSファイルをリンク -->
</head>
<body>
    <div class="container">
        <h1>ようこそ</h1>
        <p><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>さん。</p>
        <p>ログイン成功しました。</p>
        <h3 class="flag">flagは hscctf2024{SQLinjectionhuckweb}</h3>
        <a href="login.php">ログアウト</a>
    </div>
</body>
</html>
