<?php
// エラーメッセージの表示を有効化
ini_set('display_errors', true);
error_reporting(E_ALL);

// 出力バッファリングを有効にする
ob_start();

// セッションスタート
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 変数に格納（サニタイズなし）
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    try {
        // SQLiteに接続（ファイル名は適宜変更）
        $pdo = new PDO('sqlite:kadai.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 🔥 プリペアドステートメントを使わず、直接変数を埋め込む（SQLインジェクションの温床）
        $sql = "SELECT user_name, password FROM users WHERE user_name = '$user_name' AND password = '$password'";
        $stmt = $pdo->query($sql);  // 🔥 queryで直接実行
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_name'] = $user['user_name'];

            // セッションIDを取得
            $sessionId = session_id();
            $sessionData = serialize($_SESSION);
            $timestamp = time();

            // セッション保存（セキュアではないが保持）
            $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (:id, :data, :ts)");
            $stmt->execute([
                ':id' => $sessionId,
                ':data' => $sessionData,
                ':ts' => $timestamp
            ]);

            // ログイン成功
            header("Location: menu.php");
            exit;
        } else {
            $error_message = "無効なユーザー名またはパスワードです。<a href='signup.php'>サインアップ</a>してください。";
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
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div>
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
            <p><button type="submit">ログイン</button></p>
        </form>
        <p>アカウントをお持ちでない方は、<a href="signup.php">こちらから登録</a>してください。</p>
        <?php if (isset($error_message)) { echo "<p style='color:red;'>$error_message</p>"; } ?>
    </div>
</body>
</html>

<?php
// 出力バッファをフラッシュして終了
ob_end_flush();
?>
