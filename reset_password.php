<?php
session_start();
require_once 'db.php';

// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

if (!isset($_GET['token'])) {
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Debug için token bilgisini logla
error_log("Verifying token: " . $token);

// Token'ı kontrol et
$stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['notification'] = "Geçersiz şifre sıfırlama bağlantısı.";
    $_SESSION['notification_type'] = "error";
    header("Location: login.php");
    exit();
}

// Süre kontrolü
$now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
$expires = new DateTime($user['reset_token_expires'], new DateTimeZone('Europe/Istanbul'));

// Debug için süre bilgilerini logla
error_log("Current time: " . $now->format('Y-m-d H:i:s'));
error_log("Expires at: " . $expires->format('Y-m-d H:i:s'));

if ($now > $expires) {
    // Token'ı temizle
    $clear_stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
    $clear_stmt->execute([$token]);
    
    $_SESSION['notification'] = "Şifre sıfırlama bağlantısının süresi dolmuş.";
    $_SESSION['notification_type'] = "error";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Şifreler eşleşmiyor!";
    } else {
        // Yeni şifreyi hashle ve kaydet
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $update_stmt->execute([$hashed_password, $user['id']]);
        
        $_SESSION['notification'] = "Şifreniz başarıyla değiştirildi. Şimdi giriş yapabilirsiniz.";
        $_SESSION['notification_type'] = "success";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama - Yaratıcı Geri Dönüşüm</title>
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

        .reset-password-container {
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
        <div class="reset-password-container">
            <div class="brand-logo">
                <h1>Yaratıcı Geri Dönüşüm</h1>
                <p class="text-muted">Yeni Şifre Belirleme</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password" class="form-label">Yeni Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">Giriş sayfasına dön</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 