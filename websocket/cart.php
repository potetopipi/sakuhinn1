<?php
session_start();

// カートが空でないか確認
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_items = $_SESSION['cart'];
$total_price = array_sum(array_column($cart_items, 'price'));

// 商品を削除する処理
if (isset($_POST['remove_item'])) {
    $remove_index = $_POST['remove_item'];
    // 指定されたインデックスの商品をカートから削除
    unset($_SESSION['cart'][$remove_index]);
    // カートのインデックスを再索引する
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");  // リダイレクトしてカートページを更新
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>カートの内容</title>
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <h1>🛒 カートの内容</h1>

    <?php if (empty($cart_items)): ?>
        <p>カートに商品がありません。</p>
    <?php else: ?>
        <ul>
            <?php foreach ($cart_items as $index => $item): ?>
                <li>
                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> - <?= number_format($item['price']) ?>円
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="remove_item" value="<?= $index ?>">削除</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

        <p>合計金額: <?= number_format($total_price) ?>円</p>

        <form method="POST" action="purchase.php">
            <button type="submit">購入する</button>
        </form>
    <?php endif; ?>
    <p><a href="menu.php" class="menu">戻る</a></p>
</body>
</html>
