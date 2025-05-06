<?php
// セッション開始
session_start();

// セッション変数を全て解除
session_unset();

// セッション自体を破棄
session_destroy();

// セッションIDを保存するクッキーを削除
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// ログインページ（またはホームページ）にリダイレクト
header("Location: login.php"); // 必要に応じて適切なページに変更してください
exit;
?>
