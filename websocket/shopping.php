<?php
session_start();

// PDOでSQLite3に接続
$pdo = new PDO('sqlite:kadai.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// セッション変数が設定されていない場合、ログインページにリダイレクト
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

// ユーザーの所持金をデータベースから取得 (脆弱な方法)
$stmt = $pdo->query("SELECT money FROM users WHERE user_name = '" . $_SESSION['user_name'] . "'");
$user = $stmt->fetch();

$user_money = $user ? $user['money'] : 0;

// 商品を取得 (修正後の検索処理)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM products WHERE id BETWEEN 1 AND 8"; // IDが1～8の商品をデフォルトで取得
if (!empty($search)) {
    $sql .= " OR name LIKE '%$search%'"; // 検索時に任意の名前が一致する商品も追加 (SQLインジェクションの脆弱性)
}
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// カートに商品を追加する処理
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['add_to_cart'];
    $stmt = $pdo->query("SELECT * FROM products WHERE id = $product_id"); // SQLインジェクションの脆弱性
    $product = $stmt->fetch();

    if ($product) {
        $_SESSION['cart'][] = $product; // カートデータをそのままセッションに格納 (セッション固定攻撃の脆弱性)
        header("Location: shopping.php?cart=added");
        exit;
    }
}


?>

<?php
// 購入メッセージがセッションに保存されていれば表示
if (isset($_SESSION['purchase_message'])) {
    echo "<p style='color: green;'>" . $_SESSION['purchase_message'] . "</p>";
    // メッセージを表示後に削除
    unset($_SESSION['purchase_message']);
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ショッピングサイト</title>
    <link rel="stylesheet" href="css/shopping.css">
</head>

<body>
    <div class="container">
        <h1>ショッピングサイト</h1>
        <h3 class="user-info">
            User: <?= $_SESSION['user_name'] ?> <!-- XSSの脆弱性 -->
            <span class="user-money">所持金: ¥<?= $user_money ?></span> <!-- XSSの脆弱性 -->
        </h3>

        <div style="position: absolute; top: 10px; right: 10px;">
            <a href="cart.php" style="text-decoration: none; color: white;">
                🛒 カート (<?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>)
            </a>
        </div>

        <form class="search-bar" method="GET" action="shopping.php">
            <input type="text" name="search" placeholder="検索" style="width: 300px; padding: 10px;">
            <button type="submit" style="padding: 10px;">検索</button>
        </form>
        <?php if ($_SESSION['user_name'] === 'admin'): ?>
                <form method="GET" action="edit_label.php">
                    <button type="submit" style="padding: 10px;">ラベル編集</button>
                </form>
            <?php endif; ?>
        <div class="result">
            <?php if (!empty($search)): ?>
                <p>検索結果: <strong><?= $search ?></strong></p> <!-- XSSの脆弱性 -->
            <?php endif; ?>
        </div>

        <div class="product-list">
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <!-- 🔽 ラベル表示を追加 -->
                    <?php if (!empty($product['label'])): ?>
                        <div class="product-label"><?= htmlspecialchars($product['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <h2>商品名: <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>価格: <?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?>円</p>
                    <form method="POST">
                        <button type="submit" name="add_to_cart" value="<?= $product['id'] ?>">カートに追加</button>
                    </form>
                </div>

            <?php endforeach; ?>
        </div>

        <p><a href="menu.php" class="menu">戻る</a></p>
</body>

</html>