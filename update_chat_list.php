<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

try {
    // Mesajlaşmış kişileri ve son mesajları getir
    $chat_partners_stmt = $pdo->prepare("
        SELECT DISTINCT
            CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS user_id,
            users.first_name, users.last_name, users.profile_picture,
            (
                SELECT content 
                FROM messages m2 
                WHERE (
                    (m2.sender_id = ? AND m2.receiver_id = users.id) OR 
                    (m2.sender_id = users.id AND m2.receiver_id = ?)
                )
                ORDER BY m2.created_at DESC 
                LIMIT 1
            ) as last_message,
            (
                SELECT created_at 
                FROM messages m2 
                WHERE (
                    (m2.sender_id = ? AND m2.receiver_id = users.id) OR 
                    (m2.sender_id = users.id AND m2.receiver_id = ?)
                )
                ORDER BY m2.created_at DESC 
                LIMIT 1
            ) as last_message_time,
            (
                SELECT COUNT(*)
                FROM messages m2
                WHERE m2.sender_id = users.id 
                AND m2.receiver_id = ? 
                AND m2.is_read = 0
            ) as unread_count
        FROM messages
        JOIN users ON users.id = CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        WHERE (sender_id = ? OR receiver_id = ?)
        AND users.id != ?
        GROUP BY users.id
        ORDER BY last_message_time DESC
    ");

    $chat_partners_stmt->execute([
        $current_user_id,  // CASE WHEN için
        $current_user_id,  // İlk alt sorgu için sender_id
        $current_user_id,  // İlk alt sorgu için receiver_id
        $current_user_id,  // İkinci alt sorgu için sender_id
        $current_user_id,  // İkinci alt sorgu için receiver_id
        $current_user_id,  // Üçüncü alt sorgu için receiver_id
        $current_user_id,  // JOIN için
        $current_user_id,  // WHERE sender_id için
        $current_user_id,  // WHERE receiver_id için
        $current_user_id   // AND users.id != ? için
    ]);

    $chat_partners = $chat_partners_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Son mesaj zamanını formatla
    foreach ($chat_partners as &$partner) {
        if ($partner['last_message_time']) {
            $timestamp = strtotime($partner['last_message_time']);
            $partner['last_message_time'] = date('H:i', $timestamp);
        }
    }

    echo json_encode([
        'success' => true,
        'chat_partners' => $chat_partners
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} 