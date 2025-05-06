<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

$pdo = new PDO('sqlite:kadai.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$user_name = $_SESSION['user_name'];

// 購入日（重複なし）
$date_stmt = $pdo->query("SELECT DISTINCT DATE(created_at) AS purchase_date FROM messages WHERE user_name = '$user_name' ORDER BY purchase_date DESC");
$dates = $date_stmt->fetchAll();

$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$purchase_history = [];

if (!empty($selected_date)) {
    // ⚠️ 脆弱性：SQLインジェクションのリスクあり（prepare等を使用していない）
    if ($selected_date === 'all') {
        $sql = "SELECT content, created_at FROM messages WHERE user_name = '$user_name' ORDER BY created_at DESC";
    } else {
        $sql = "SELECT content, created_at FROM messages WHERE user_name = '$user_name' AND DATE(created_at) = '$selected_date' ORDER BY created_at DESC";
    }
    $stmt = $pdo->query($sql);
    $purchase_history = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>購入履歴</title>
    <link rel="stylesheet" href="css/purchase_history.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($user_name); ?>さんの購入履歴</h1>

    <form method="get">
        <label>購入日を選択：</label>
        <select name="date">
            <option value="">-- 選択してください --</option>
            <option value="all" <?= $selected_date === 'all' ? 'selected' : '' ?>>一覧表示</option>
            <?php foreach ($dates as $date): ?>
                <option value="<?= htmlspecialchars($date['purchase_date']) ?>" <?= $selected_date === $date['purchase_date'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($date['purchase_date']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">表示</button>
    </form>

    <?php if (!empty($selected_date)): ?>
        <h2>
            <?= $selected_date === 'all' ? 'すべての購入履歴' : htmlspecialchars($selected_date) . ' の購入履歴' ?>
        </h2>
        <?php if (!empty($purchase_history)): ?>
            <?php foreach ($purchase_history as $item): ?>
                <div>
                    <p><?= htmlspecialchars($item['content']) ?></p>
                    <p><?= htmlspecialchars($item['created_at']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>この日には購入履歴がありません。</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="shopping.php">ショッピングページに戻る</a>
</body>
</html>
