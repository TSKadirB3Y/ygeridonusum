<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

if (!isset($_POST['receiver_id']) || !isset($_POST['message_content'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message_content = htmlspecialchars($_POST['message_content']);

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message_content]);
    
    $message_id = $pdo->lastInsertId();
    
    // Yeni mesajın bilgilerini al
    $stmt = $pdo->prepare("SELECT id, content, created_at FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($message) {
        // Mesaj zamanını formatla
        $timestamp = strtotime($message['created_at']);
        $formatted_time = date('H:i', $timestamp);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'message_id' => $message['id'],
                'message' => $message['content'],
                'created_at' => $formatted_time
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mesaj kaydedilemedi']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
} 