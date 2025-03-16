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

if (!isset($_POST['post_id']) || !isset($_POST['comment_content'])) {
    $_SESSION['notification'] = 'Geçersiz yorum verisi.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Geçersiz yorum verisi.']);
    exit;
}

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];
$content = trim($_POST['comment_content']);

if (empty($content)) {
    $_SESSION['notification'] = 'Yorum içeriği boş olamaz.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Yorum içeriği boş olamaz.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content]);

    $_SESSION['notification'] = 'Yorum başarıyla eklendi.';
    $_SESSION['notification_type'] = 'success';
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $_SESSION['notification'] = 'Yorum eklenirken bir hata oluştu.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
?>