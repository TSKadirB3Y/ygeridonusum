<?php
session_start();
include 'register/config.php'; // Veritabanı bağlantısını dahil et

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || !isset($_POST['user_id']) || !isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
    exit;
}

// Admin değilse işlemi sonlandır
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM uyeler WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
    exit;
}

// Gelen veri
$target_user_id = $_POST['user_id'];
$action = $_POST['action'];

// Kullanıcı durumunu güncelleme
$new_status = $action === 'ban' ? 'banned' : 'active';
$stmt = $conn->prepare("UPDATE uyeler SET status = :status WHERE id = :id");
$stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
$stmt->bindParam(':id', $target_user_id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'new_status' => $new_status]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Durum güncellenemedi']);
}

$conn = null;
?>
