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

    $user_id = isset($_GET['id']) ? $_GET['id'] : null;

    if ($user_id) {
        $stmt = $pdo->prepare('SELECT user_name, password, profile_image FROM users WHERE id = :id');
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
    } else {
        echo "ユーザーIDが指定されていません。";
        exit;
    }

    if (!$user) {
        echo "ユーザー情報が見つかりません。";
        exit;
    }
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['new_username'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ユーザーネーム更新
    if (!empty($new_username) && $new_username !== $user['user_name']) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_name = :new_username');
        $stmt->execute(['new_username' => $new_username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'そのユーザーネームは既に使用されています。';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET user_name = :new_username WHERE id = :user_id');
            $stmt->execute(['new_username' => $new_username, 'user_id' => $user_id]);
            $_SESSION['user_name'] = $new_username;
            $success = 'ユーザーネームが更新されました。';
        }
    }

    // パスワード更新
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'すべてのパスワード欄を入力してください。';
        } elseif ($current_password !== $user['password']) {
            $error = '現在のパスワードが正しくありません。';
        } elseif ($new_password !== $confirm_password) {
            $error = '新しいパスワードと確認用パスワードが一致しません。';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password = :new_password WHERE id = :user_id');
            $stmt->execute(['new_password' => $new_password, 'user_id' => $user_id]);
            $success .= ' パスワードが更新されました。';
        }
    }

    // プロフィール画像のアップロード
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $filename = basename($_FILES['profile_image']['name']);
        $target_dir = "uploads/";
        $target_file = $target_dir . time() . "_" . $filename;

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $relative_path = basename($target_file);
            $stmt = $pdo->prepare('UPDATE users SET profile_image = :image WHERE id = :id');
            $stmt->execute(['image' => $relative_path, 'id' => $user_id]);
            $success .= ' プロフィール画像が更新されました。';
        } else {
            $error = '画像のアップロードに失敗しました。';
        }
    }
}
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール変更</title>
    <link rel="stylesheet" href="css/change_profile.css">
</head>
<body>
    <h1>プロフィール変更</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <p>
            <label for="new_username">新しいユーザーネーム:</label><br>
            <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?>">
        </p>

        <p>
            <label for="current_password">現在のパスワード:</label><br>
            <input type="password" id="current_password" name="current_password">
        </p>
        <p>
            <label for="new_password">新しいパスワード:</label><br>
            <input type="password" id="new_password" name="new_password">
        </p>
        <p>
            <label for="confirm_password">新しいパスワード（確認用）:</label><br>
            <input type="password" id="confirm_password" name="confirm_password">
        </p>

        <p>
            <label for="profile_image">プロフィール画像のアップロード:</label><br>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
        </p>

        <p><button type="submit">更新</button></p>
    </form>

    <p><a href="profile.php">戻る</a></p>
</body>
</html>
