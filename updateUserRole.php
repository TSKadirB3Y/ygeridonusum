<?php
session_start();
include 'register/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Oturum açılmamış"]);
    exit;
}

$user_id = $_POST['user_id'];

// Kullanıcının mevcut yetkisini al
$stmt = $conn->prepare("SELECT role FROM uyeler WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Kullanıcı bulunamadı"]);
    exit;
}

// Yeni yetki belirle
$newRole = $user['role'] == 'admin' ? 'user' : 'admin';

// Yetkiyi güncelle
$stmt = $conn->prepare("UPDATE uyeler SET role = :newRole WHERE id = :id");
$stmt->bindParam(':newRole', $newRole, PDO::PARAM_STR);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "new_role" => $newRole]);
} else {
    echo json_encode(["status" => "error", "message" => "Yetki değiştirilemedi"]);
}

$conn = null;
?>
