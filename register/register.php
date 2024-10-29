<?php
// Başlık tanımı
$title = "Kayıt Ol"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title> <!-- Dinamik başlık -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>

    <div class="container">

        <div class="logo">
            <img src="../img/loginImage.png" alt="Logo">
        </div>

        <form action="register.php" method="POST"> <!-- Form metodu ayarlandı -->
            <div class="title">Kayıt</div>

            <div class="input-box">
                <input type="text" name="u-name" placeholder="Adınız" required> <!-- Ad girişi -->
                <div class="underline"></div>
            </div>

            <div class="input-box">
                <input type="text" name="surname" placeholder="Soyadınız" required> <!-- Soyad girişi -->
                <div class="underline"></div>
            </div>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email Giriniz" required> <!-- Email girişi -->
                <div class="underline"></div>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Şifrenizi Giriniz" required> <!-- Şifre girişi -->
                <div class="underline"></div>
            </div>

            <div class="input-box button">
                <input type="submit" value="Kayıt Ol">
            </div>

            <div class="input-box">
                <a href="../login.php" class="register-page">Giriş Yap</a>
            </div>

        </form>
    </div>   
</body>
</html>
