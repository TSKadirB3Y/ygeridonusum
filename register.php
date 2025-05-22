<?php
session_start();

// Eğer kullanıcı zaten giriş yapmışsa posts.php'ye yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: posts.php");
    exit();
}

// Veritabanı bağlantısını dahil edelim
require_once 'db.php';
require_once 'mail_functions.php';

// Eğer form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kullanıcıdan gelen verileri alalım
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Hataları tutacak bir dizi
    $errors = [];

    // Şifrelerin eşleşip eşleşmediğini kontrol et
    if ($password !== $confirm_password) {
        $errors[] = "Şifreler eşleşmiyor!";
    }

    // Kullanıcı adının eşsiz olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Bu kullanıcı adı zaten alınmış!";
    }

    // E-posta adresinin eşsiz olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Bu e-posta adresi zaten kayıtlı!";
    }

    // Şifreyi güvenli bir şekilde hashleyelim
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));

        // Kullanıcıyı veritabanına ekle
        $sql = "INSERT INTO users (first_name, last_name, email, username, password, verification_token) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$first_name, $last_name, $email, $username, $hashed_password, $verification_token])) {
            // Doğrulama e-postası gönder
            if (sendVerificationEmail($email, $verification_token)) {
                $_SESSION['notification'] = "Kayıt işlemi başarılı! Lütfen e-posta adresinizi doğrulayın.";
                $_SESSION['notification_type'] = "success";
            } else {
                $_SESSION['notification'] = "Kayıt işlemi başarılı ancak doğrulama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.";
                $_SESSION['notification_type'] = "warning";
            }
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Bir hata oluştu, lütfen tekrar deneyin.";
        }
    }
}

$page_name = 'register';
$sql = "SELECT * FROM page_meta WHERE page_name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$page_name]);
$page_meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer meta verileri varsa, sayfa başlığını ve meta açıklamasını kullan
$title = $page_meta['title'] ?? 'Varsayılan Başlık';
$description = $page_meta['description'] ?? 'Varsayılan açıklama';
$keywords = $page_meta['keywords'] ?? 'Varsayılan, Anahtar, Kelimeler';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Yaratıcı Geri Dönüşüm</title>
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

        .register-container {
            max-width: 500px;
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
        <div class="register-container">
            <div class="brand-logo">
                <h1>Yaratıcı Geri Dönüşüm</h1>
                <p class="text-muted">Yeni Hesap Oluştur</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name" class="form-label">Ad</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name" class="form-label">Soyad</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="username" class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Kayıt Ol</button>
            </form>

            <div class="text-center mt-3">
                <p>Zaten hesabınız var mı? <a href="login.php" class="text-decoration-none">Giriş Yap</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
