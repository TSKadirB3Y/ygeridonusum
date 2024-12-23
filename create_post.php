<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once 'db.php';
require_once 'getID3-master/getid3/getid3.php'; // getID3 kütüphanesini dahil et

// Form verisi gönderildiyse, gönderi kaydedelim
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $content = htmlspecialchars($_POST['content']);
    $media = $_FILES['media'];

    $media_name = $media['name'];
    $media_tmp_name = $media['tmp_name'];
    $media_type = $media['type'];
    $media_size = $media['size'];

    // İzin verilen dosya türleri
    $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];

    // Dosya türünü kontrol et
    if (in_array($media_type, $allowed_image_types)) {
        // Eğer dosya bir resimse
        $image = basename($media_name);
        $video = null;  // Video boş olur
        move_uploaded_file($media_tmp_name, "uploads/" . $image);
    } elseif (in_array($media_type, $allowed_video_types)) {
        // Eğer dosya bir video ise
        $video = basename($media_name);
        $image = null;  // Resim boş olur

        // getID3 sınıfını başlat
        $getID3 = new getID3;

        // Video dosyasının meta bilgilerini al
        $fileInfo = $getID3->analyze($media_tmp_name);

        // Video süresi (saniye cinsinden)
        $duration = $fileInfo['playtime_seconds'];

        // Eğer video süresi 60 saniyeyi aşarsa hata mesajı göster
        if ($duration > 60) {
            $error_message = "Video süresi çok uzun! Lütfen 1 dakikadan kısa bir video yükleyin.";
        }

        move_uploaded_file($media_tmp_name, "uploads/" . $video);
    } else {
        $error_message = "Yalnızca resim veya video dosyaları yükleyebilirsiniz.";
    }

    if (empty($error_message)) {
        // SQL sorgusu
        $sql = "INSERT INTO posts (user_id, content, image, video) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $content, $image, $video])) {
            $success_message = 'Gönderiniz başarıyla paylaşıldı!';
        } else {
            $error_message = 'Bir hata oluştu, lütfen tekrar deneyin.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gönderi Paylaş</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
    /* Genel Stil */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 100vh;
    }

    /* Formu Stilize Et */
    .form-container {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 100%;
        margin: 20px auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Başlık */
    h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    /* Textarea */
    textarea {
        width: 100%;
        height: 150px;
        padding: 10px;
        font-size: 16px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        resize: none; /* Boyutlandırmayı engeller */
    }

    /* Dosya Yükleme */
    input[type="file"] {
        margin-bottom: 15px;
    }

    /* Gönder Butonu */
    button {
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }

    /* Başarı ve Hata Mesajları */
    .error {
        color: red;
        font-weight: bold;
    }

    .success {
        color: green;
        font-weight: bold;
    }

    /* Alt Navigasyon */
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

    <div class="form-container">
        <h1>Gönderi Paylaş</h1>
        
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <form action="create_post.php" method="POST" enctype="multipart/form-data">
            <textarea name="content" placeholder="Gönderinizi buraya yazın..." required></textarea><br><br>
            <input type="file" name="media" accept="image/*,video/*"><br><br>
            <button type="submit">Gönderiyi Paylaş</button>
        </form>
    </div>

    <!-- Alt Navigasyon -->
    <nav class="bottom-nav">
        <ul>
            <li><a href="posts.php"><i class="fas fa-home"></i></a></li>
            <li><a href="posts.php"><i class="fa-solid fa-compass"></i></a></li>
            <li><a href="profile.php"><i class="fa-solid fa-gear"></i></a></li>
            <li><a href="messages.php"><i class="fa-solid fa-comment"></i><span></a></li>
        </ul>
    </nav>

</body>
</html>