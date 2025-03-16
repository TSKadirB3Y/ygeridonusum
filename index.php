<?php
session_start();

// Eğer kullanıcı giriş yapmışsa posts.php'ye yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: posts.php");
    exit();
} else {
    // Giriş yapmamış kullanıcıları login.php'ye yönlendir
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı bilgilerini oturumdan veya veritabanından al
$first_name = "Kullanıcı Adı";
$last_name = "Soyadı";

if (isset($_SESSION['user_id'])) {
    // Oturumdan bilgileri al
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $first_name = $_SESSION['first_name'];
        $last_name = $_SESSION['last_name'];
    } else {
        // Eğer oturumda yoksa, veritabanından al
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaratıcı Geri Dönüşüm</title>
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

        .login-container {
            max-width: 400px;
            margin: 100px auto;
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

        @media (max-width: 576px) {
            .login-container {
                margin: 50px 20px;
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
                <h1>SocialConnect</h1>
                <p class="text-muted">Arkadaşlarınla bağlantıda kal</p>
            </div>
            
            <form action="login.php" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Kullanıcı Adı" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Şifre" required>
                </div>
                <button type="submit" class="btn btn-primary">Giriş Yap</button>
            </form>

            <div class="social-login">
                <div class="divider">
                    <span>veya</span>
                </div>
                <div class="social-icons">
                    <a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="google"><i class="fab fa-google"></i></a>
                    <a href="#" class="twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="register-link">
                Hesabın yok mu? <a href="register.php">Hemen Kaydol</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>