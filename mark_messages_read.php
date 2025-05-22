<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$sender_id = $_POST['sender_id'] ?? null;

if (!$sender_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz gönderen ID']);
    exit();
}

try {
    // Belirli bir kullanıcıdan gelen okunmamış mesajları okundu olarak işaretle
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = ? 
        AND receiver_id = ? 
        AND is_read = 0
    ");
    $stmt->execute([$sender_id, $current_user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Mesajlar okundu olarak işaretlendi'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} 