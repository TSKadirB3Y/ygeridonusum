<?php
session_start();
require_once 'db.php';

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'batman') {
    echo "Bu sayfaya erişim yetkiniz yok.";
    exit();
}

// Rol değişikliği yapabilmek için gelen veriyi kontrol et
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    // Batman rolü dışında bir kullanıcının rolü değiştirilmemeli
    if ($new_role != 'batman') {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$new_role, $user_id])) {
            echo "Rol başarıyla değiştirildi!";
        } else {
            echo "Bir hata oluştu.";
        }
    } else {
        echo "Batman rolü değiştirilemez!";
    }
} else {
    echo "Geçersiz işlem.";
}
?>