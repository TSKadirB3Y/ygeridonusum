<?php
session_start();
include 'register/config.php'; // config dosyasını dahil et

// Pop-up mesajını gösterecek değişkenler
$show_popup = false;
$popup_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $sifre = $_POST['password'];

    // Kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT id, sifre, status, role FROM uyeler WHERE email = :email");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        $hashed_password = $user['sifre'];
        $user_status = $user['status']; // Kullanıcının durumu
        $user_role = $user['role']; // Kullanıcının rolü

        // Eğer kullanıcı banlanmışsa, giriş yapmasına izin verme
        if ($user_status == 'banned') {
            $show_popup = true;
            $popup_message = "Hesabınız banlanmıştır.";
        } elseif (password_verify($sifre, $hashed_password)) {
            // Şifre doğruysa
            $_SESSION['user_id'] = $user_id; // Oturumda kullanıcı ID bilgisi sakla

            // Eğer kullanıcı adminse, admin.php sayfasına yönlendir
            if ($user_role == 'admin') {
                header("Location: admin.php"); // Admin sayfasına yönlendir
            } else {
                header("Location: home/homepage.php"); // Normal kullanıcıyı ana sayfaya yönlendir
            }
            exit;
        } else {
            // Şifre yanlışsa
            $show_popup = true;
            $popup_message = "Hatalı şifre!";
        }
    } else {
        // Kullanıcı bulunamadıysa
        $show_popup = true;
        $popup_message = "Kullanıcı bulunamadı!";
    }
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sayfası</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Pop-up mesajı için CSS */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #f44336;
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 9999;
            text-align: center;
        }
        .popup .popup-content {
            margin-bottom: 15px;
        }
        .popup .close-btn {
            background-color: #fff;
            color: #f44336;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Eğer bir pop-up mesajı varsa bu pop-up görünecek -->
    <?php if ($show_popup): ?>
        <div class="popup" id="messagePopup">
            <div class="popup-content"><?php echo $popup_message; ?></div>
            <button class="close-btn" onclick="closePopup()">Kapat</button>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="logo">
            <img src="img/loginImage.png" alt="Logo">
        </div>

        <form action="" method="POST">
            <div class="title">Giriş</div>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email Giriniz" required>
                <div class="underline"></div>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Şifrenizi Giriniz" required>
                <div class="underline"></div>
            </div>

            <div class="input-box button">
                <input type="submit" value="Giriş Yap">
            </div>

            <div class="register">
                <a href="register/register.php" class="register-page">Kayıt Ol</a>
            </div>
        </form>
    </div>

    <script>
        // Pop-up'ı kapatmak için fonksiyon
        function closePopup() {
            document.getElementById('messagePopup').style.display = 'none';
        }

        // Eğer pop-up gösteriliyorsa, onu ekranda göster
        <?php if ($show_popup): ?>
            document.getElementById('messagePopup').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
