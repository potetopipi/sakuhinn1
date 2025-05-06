<?php
// エラーメッセージの表示を有効化
ini_set('display_errors', true);
error_reporting(E_ALL);

// PDOでSQLite3に接続
$pdo = new PDO('sqlite:kadai.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 入力データを取得
    $user_name = filter_input(INPUT_POST, 'user_name');
    $password = filter_input(INPUT_POST, 'password');
    $confirm_password = filter_input(INPUT_POST, 'confirm_password'); // 確認用パスワードを取得

    // 確認用パスワードが一致するかチェック
    if ($password !== $confirm_password) {
        $error_message = "パスワードと確認用パスワードが一致しません。";
    } else {
        // ユーザーの重複をチェック
        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_name = :user_name');
        $stmt->execute(['user_name' => $user_name]);
        if ($stmt->fetch()) {
            $error_message = "そのユーザー名は既に使用されています。";
        } else {
            // ユーザーをデータベースに追加（初期所持金を10,000円に設定）
            $sql = 'INSERT INTO users (user_name, password, money) VALUES (:user_name, :password, :money)';
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute(['user_name' => $user_name, 'password' => $password, 'money' => 10000])) {
                header("Location: login.php"); // 登録成功後にログインページへリダイレクト
                exit;
            } else {
                $error_message = "エラーが発生しました。";
            }
        }
    }
}
?>

<!DOCTYPE HTML>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>サインアップ</title>
    <link rel="stylesheet" href="css/signup.css">
</head>

<body>
    <div>
        <h1>サインアップ</h1>
        <form action="" method="post">
            <p>
                <label for="user_name">ユーザー名:</label>
                <input type="text" name="user_name" required />
            </p>
            <p>
                <label for="password">パスワード:</label>
                <input type="password" name="password" required />
            </p>
            <p>
                <label for="confirm_password">パスワード（確認用）:</label><br>
                <input type="password" id="confirm_password" name="confirm_password">
            </p>
            <p><button type="submit">登録</button></p>
        </form>
        <?php if (isset($error_message)) {
            echo "<p style='color:red;'>$error_message</p>";
        } ?>
    </div>
    <p><a href="login.php">戻る</a></p>

</body>

</html>
