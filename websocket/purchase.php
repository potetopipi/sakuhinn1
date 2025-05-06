<?php
session_start();

if (!isset($_SESSION['user_name']) || empty($_SESSION['cart'])) {
    header("Location: shopping.php");
    exit;
}

$pdo = new PDO('sqlite:kadai.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

try {
    $pdo->beginTransaction();

    $user_name = $_SESSION['user_name'];
    $cart_items = $_SESSION['cart'];
    $total_price = array_sum(array_column($cart_items, 'price'));

    // ユーザーの所持金を更新
    $stmt = $pdo->prepare("UPDATE users SET money = money - :total_price WHERE user_name = :user_name");
    $stmt->execute(['total_price' => $total_price, 'user_name' => $user_name]);

    // 購入履歴を保存
    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO messages (user_name, content, product_id) VALUES (:user_name, :content, :product_id)");
        $stmt->execute([
            'user_name' => $user_name,
            'content' => '購入した商品: ' . $item['name'] . '（' . $item['price'] . '円）',
            'product_id' => $item['id'] // 商品IDを追加
        ]);
    }    

    $pdo->commit();

    // カートを空にする
    $_SESSION['cart'] = [];

    // 購入成功メッセージをセッションに保存
    $_SESSION['purchase_message'] = '購入しました！';

    header("Location: shopping.php?purchase=success");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: shopping.php?purchase=error");
    exit;
}
?>
