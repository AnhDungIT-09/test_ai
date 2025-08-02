<?php
// dashboard.php

// Kết nối DB
$db = new PDO("mysql:host=localhost;dbname=zalo_chat", "root", "");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Gửi phản hồi từ nhân viên nếu có
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['reply'])) {
    $user_id = $_POST['user_id'];
    $reply = $_POST['reply'];

    // Gửi đến Zalo OA
    send_message_to_zalo($user_id, $reply);

    // Lưu vào DB
    $stmt = $db->prepare("INSERT INTO messages (user_id, sender, message, created_at) VALUES (?, 'staff', ?, NOW())");
    $stmt->execute([$user_id, $reply]);

    header("Location: dashboard.php");
    exit;
}

function send_message_to_zalo($user_id, $message)
{
    $access_token = 'YOUR_ZALO_OA_ACCESS_TOKEN';
    $data = [
        "recipient" => ["user_id" => $user_id],
        "message" => ["text" => $message]
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/json\r\nAccess-Token: $access_token\r\n",
            "method"  => "POST",
            "content" => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents("https://openapi.zalo.me/v2.0/oa/message", false, $context);
}

// Sửa lỗi ở đây
// Lấy danh sách hội thoại gần nhất
$stmt = $db->query("SELECT DISTINCT user_id, created_at FROM messages ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tạo một mảng mới chỉ chứa user_id duy nhất và đã được sắp xếp
$user_ids = array_column($users, 'user_id');

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Dashboard Tư vấn</title>
    <style>
    body {
        font-family: Arial;
        margin: 40px;
    }

    .chatbox {
        border: 1px solid #ccc;
        padding: 10px;
        margin-bottom: 30px;
    }

    .msg {
        margin: 5px 0;
    }

    .user {
        color: blue;
    }

    .gpt {
        color: green;
    }

    .staff {
        color: orange;
    }
    </style>
</head>

<body>
    <h1>Dashboard Tư vấn Khách hàng</h1>

    <?php foreach ($user_ids as $uid): ?>
    <div class="chatbox">
        <h3>Khách hàng: <?= htmlspecialchars($uid) ?></h3>
        <?php
            $stmt = $db->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC");
            $stmt->execute([$uid]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
        <?php foreach ($messages as $msg): ?>
        <div class="msg <?= $msg['sender'] ?>">
            <strong><?= ucfirst($msg['sender']) ?>:</strong> <?= nl2br(htmlspecialchars($msg['message'])) ?>
        </div>
        <?php endforeach; ?>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($uid) ?>">
            <textarea name="reply" rows="3" cols="50" placeholder="Nhập phản hồi từ nhân viên..."></textarea><br>
            <button type="submit">Gửi phản hồi</button>
        </form>
    </div>
    <?php endforeach; ?>
</body>

</html>