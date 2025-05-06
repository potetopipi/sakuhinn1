<?php
session_start();

$pdo = new PDO('sqlite:kadai.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 管理者チェック（仮の例。必要に応じて変更してください）
if (!isset($_SESSION['user_name']) || $_SESSION['user_name'] !== 'admin') {
    echo "アクセス権限がありません。";
    exit;
}

// ラベル更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $label = $_POST['label'];

    $stmt = $pdo->prepare("UPDATE products SET label = :label WHERE id = :id");
    $stmt->execute([
        ':label' => $label,
        ':id' => $product_id
    ]);
}

// 商品一覧を取得
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品ラベル編集</title>
    <style>
        table { border-collapse: collapse; width: 80%; margin: 20px auto; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        select, button { padding: 5px 10px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">商品ラベル編集</h2>
    <table>
        <tr>
            <th>商品名</th>
            <th>現在のラベル</th>
            <th>変更</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['label']) ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <select name="label">
                        <option value="">なし</option>
                        <option value="SEAL" <?= $product['label'] === 'SEAL' ? 'selected' : '' ?>>SEAL</option>
                        <option value="NEW" <?= $product['label'] === 'NEW' ? 'selected' : '' ?>>NEW</option>
                        <option value="LIMITED" <?= $product['label'] === 'LIMITED' ? 'selected' : '' ?>>LIMITED</option>
                    </select>
                    <button type="submit">更新</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p style="text-align:center;"><a href="menu.php">メニューに戻る</a></p>
</body>
</html>
