<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['notification'] = 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Oturum süreniz dolmuş.']);
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['user_id'])) {
    $_SESSION['notification'] = 'Geçersiz paylaşım verisi.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Geçersiz paylaşım verisi.']);
    exit;
}

$post_id = $_POST['post_id'];
$shared_with_user_id = $_POST['user_id'];
$sharing_user_id = $_SESSION['user_id'];

try {
    // Paylaşımın daha önce yapılıp yapılmadığını kontrol et
    $check_stmt = $pdo->prepare("SELECT id FROM messages WHERE sender_id = ? AND receiver_id = ? AND shared_post_id = ?");
    $check_stmt->execute([$sharing_user_id, $shared_with_user_id, $post_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $_SESSION['notification'] = 'Bu gönderiyi daha önce bu kullanıcı ile paylaştınız.';
        $_SESSION['notification_type'] = 'error';
        echo json_encode(['success' => false, 'message' => 'Gönderi zaten paylaşılmış.']);
        exit;
    }

    // Mesaj olarak paylaşımı ekle
    $share_stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, shared_post_id, content) VALUES (?, ?, ?, 'Bir gönderi paylaştı')");
    $share_stmt->execute([$sharing_user_id, $shared_with_user_id, $post_id]);

    $_SESSION['notification'] = 'Gönderi başarıyla paylaşıldı.';
    $_SESSION['notification_type'] = 'success';
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $_SESSION['notification'] = 'Paylaşım yapılırken bir hata oluştu.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?> 