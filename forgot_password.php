<?php
session_start();
require_once 'db.php';
require_once 'mail_functions.php';

// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // E-posta adresini kontrol et
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Benzersiz token oluştur
        $token = bin2hex(random_bytes(32));
        
        // Şu anki zamanı al ve 1 saat ekle
        $now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
        $now->add(new DateInterval('PT1H'));
        $expires = $now->format('Y-m-d H:i:s');
        
        // Önce eski token'ları temizle
        $clear_stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $clear_stmt->execute([$user['id']]);
        
        // Yeni token'ı veritabanına kaydet
        $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $update_stmt->execute([$token, $expires, $user['id']]);
        
        // Debug için token ve süre bilgilerini logla
        error_log("Token created: " . $token);
        error_log("Expires at: " . $expires);
        
        // Şifre sıfırlama e-postası gönder
        if (sendPasswordResetEmail($email, $token)) {
            $_SESSION['notification'] = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
            $_SESSION['notification_type'] = "success";
        } else {
            $_SESSION['notification'] = "E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
            $_SESSION['notification_type'] = "error";
        }
    } else {
        $_SESSION['notification'] = "Bu e-posta adresi ile kayıtlı bir hesap bulunamadı.";
        $_SESSION['notification_type'] = "error";
    }
    
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum - Yaratıcı Geri Dönüşüm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f3f6f9;
            --text-color: #2c3e50;
            --border-radius: 15px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .forgot-password-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid var(--secondary-color);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-password-container">
            <div class="brand-logo">
                <h1>Yaratıcı Geri Dönüşüm</h1>
                <p class="text-muted">Şifremi Unuttum</p>
            </div>

            <?php if (isset($_SESSION['notification'])): ?>
            <div class="alert alert-<?php echo $_SESSION['notification_type']; ?>" role="alert">
                <?php 
                echo $_SESSION['notification'];
                unset($_SESSION['notification']);
                unset($_SESSION['notification_type']);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">E-posta Adresi</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Şifre Sıfırlama Bağlantısı Gönder</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">Giriş sayfasına dön</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 