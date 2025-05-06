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

    // PDOでSQLite3に接続
    try {
        $pdo = new PDO('sqlite:kadai.db', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // SQLインジェクションが可能なクエリ（セキュリティ上、プリペアドステートメントを使用した方が良い）
        $sql = "SELECT user_name, password FROM users WHERE user_name = :user_name AND password = :password";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_name' => $user_name,
            ':password' => $password
        ]);
        $user = $stmt->fetch();

        // ログイン処理
        if ($user) {
            $_SESSION['user_name'] = $user['user_name']; // セッションにユーザー名を格納

            // セッションIDを取得
            $sessionId = session_id();
            $sessionData = serialize($_SESSION);
            $timestamp = time();

            // セッションIDとデータをsessionsテーブルに保存
            $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (:id, :data, :ts)");
            $stmt->execute([
                ':id' => $sessionId,
                ':data' => $sessionData,
                ':ts' => $timestamp
            ]);

            // ログイン成功後にmenu.phpにリダイレクト
            header("Location: menu.php");
            exit;
        } else {
            $error_message = "無効なユーザー名またはパスワードです。アカウントがない場合は、<a href='signup.php'>サインアップ</a>してください。";
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
