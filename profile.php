<?php
session_start();
include 'register/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini veri tabanından al
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT ad, role, soyad, email FROM uyeler WHERE id = :id");
$stmt->bindParam(":id", $user_id, PDO::PARAM_INT);

if ($stmt->execute()) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $ad = $user['ad'];
        $soyad = $user['soyad'];
        $email = $user['email'];
        $role = $user['role'];
    } else {
        echo "<script>alert('Kullanıcı bilgileri bulunamadı.');</script>";
    }
} else {
    echo "<script>alert('Kullanıcı bilgileri alınamadı.');</script>";
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Sayfası</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <h2>Profil Bilgileriniz</h2>
            <div class="card">
            <p><strong>Yetki:</strong> <?php echo htmlspecialchars($role); ?></p>
                <p><strong>Ad:</strong> <?php echo htmlspecialchars($ad); ?></p>
                <p><strong>Soyad:</strong> <?php echo htmlspecialchars($soyad); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            </div>
            <div class="input-box button">
                <a href="logout.php" class="logout-button">Çıkış Yap</a>
            </div>
        </div>
    </div>
</body>
</html>
