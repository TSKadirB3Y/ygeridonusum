<?php
session_start();
require_once 'db.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

$chat_user_id = isset($_GET['chat_user_id']) ? intval($_GET['chat_user_id']) : 0;
$last_message_time = isset($_GET['last_message_time']) ? $_GET['last_message_time'] : null;

if ($chat_user_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı']);
    exit();
}

try {
    // Yeni mesajları getir
    $sql = "
        SELECT m.*, u.profile_picture
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE (
            (m.sender_id = ? AND m.receiver_id = ?) OR
            (m.sender_id = ? AND m.receiver_id = ?)
        )
        " . ($last_message_time ? "AND m.created_at > ?" : "") . "
        ORDER BY m.created_at ASC
    ";
    
    $params = [
        $_SESSION['user_id'],
        $chat_user_id,
        $chat_user_id,
        $_SESSION['user_id']
    ];

    if ($last_message_time) {
        $params[] = $last_message_time;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Başarılı yanıt döndür
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (PDOException $e) {
    // Hata durumunda
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Mesajlar alınamadı',
        'error' => $e->getMessage()
    ]);
} 