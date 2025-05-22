<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.']);
    exit;
}

if (!isset($_POST['comment_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek parametreleri.']);
    exit;
}

$comment_id = $_POST['comment_id'];
$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];

try {
    // Yorumun sahibi olup olmadığını kontrol et
    $check_stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $check_stmt->execute([$comment_id]);
    $comment = $check_stmt->fetch();

    if (!$comment || $comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
        exit;
    }

    // Yorumu sil
    $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $delete_stmt->execute([$comment_id]);

    echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Yorum silinirken bir hata oluştu.']);
} 