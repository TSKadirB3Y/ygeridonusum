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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }
        .container h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .container p {
            margin: 10px 0;
        }
        .container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .container button:hover {
            background-color: #45a049;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .container h1 {
                font-size: 20px;
            }
            .container button {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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
    </div>
</body>
</html>