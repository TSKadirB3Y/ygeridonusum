<?php
session_start();

// Eğer kullanıcı zaten giriş yapmışsa posts.php'ye yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: posts.php");
    exit();
}

// Veritabanı bağlantısını dahil edelim
require_once 'db.php';

// Eğer form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kullanıcıdan gelen verileri alalım
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];  // Kullanıcı adı
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];  // Şifre doğrulama

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

    // Şifreyi güvenli bir şekilde hashleyelim
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // SQL sorgusunu yazalım
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        // E-posta daha önce kayıtlı mı diye kontrol et
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu e-posta zaten kayıtlı!";
        } else {
            // E-posta ve kullanıcı adı benzersizse, kullanıcıyı ekleyelim
            $sql = "INSERT INTO users (first_name, last_name, email, username, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$first_name, $last_name, $email, $username, $hashed_password])) {
                echo "<p>Kullanıcı başarıyla kaydedildi!</p>";
                echo "<a href='login.php'>Giriş yapmak için tıklayın</a>";
                exit();
            } else {
                $errors[] = "Bir hata oluştu, lütfen tekrar deneyin.";
            }
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
    <title>SocialConnect - Kayıt Ol</title>
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
        }

        .register-container {
            max-width: 500px;
            margin: 50px auto;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        @media (max-width: 576px) {
            .register-container {
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
        <div class="register-container">
            <div class="brand-logo">
                <h1>SocialConnect</h1>
                <p class="text-muted">Yeni Hesap Oluştur</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Ad</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Soyad</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" name="username" required>
                </div>

                <div class="form-group">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" name="email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Şifre</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Profil Fotoğrafı</label>
                    <input type="file" class="form-control" name="profile_picture" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Kayıt Ol</button>
            </form>

            <div class="login-link">
                Zaten hesabın var mı? <a href="index.php">Giriş Yap</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
