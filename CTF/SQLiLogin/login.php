<?php
// エラーメッセージの表示を有効化
ini_set('display_errors', true);
error_reporting(E_ALL);

// 出力バッファリングを有効にする
ob_start();

// セッションスタート
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 変数に格納
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    // PDOでSQLite3に接続
    try {
        $pdo = new PDO('sqlite:ctf.db', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // ユーザー情報を取得（脆弱なコード）
        $sql = "SELECT user_name, password FROM users WHERE user_name = '$user_name' AND password = '$password'";
        $stmt = $pdo->query($sql);
        $user = $stmt->fetch();

        // パスワードの確認をしない
        if ($user) {
            $_SESSION['user_name'] = $user['user_name']; // セッションにユーザー名を格納
            header("Location: main.php"); // ログイン成功後にmain.phpにリダイレクト
            exit;
        } else {
            $error_message = "無効なユーザー名またはパスワードです。";
        }
    } catch (PDOException $e) {
        echo "データベース接続エラー: " . $e->getMessage();
    }
}
?>
<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="styles.css"> <!-- CSSファイルをリンク -->
</head>
<body>
<div class="container">
    <h1>ログイン</h1>
<form action="" method="post">
    <p>
        <label for="user_name">ユーザー名:</label>
        <input type="text" name="user_name" required />
    </p>
    <p>
        <label for="password">パスワード:</label>
        <input type="password" name="password" required />
    </p>
    <div class="button-container">
        <button type="submit">ログイン</button>
    </div>
</form>
<?php if (isset($error_message)): ?>
    <p class="error-message"><?php echo $error_message; ?></p>
<?php endif; ?>
</div>

</body>
</html>

<?php
// 出力バッファをフラッシュして終了
ob_end_flush();
