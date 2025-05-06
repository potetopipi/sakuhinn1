<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    private $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->db = new \PDO('sqlite:kadai.db');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        echo "WebSocket server started...\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection: ({$conn->resourceId})\n";
        $this->sendAllMessages($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received: $msg\n";
        
        // JSONをデコード
        $data = json_decode($msg, true);

        // デコード結果が正しいかチェック
        if (!$data || !isset($data['type'])) {
            return;
        }

    // セッションIDが空または未設定の場合、処理を無視
    if (empty($data['session_id'])) {
        $response = [
            'type' => 'error',
            'message' => 'Session ID is missing or invalid'
        ];
        $from->send(json_encode($response));
        $from->close(); // 不正なセッションの場合は接続を閉じる
        return;
    }

    if ($data['type'] === 'auth') {
        // 認証処理
        $clientSessionId = $data['session_id'];

        // セッションIDが有効かどうかを確認
        try {
            $pdo = new PDO('sqlite:kadai.db', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // セッションIDがデータベースに存在するかを確認
            $sql = "SELECT * FROM sessions WHERE id = :session_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':session_id' => $clientSessionId]);
            $session = $stmt->fetch();

            if ($session) {
                // 認証成功
                $response = ['type' => 'auth_response', 'status' => 'success', 'message' => 'Session ID received'];
                $from->send(json_encode($response));

                // クライアントのユーザー名などを記録（認証後の処理）
            } else {
                // 認証失敗
                $response = ['type' => 'auth_response', 'status' => 'failure', 'message' => 'Invalid session ID'];
                $from->send(json_encode($response));
                $from->close(); // 接続を閉じる
            }
        } catch (PDOException $e) {
            echo "データベース接続エラー: " . $e->getMessage();
        }
    }

        // profile_imageのURLがエスケープされたスラッシュを含む場合は修正
        if (isset($data['profile_image'])) {
            $data['profile_image'] = str_replace('\/', '/', $data['profile_image']);
        }

        // メッセージタイプごとの処理
        if ($data['type'] === 'message') {
            $stmt = $this->db->prepare("INSERT INTO contents (user_name, content, created_at) VALUES (:user_name, :content, :created_at)");
            $stmt->execute([
                ':user_name' => $data['user_name'],
                ':content' => $data['content'], // XSS対策なし（注意）
                ':created_at' => $data['created_at']
            ]);
            $data['id'] = $this->db->lastInsertId();
            $data['profile_image'] = $this->getProfileImage($data['user_name']);
        } elseif ($data['type'] === 'reply') {
            $stmt = $this->db->prepare("INSERT INTO replies (message_id, user_name, content, created_at) VALUES (:message_id, :user_name, :content, :created_at)");
            $stmt->execute([
                ':message_id' => $data['message_id'],
                ':user_name' => $data['user_name'],
                ':content' => $data['content'], // XSS対策なし（注意）
                ':created_at' => $data['created_at']
            ]);
            $data['reply_id'] = $this->db->lastInsertId();
            $data['profile_image'] = $this->getProfileImage($data['user_name']);
        } elseif ($data['type'] === 'delete') {
            if (isset($data['message_id'])) {
                $stmt = $this->db->prepare("DELETE FROM contents WHERE id = :id");
                $stmt->execute([':id' => $data['message_id']]);
            }
        } elseif ($data['type'] === 'delete_reply') {
            if (isset($data['reply_id'])) {
                $stmt = $this->db->prepare("DELETE FROM replies WHERE id = :id");
                $stmt->execute([':id' => $data['reply_id']]);
            }
        }

        // クライアントにメッセージをブロードキャスト
        foreach ($this->clients as $client) {
            $client->send(json_encode($data, JSON_UNESCAPED_SLASHES));
        }

        echo "Broadcast: " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
    }



    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection ({$conn->resourceId}) closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function sendAllMessages(ConnectionInterface $conn)
    {
        $stmt = $this->db->query("SELECT * FROM contents");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($messages as $message) {
            $message['created_at'] = (new DateTime($message['created_at']))->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');
            $message['profile_image'] = $this->getProfileImage($message['user_name']);

            $stmt = $this->db->prepare("SELECT * FROM replies WHERE message_id = :message_id");
            $stmt->execute([':message_id' => $message['id']]);
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($replies as &$reply) {
                $reply['created_at'] = (new DateTime($reply['created_at']))->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');
                $reply['profile_image'] = $this->getProfileImage($reply['user_name']);
            }

            $message['replies'] = $replies;

            $conn->send(json_encode($message));
        }
    }

    private function getProfileImage($user_name)
    {
        $stmt = $this->db->prepare("SELECT profile_image FROM users WHERE user_name = :user_name");
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // デバッグ出力を追加
        if ($result && $result['profile_image']) {
            $imageUrl = "http://localhost:5010/uploads/" . $result['profile_image'];
            echo "Profile image URL: " . $imageUrl . "\n"; // デバッグ用
            return $imageUrl;
        }

        return null; // 画像がない場合はnull
    }
}

$server = Ratchet\Server\IoServer::factory(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            new ChatServer()
        )
    ),
    7010
);

$server->run();
