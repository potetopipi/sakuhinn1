<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // ユーザーからの入力を取得
        $message = $_POST['message'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        // 入力された内容をシェルコマンドに渡す
        // このコードはOSインジェクションを発生させる可能性があります
        $output = shell_exec("echo $message");

    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>問い合わせフォーム</title>
    <link rel="stylesheet" href="css/toiawase.css">
</head>
<body>

    <div class="form-container">
        <form method="POST">
            <h1>問い合わせ</h1>
            <label for="name">名前:</label>
            <input type="text" name="name" id="name" required><br>
            <label for="email">メールアドレス:</label>
            <input type="email" name="email" id="email" required><br>
            <label for="message">メッセージ:</label>
            <input type="text" name="message" id="message" required><br>
            <button type="submit">送信</button>
            
	</form>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST') : ?>
            <div class="output">
                <p>以下の内容で送信しました</p>
                <h3>送信内容:</h3>
                <p>名前: <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></p>
                <p>メールアドレス: <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                <p>メッセージ:<?php echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <a href="menu.php" class="menu">戻る</a>
</body>
</html>
