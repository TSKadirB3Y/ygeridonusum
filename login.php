<?php
session_start();

// Eğer kullanıcı zaten giriş yapmışsa posts.php'ye yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: posts.php");
    exit();
}

require_once 'db.php';

// Form gönderildiyse giriş işlemi yapalım
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // E-posta kontrolü
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    // Kullanıcı varsa
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Banned kullanıcı kontrolü
        if ($user['banned'] == 1) {
            $error_message = "Hesabınız banlanmıştır. Lütfen destek ile iletişime geçin.";
        } else {
            // E-posta doğrulama kontrolü
            if ($user['email_verified'] == 0) {
                $error_message = "Lütfen önce e-posta adresinizi doğrulayın. Doğrulama e-postası gönderildi.";
            } else {
                // Şifreyi doğrula
                if (password_verify($password, $user['password'])) {
                    // Oturum güvenliği için ID yenile
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];  // Kullanıcı rolünü al
                    
                    // Yönlendirme
                    header("Location: posts.php");
                    exit();
                } else {
                    $error_message = "Geçersiz giriş bilgileri!";
                }
            }
        }
    } else {
        $error_message = "Geçersiz giriş bilgileri!";
    }
}

$page_name = 'login';
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
    <title>Giriş Yap - Yaratıcı Geri Dönüşüm</title>
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

        .login-container {
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

        .brand-logo p {
            color: #666;
            margin-bottom: 0;
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

        .social-login {
            margin-top: 30px;
            text-align: center;
        }

        .social-login .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .social-login .divider::before,
        .social-login .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .social-login .divider span {
            padding: 0 10px;
            color: #777;
            font-size: 0.9rem;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icons a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .social-icons a:hover {
            transform: scale(1.1);
        }

        .facebook { background: #3b5998; }
        .google { background: #db4437; }
        .twitter { background: #1da1f2; }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            padding: 15px;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 20px;
            }

            .brand-logo h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="brand-logo">
                <h1>Yaratıcı Geri Dönüşüm</h1>
                <p class="text-muted">Sosyal dünyaya hoş geldiniz</p>
            </div>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['notification'])): ?>
            <div class="alert alert-<?php echo $_SESSION['notification_type']; ?>" role="alert">
                <?php 
                echo $_SESSION['notification'];
                unset($_SESSION['notification']);
                unset($_SESSION['notification_type']);
                ?>
            </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-group">
                    <input type="email" class="form-control" name="email" placeholder="E-posta" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password" placeholder="Şifre" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Beni Hatırla</label>
                </div>
                <button type="submit" class="btn btn-primary">Giriş Yap</button>
            </form>

            <div class="text-center mt-3">
                <a href="forgot_password.php" class="text-decoration-none">Şifremi Unuttum</a>
            </div>

            <div class="text-center mt-3">
                <p>Hesabınız yok mu? <a href="register.php" class="text-decoration-none">Hemen Kaydol</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!-- Meta Tagları -->
<meta name='title' content='Giriş Yap'>
<meta name='description' content='Giriş Yap'>
<meta name='keywords' content='Giriş Yap'>
