<?php
// config.php dosyasını dahil et
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $ad = $_POST['u-name'];
    $soyad = $_POST['surname'];
    $email = $_POST['email'];
    $sifre = $_POST['password'];

    // Şifreyi güvenli bir şekilde hash'le
    $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);

    // SQL sorgusunu hazırla
    $stmt = $conn->prepare("INSERT INTO uyeler (ad, soyad, email, sifre) VALUES (:ad, :soyad, :email, :sifre)");

    // Parametreleri bağla
    $stmt->bindParam(':ad', $ad, PDO::PARAM_STR);
    $stmt->bindParam(':soyad', $soyad, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':sifre', $hashed_password, PDO::PARAM_STR);

    // Sorguyu çalıştır
    if ($stmt->execute()) {
        echo "<script>alert('Yeni kayıt başarıyla eklendi.'); window.location.href='../login.php';</script>";
    } else {
        echo "<script>alert('Hata: Kayıt eklenemedi.');</script>";
    }
}

// Bağlantıyı kapat
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>

    <div class="container">
        <div class="logo">
            <img src="../img/loginImage.png" alt="Logo">
        </div>

        <form action="" method="POST"> <!-- Form metodu ayarlandı -->
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
