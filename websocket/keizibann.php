<?php
session_start();

// セッション変数が設定されていない場合、ログインページにリダイレクト
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE HTML>
<html lang="ja">

<head>
    <title>リアルタイム掲示板</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/keizibann.css">
</head>

<body>
    <h1>掲示板へようこそ！</h1>
    <div class="welcome-container">
        <!-- プロフィール画像の表示 -->
        <?php
        try {
            $pdo = new PDO('sqlite:kadai.db', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $sql = 'SELECT profile_image FROM users WHERE user_name = :user_name';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_name' => $_SESSION['user_name']]);
            $user = $stmt->fetch();

            if (!empty($user['profile_image'])) {
                echo '<img src="uploads/' . htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8') . '" alt="プロフィール画像" class="profile-img">';
            }
        } catch (PDOException $e) {
            echo "データベース接続エラー: " . $e->getMessage();
        }
        ?>
        <p>ようこそ、<?php echo $_SESSION['user_name']; ?>さん。</p>
    </div>

    <!-- メッセージ表示エリア -->
    <div id="msg_log" class="msg-log"></div>

    <!-- メッセージ入力フォーム -->
    <textarea id="content" placeholder="メッセージを入力してください"></textarea><br>
    <button onclick="sendMessage()">送信</button>

    <script type="text/javascript">
        // WebSocketの接続設定
        var conn = new WebSocket('ws://localhost:7010');
        var user_name = "<?php echo $_SESSION['user_name']; ?>"; // PHPでセッションからユーザー名を取得
        var sessionId = "<?php echo session_id(); ?>";
        
    // WebSocketが接続された時の処理
    conn.onopen = function(e) {
        console.log("接続が確立されました!"); // 接続が成功した場合にログを出力
        
        // サーバーにセッションIDを送信して認証する
        conn.send(JSON.stringify({
            type: "auth",
            session_id: sessionId
        }));
    };
        conn.onmessage = function(e) {
            var data = JSON.parse(e.data); // 受け取ったメッセージをJSON形式からオブジェクトに変換
            var msg_log = document.getElementById("msg_log"); // メッセージログの表示エリアを取得

            // メッセージタイプが"reply"でない場合のみメッセージを表示
            if (data.type !== 'reply') {
                var messageDiv = document.createElement("div");
                messageDiv.className = 'message';

                // メッセージIDをdivのIDに設定（メッセージごとに一意なID）
                messageDiv.id = 'message_' + data.id;


                // プロフィール画像がある場合のみ表示
                if (data.profile_image) {
                    var profileImg = document.createElement('img');
                    profileImg.src = data.profile_image; // URLにbaseを追加しない
                    profileImg.alt = 'プロフィール画像';
                    profileImg.classList.add('profile-image');
                    messageDiv.appendChild(profileImg);
                }


                // ISO 8601形式の日付を日本時間に変換
                var createdAt = new Date(data.created_at);
                var options = {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'Asia/Tokyo', // 日本時間に設定
                    hour12: false
                };
                var formattedTime = new Intl.DateTimeFormat('ja-JP', options).format(createdAt);

                // メッセージ内容を設定
                messageDiv.innerHTML += "<strong>" + data.user_name + "</strong>: " + data.content + "<br><small>" + formattedTime + "</small><br>";

                // 返信ボタンを作成
                var replyButton = document.createElement("button");
                replyButton.innerText = "返信";
                replyButton.onclick = function() {
                    toggleReplyForm(data.id); // 返信フォームを表示・非表示に切り替える
                };
                messageDiv.appendChild(replyButton);

                // 消去ボタンを作成
                var deleteButton = document.createElement("button");
                deleteButton.innerText = "消去";
                deleteButton.onclick = function() {
                    deleteMessage(data.id); // メッセージを削除
                };
                messageDiv.appendChild(deleteButton);

                // 返信用のコンテナを作成
                var replyContainer = document.createElement("div");
                replyContainer.className = 'reply-container';
                replyContainer.id = 'reply_container_' + data.id;

                // 返信内容入力用のテキストエリア
                var replyInput = document.createElement("textarea");
                replyInput.className = 'reply-input';
                replyInput.placeholder = '返信内容を入力';

                // 返信送信ボタン
                var replySubmitButton = document.createElement("button");
                replySubmitButton.innerText = "返信を送信";
                replySubmitButton.onclick = function() {
                    sendReply(data.id, replyInput.value); // 返信を送信
                };

                // 返信フォームを返信コンテナに追加
                replyContainer.appendChild(replyInput);
                replyContainer.appendChild(replySubmitButton);

                // 返信リスト用のdivを作成
                var replyList = document.createElement("div");
                replyList.className = 'replies-list';
                replyList.id = 'replies_' + data.id;
                replyContainer.appendChild(replyList);

                // すでに送信された返信を表示
                if (data.replies && data.replies.length > 0) {
                    data.replies.forEach(function(reply) {
                        var replyDiv = document.createElement("div");
                        replyDiv.className = 'reply';
                        replyDiv.id = 'reply_' + reply.id;
                        replyDiv.innerHTML = "<strong>" + reply.user_name + "</strong>: " + reply.content;

                        // 返信消去ボタン
                        var deleteReplyButton = document.createElement("button");
                        deleteReplyButton.innerText = "返信消去";
                        deleteReplyButton.onclick = function() {
                            deleteReply(reply.id, data.id); // 返信を削除
                        };
                        replyDiv.appendChild(deleteReplyButton);

                        // 返信リストに返信を追加
                        replyList.appendChild(replyDiv);
                    });
                }

                // メッセージと返信フォームを表示エリアに追加
                messageDiv.appendChild(replyContainer);
                msg_log.appendChild(messageDiv);
                msg_log.scrollTop = msg_log.scrollHeight; // 新しいメッセージが下にスクロールされるように
            } else {
                // 返信メッセージの場合
                var replyDiv = document.createElement("div");
                replyDiv.className = 'reply';
                replyDiv.id = 'reply_' + data.reply_id;
                replyDiv.innerHTML = "<strong>" + data.user_name + "</strong>: " + data.content;

                // 返信消去ボタン
                var deleteReplyButton = document.createElement("button");
                deleteReplyButton.innerText = "返信消去";
                deleteReplyButton.onclick = function() {
                    deleteReply(data.reply_id, data.message_id); // 返信を削除
                };
                replyDiv.appendChild(deleteReplyButton);

                // 返信リストに返信を追加
                document.getElementById('replies_' + data.message_id).appendChild(replyDiv);
            }
        }
        // メッセージ送信関数
        function sendMessage() {
            var content = document.getElementById("content").value; // メッセージ内容を取得

            // メッセージ内容が空の場合にアラートを表示
            if (content.trim() === "") {
                alert("メッセージが空です。");
                return; // 何も送信せずに終了
            }

            var data = {
                'user_name': user_name,
                'content': content,
                'created_at': new Date().toISOString(),
                'type': 'message' // メッセージタイプを設定
            };
            conn.send(JSON.stringify(data)); // メッセージをWebSocketで送信
            document.getElementById("content").value = ''; // 入力欄をリセット
        }

        // 返信フォームの表示・非表示を切り替える関数
        function toggleReplyForm(messageId) {
            var replyContainer = document.getElementById('reply_container_' + messageId);
            if (replyContainer.style.display === "none" || replyContainer.style.display === "") {
                replyContainer.style.display = "block"; // 表示する
            } else {
                replyContainer.style.display = "none"; // 非表示にする
            }
        }

        // 返信送信関数
        function sendReply(messageId, replyContent) {
            var replyContainer = document.getElementById('reply_container_' + messageId);
            var replyInput = replyContainer.querySelector('textarea'); // テキストエリアを取得

            console.log("textareaの内容:", replyInput.value); // textareaの内容を表示

            console.log("返信内容（trim前）:", replyContent); // trim前の内容
            console.log("返信内容（trim後）:", replyContent.trim()); // trim後の内容

            if (replyContent.trim() === "") {
                alert("返信内容が空です。");
                return;
            }

            var replyData = {
                'user_name': user_name,
                'content': replyContent,
                'created_at': new Date().toISOString(),
                'message_id': messageId,
                'type': 'reply'
            };
            conn.send(JSON.stringify(replyData)); // 返信をWebSocketで送信
            replyContainer.style.display = "none"; // 返信フォームを非表示にする
            replyInput.value = ''; // 入力欄をクリア
        }

        // メッセージ削除関数
        function deleteMessage(messageId) {
            var deleteData = {
                'message_id': messageId,
                'type': 'delete' // 削除タイプを設定
            };
            conn.send(JSON.stringify(deleteData)); // メッセージ削除のリクエストを送信

            // メッセージをDOMから削除
            var messageDiv = document.getElementById('message_' + messageId);
            if (messageDiv) {
                messageDiv.remove();
            }
        }

        // 返信削除関数
        function deleteReply(replyId, messageId) {
            var deleteData = {
                'reply_id': replyId,
                'type': 'delete_reply' // 返信削除タイプを設定
            };
            conn.send(JSON.stringify(deleteData)); // 返信削除のリクエストを送信

            // 返信をDOMから削除
            var replyDiv = document.getElementById('reply_' + replyId);
            if (replyDiv) {
                replyDiv.remove();
            }
        }
    </script>
    <a href="menu.php" class="menu">戻る</a>
</body>

</html>