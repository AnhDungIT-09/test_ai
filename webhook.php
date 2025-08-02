<?php
// webhook.php

// Thiết lập thông tin kết nối DB
$db = new PDO("mysql:host=localhost;dbname=zalo_chat", "root", "");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['message']['text']) || !isset($input['sender']['id'])) {
    http_response_code(400);
    exit('Invalid payload');
}

$message = $input['message']['text'];
$user_id = $input['sender']['id'];

if (is_handled_by_staff($db, $user_id)) {
    exit; // Nhân viên đã xử lý, không phản hồi nữa
} else {
    $reply = get_gpt_reply($message);
    log_message($db, $user_id, 'gpt', $reply);
    send_message_to_zalo($user_id, $reply);
}

function is_handled_by_staff($db, $user_id) {
    $stmt = $db->prepare("SELECT 1 FROM messages WHERE user_id = ? AND sender = 'staff' LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch() !== false;
}

function log_message($db, $user_id, $sender, $message) {
    $stmt = $db->prepare("INSERT INTO messages (user_id, sender, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $sender, $message]);
}

function get_gpt_reply($message) {
    $api_key = 'YOUR_OPENAI_API_KEY';
    $data = [
        "model" => "gpt-4",
        "messages" => [["role" => "user", "content" => $message]]
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/json\r\nAuthorization: Bearer $api_key\r\n",
            "method"  => "POST",
            "content" => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents("https://api.openai.com/v1/chat/completions", false, $context);
    $response = json_decode($result, true);
    return $response['choices'][0]['message']['content'] ?? 'Xin lỗi, tôi không thể trả lời câu hỏi đó.';
}

function send_message_to_zalo($user_id, $message) {
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
?>