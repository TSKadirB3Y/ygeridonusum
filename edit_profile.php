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

// Başarı ve hata mesajları için değişkenler
$success_message = '';
$error_message = '';

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
            if ($user['profile_picture'] && $user['profile_picture'] != 'default-profile.png') {
                $old_picture_path = 'profilep/' . $user['profile_picture'];
                if (file_exists($old_picture_path)) {
                    unlink($old_picture_path);
                }
            }

            // Yeni profil fotoğrafını yükle
            $new_picture_name = uniqid() . '.' . $file_extension;
            $upload_path = 'profilep/' . $new_picture_name;

            if (move_uploaded_file($profile_picture['tmp_name'], $upload_path)) {
                // Veritabanında güncelle
                $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$new_picture_name, $user_id]);

                $success_message = "Profil fotoğrafınız başarıyla güncellendi!";
                // Kullanıcı bilgilerini güncelle
                $user['profile_picture'] = $new_picture_name;
            } else {
                $error_message = "Profil fotoğrafı yüklenirken bir hata oluştu.";
            }
        } else {
            $error_message = "Geçersiz dosya formatı. Yalnızca .jpg, .jpeg, .png, .gif formatları kabul edilir.";
        }
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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profili Düzenle - SocialConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .edit-profile-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        .current-profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }
        .btn-primary:hover {
            background-color: #357abd;
            border-color: #357abd;
        }
        .alert {
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link:hover {
            color: #357abd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-profile-container">
            <a href="profile.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="profile-section">
                <h2 class="section-title">Profil Fotoğrafını Değiştir</h2>
                <div class="text-center">
                    <img src="profilep/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default-profile.png'); ?>" 
                         alt="Profil Fotoğrafı" 
                         class="current-profile-picture">
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Yeni Profil Fotoğrafı</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                    </div>
                    <button type="submit" name="change_profile_picture" class="btn btn-primary">Fotoğrafı Güncelle</button>
                </form>
            </div>

            <div class="profile-section">
                <h2 class="section-title">Şifre Değiştir</h2>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Şifreyi Değiştir</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 