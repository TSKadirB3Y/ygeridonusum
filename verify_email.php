<?php
session_start();
require_once 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Token'ı veritabanında ara
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // E-posta adresini doğrulanmış olarak işaretle
        $update_stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $update_stmt->execute([$user['id']]);
        
        $_SESSION['notification'] = "E-posta adresiniz başarıyla doğrulandı! Şimdi giriş yapabilirsiniz.";
        $_SESSION['notification_type'] = "success";
    } else {
        $_SESSION['notification'] = "Geçersiz veya kullanılmış doğrulama bağlantısı.";
        $_SESSION['notification_type'] = "error";
    }
} else {
    $_SESSION['notification'] = "Geçersiz doğrulama bağlantısı.";
    $_SESSION['notification_type'] = "error";
}

header("Location: login.php");
exit();
?> 