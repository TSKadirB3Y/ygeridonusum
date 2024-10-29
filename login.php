<?php
// Başlık tanımı
$title = "Animasyonlu Login Form"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title> <!-- Dinamik başlık -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>

    <div class="container">

        <div class="logo">
            <img src="img/loginImage.png" alt="Logo">
        </div>

        <form action="login.php" method="POST"> <!-- Form metodu ayarlandı -->
            <div class="title">Giriş</div>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email Giriniz" required> <!-- Email girişi -->
                <div class="underline"></div>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Şifrenizi Giriniz" required> <!-- Şifre girişi -->
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
</body>
</html>
