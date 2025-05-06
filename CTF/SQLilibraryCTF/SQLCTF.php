<!DOCTYPE html>
<html>
<head>
    <title>CTF図書館</title>
</head>
<body>
<h1>CTF図書館</h1>
<p>CTF図書館へようこそ！</p>
<p>ここではCTFに関する書籍を閲覧することができます。</p>
<p>書籍の一覧は以下の通りです。</p>

<?php
    // 並び替えの条件を取得（未サニタイズ、脆弱性を意図的に作成）
    $sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'title';  // デフォルトは 'title'
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';  // デフォルトは昇順 ('ASC')

    // データベース接続
    $db = new SQLite3('ctf_library.db');
    
    // SQL文を動的に構築（脆弱性あり）
    $query = "SELECT * FROM books ORDER BY $sort_column $sort_order LIMIT 20";  // LIMITで最大20件まで表示
    $result = $db->query($query);
?>

<!-- 並び替えのボタン -->
<form method="get" action="">
    <button type="submit" name="sort_column" value="title">
        書籍名 (<?php echo $sort_order == 'ASC' ? '昇順' : '降順'; ?>)
    </button>
    <button type="submit" name="sort_column" value="id">
        No (<?php echo $sort_order == 'ASC' ? '昇順' : '降順'; ?>)
    </button>
    <input type="hidden" name="sort_order" value="<?php echo $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>" />
</form>

<!-- 書籍一覧のテーブル -->
<table border="2">
    <tr>
        <th>書籍名</th>
        <th>No</th>
        <th>Flag</th>
    </tr>

    <?php
    while ($row = $result->fetchArray()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['flag'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>

</body>
</html>
