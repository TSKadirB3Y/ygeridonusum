<?php
session_start();
include '../register/config.php'; // Veritabanı bağlantısı dahil et

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Kullanıcıyı kontrol et ve admin olup olmadığını öğren
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM uyeler WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcı admin mi?
$isAdmin = $user && $user['role'] === 'admin';

// Başlık tanımı
$title = "Ana Sayfa"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title> <!-- Dinamik başlık -->
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="#">Logo</a>
        </div>
        <div class="burger" id="burger-menu">
            <i class="fas fa-bars"></i>
        </div>
        <ul class="nav-links" id="nav-links">
            <li><a href="#"><i class="fas fa-home"></i> Ana Sayfa</a></li>
            <li><a href="#"><i class="fas fa-compass"></i> Keşfet</a></li>
            <li><a href="#"><i class="fas fa-trophy"></i> Puan Sıralaması</a></li>
            <li><a href="#"><i class="fas fa-box"></i> Malzeme Kataloğu</a></li>
            <li><a href="#"><i class="fas fa-graduation-cap"></i> Eğitim Modülü</a></li>
            <li><a href="../profile.php"><i class="fas fa-user"></i> Profil</a></li>
            
            <?php if ($isAdmin): ?>
                <li><a href="../admin.php"><i class="fas fa-user-shield"></i> Admin Paneli</a></li> <!-- Sadece admin kullanıcılar için görünür -->
            <?php endif; ?>
        </ul>
    </nav>

    <script>
        // Hamburger menü açma kapama
        const burgerMenu = document.getElementById('burger-menu');
        const navLinks = document.getElementById('nav-links');

        burgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('nav-active'); // Menü açılacak veya kapanacak
        });
    </script>
</body>
</html>
