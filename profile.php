<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profil fotoğrafı değiştirme işlemi
if (isset($_POST['change_profile_picture']) && isset($_FILES['profile_picture'])) {
    $profile_picture = $_FILES['profile_picture'];

    // Dosya geçerliliğini kontrol et
    if ($profile_picture['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);
        
        // Dosya uzantısını kontrol et
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Eski profil fotoğrafını sil
            if ($user['profile_picture'] && $user['profile_picture'] != 'default-profile.jpg') {
                $old_picture_path = 'profilep/' . $user['profile_picture'];
                if (file_exists($old_picture_path)) {
                    unlink($old_picture_path); // Eski fotoğrafı sil
                }
            }

            // Yeni profil fotoğrafını yükle
            $new_picture_name = uniqid() . '.' . $file_extension;
            $upload_path = 'profilep/' . $new_picture_name;

            // Fotoğrafı yükle
            if (move_uploaded_file($profile_picture['tmp_name'], $upload_path)) {
                // Veritabanında güncelle
                $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$new_picture_name, $user_id]);

                // Başarı mesajı
                $success_message = "Profil fotoğrafınız başarıyla güncellendi!";
                // Yeni fotoğrafı yükledikten sonra güncellenmiş fotoğrafı hemen göstermek için:
                $user['profile_picture'] = $new_picture_name;
            } else {
                $error_message = "Profil fotoğrafı yüklenirken bir hata oluştu.";
            }
        } else {
            $error_message = "Geçersiz dosya formatı. Yalnızca .jpg, .jpeg, .png, .gif formatları kabul edilir.";
        }
    } else {
        $error_message = "Fotoğraf yüklenirken bir hata oluştu.";
    }
}

// Şifre değiştirme işlemi
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Mevcut şifreyi doğrula
    if (password_verify($current_password, $user['password'])) {
        // Yeni şifrenin doğruluğunu kontrol et
        if ($new_password === $confirm_password) {
            // Yeni şifreyi güvenli bir şekilde hash'le
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Şifreyi güncelle
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$new_password_hash, $user_id]);

            $success_message = "Şifreniz başarıyla değiştirildi!";
        } else {
            $error_message = "Yeni şifreler eşleşmiyor!";
        }
    } else {
        $error_message = "Mevcut şifreniz yanlış!";
    }
}

$page_name = 'profile';
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
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .form-group button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .form-group button:hover {
            background-color: #0056b3;
        }

        .log-out button {
            padding: 10px 15px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .log-out button:hover {
            background-color: red;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Şifre değiştir formunun başlangıçta gizlenmesi */
        #change-password-form {
            display: <?php echo isset($_POST['show_password_form']) ? 'block' : 'none'; ?>;
        }

        /* Profil fotoğrafı değiştir formunun başlangıçta gizlenmesi */
        #change-profile-picture-form {
            display: <?php echo isset($_POST['show_profile_picture_form']) ? 'block' : 'none'; ?>;
        }

        /* Sabit Menü CSS */
        .bottom-nav {
            position: fixed;
bottom: 0;
left: 0;
width: 100%;
background: linear-gradient(90deg, #0066cc, #003d99);
display: flex;
justify-content: space-evenly;
align-items: center;
padding: 15px 0;
border-top-left-radius: 15px;
border-top-right-radius: 15px;
box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
z-index: 1000;
        }

        .bottom-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        .bottom-nav ul li {
            margin: 0 15px;
        }

        .bottom-nav ul li a {
            text-decoration: none;
            color: white;
            font-size: 24px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Profil Bilgileriniz</h1>

        <!-- Başarı ve Hata Mesajları -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Profil Fotoğrafı ve Bilgileri Gösterme -->
        <div>
            <h2>Profil Fotoğrafı</h2>
            <?php if ($user['profile_picture']): ?>
                <img src="profilep/<?php echo $user['profile_picture']; ?>" alt="Profil Fotoğrafı" id="profile-picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <img src="profilep/default-profile.jpg" alt="Varsayılan Profil Fotoğrafı" id="profile-picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
            <?php endif; ?>
            <p><strong>Ad: </strong><?php echo $user['first_name'] . " " . $user['last_name']; ?></p>
            <p><strong>Rol: </strong><?php echo $user['role']; ?></p>
        </div>

        <!-- Profil Fotoğrafı Değiştir Butonu -->
        <form action="profile.php" method="POST">
            <div class="form-group">
                <button type="submit" name="show_profile_picture_form">Profil Fotoğrafını Değiştir</button>
            </div>
        </form>

        <!-- Profil Fotoğrafı Değiştirme Formu -->
        <div id="change-profile-picture-form">
            <h2>Profil Fotoğrafını Değiştir</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_picture">Yeni Profil Fotoğrafı</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="change_profile_picture">Fotoğrafı Değiştir</button>
                </div>
            </form>
        </div>

        <!-- Şifre Değiştirme Butonu -->
        <form action="profile.php" method="POST">
            <div class="form-group">
                <button type="submit" name="show_password_form">Şifreyi Değiştir</button>
            </div>
        </form>

        <!-- Şifre Değiştirme Formu -->
        <div id="change-password-form">
            <h2>Şifre Değiştir</h2>
            <form action="profile.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Yeni Şifreyi Onayla</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="change_password">Şifreyi Değiştir</button>
                </div>
            </form>
        </div>
        <!--Çıkış Yap-->
        <form action="logout.php" method="POST">
            <div class="log-out">
                <button type="submit" name="show_password_form">Çıkış Yap</button>
            </div>
        </form>
    </div>

    <!-- Sabit Menü -->
    <nav class="bottom-nav">
    <ul>
        <li><a href="posts.php"><i class="fas fa-home"></i></a></li>
        <li><a href="posts.php"><i class="fa-solid fa-compass"></i></a></li>
        <li><a href="profile.php"><i class="fa-solid fa-gear"></i></a></li>
        <li><a href="messages.php"><i class="fa-solid fa-comment"></i></a></li>
        
        <!-- Admin veya Batman rolü için yeni buton -->
        <?php if (in_array($user['role'], ['admin', 'batman'])): ?>
            <li><a href="view_meta.php"><i class="fa-solid fa-cogs"></i></a></li>
        <?php endif; ?>
    </ul>
</nav>

</body>
</html><!-- Meta Tagları -->
<meta name='title' content='Profilim'>
<meta name='description' content='Profilim'>
<meta name='keywords' content='Profilim'>