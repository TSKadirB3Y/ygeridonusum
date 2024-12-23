<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
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
    <title>Anasayfa</title>
</head>
<body>
<h1>Hoşgeldiniz, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>


    <!-- Kullanıcı giriş yaptıysa, aşağıdaki butonlar gösterilir -->
    <p><a href="create_post.php"><button>Gönderi Paylaş</button></a></p>
    <p><a href="profile.php"><button>Profilim</button></a></p>
    <p><a href="posts.php"><button>Gönderiler</button></a></p>
    <p><a href="logout.php"><button>Çıkış Yap</button></a></p>

    <!-- Eğer kullanıcı admin veya batman rolündeyse, admin sayfası butonu gösterilsin -->
    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'batman')): ?>
        <p><a href="admin.php"><button>Admin Sayfası</button></a></p>
    <?php endif; ?>
</body>
</html>
