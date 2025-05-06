<?php
session_start();

// PDOでSQLite3に接続
$pdo = new PDO('sqlite:kadai.db', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// セッションがない場合、ログインページにリダイレクト
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

// POSTからuser_nameを取得（指定されていれば使う）
$user_name = $_POST['user_name'] ?? $_SESSION['user_name'];

// ❌ prepareもバインドも使わず、直接埋め込み（脆弱）
$sql = "SELECT money, profile_image FROM users WHERE user_name = '$user_name'";
$stmt = $pdo->query($sql);
$user = $stmt->fetch();

if ($user) {
    $user_money = $user['money'];
    $profile_image = $user['profile_image'];
} else {
    $user_money = 0;
    $profile_image = null;
}

// 人気商品の取得（ここはそのまま）
$productStmt = $pdo->prepare("
    SELECT p.name AS product_name, COUNT(m.id) AS purchase_count, p.image_url, p.price, p.label
    FROM messages m
    JOIN products p ON m.product_id = p.id
    GROUP BY m.product_id
    ORDER BY purchase_count DESC
    LIMIT 5
");
$productStmt->execute();
$top_products = $productStmt->fetchAll();
?>

<!DOCTYPE HTML>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>メニュー</title>
    <link rel="stylesheet" href="css/menu.css">
</head>

<body>
    <header>
        <h1 class="user-info">
            <?php if (!empty($profile_image)): ?>
                <a href="profile.php" class="user-link">
                    <img src="uploads/<?php echo htmlspecialchars($profile_image, ENT_QUOTES, 'UTF-8'); ?>" alt="プロフィール画像" width="40" height="40" style="border-radius:50%; vertical-align: middle; margin-right: 10px;">
                </a>
            <?php endif; ?>
            User: <a href="profile.php" class="user-link"><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></a>
            <span class="user-money">所持金: ¥<?php echo number_format($user_money); ?></span>
        </h1>
        <nav class="nav-links">
            <a href="shopping.php" class="btn">ショップ</a>
            <a href="purchase_history.php" class="btn">購入履歴</a>
            <a href="keizibann.php" class="btn">掲示板</a>
            <a href="toiawase.php" class="btn">問い合わせ</a>
            <a href="logout.php" class="btn">ログアウト</a>
        </nav>
    </header>

    <h1 style="color: white;">Welcome to shoppingsite</h1>
    <p style="color: white;">-----Choose your favorite items and enjoy shopping!-----</p>

    <!-- メイン画像 -->
    <div class="main-visual-container">
        <img src="image/main.png" alt="メインビジュアル" class="main-image">

        <!-- BUY NOWボタン -->
        <a href="shopping.php" class="buy-now-button">BUY NOW</a>

        <!-- カートボタン -->
        <a href="cart.php" class="cart-button">
            <img src="image/cart.png" alt="カート" class="cart-icon">
        </a>
    </div>


    <!-- 商品セクション -->
    <div class="top-products">
        <h2>★人気の購入商品★</h2>
        <div class="scroll-container">
            <button class="scroll-arrow left-arrow">&#9664;</button>
            <div class="products-container">
                <?php foreach ($top_products as $product): ?>

                    <div class="product-item">
                        <?php if (!empty($product['label'])): ?>
                            <div class="product-label"><?= htmlspecialchars($product['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>" class="product-image">
                        <h3>商品名: <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p>価格: ¥<?= number_format($product['price']) ?>円</p>
                        <p>購入回数: <?= $product['purchase_count'] ?> 回</p>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="scroll-arrow right-arrow">&#9654;</button>
        </div>
    </div>

    <script>
        // スクロールボタンのクリックイベント
        document.querySelector('.left-arrow').addEventListener('click', function() {
            document.querySelector('.products-container').scrollBy({
                left: -window.innerWidth,
                behavior: 'smooth'
            });
        });

        document.querySelector('.right-arrow').addEventListener('click', function() {
            document.querySelector('.products-container').scrollBy({
                left: window.innerWidth,
                behavior: 'smooth'
            });
        });
    </script>

    <footer class="footer">@2025zeizyaku</footer>

</body>

</html>