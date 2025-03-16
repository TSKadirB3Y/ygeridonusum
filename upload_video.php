<?php
// Veritabanı bağlantısını dahil edelim
require_once 'db.php';

// Eğer form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kullanıcıdan gelen verileri alalım
    $video_file = $_FILES['video_file'];
    
    // Hataları tutacak bir dizi
    $errors = [];

    // Dosya geçerliliğini kontrol et
    if ($video_file['error'] == 0) {
        $allowed_extensions = ['mp4', 'avi', 'mov', 'mkv'];
        $file_extension = pathinfo($video_file['name'], PATHINFO_EXTENSION);
        
        // Dosya uzantısını kontrol et
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Video dosyasını yüklemek için yeni isim oluştur
            $new_video_name = uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/videos/' . $new_video_name;

            // Videoyu yükle
            if (move_uploaded_file($video_file['tmp_name'], $upload_path)) {
                // Videoyu veritabanına kaydet
                $sql = "INSERT INTO posts (user_id, video_path) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $new_video_name]);

                echo "<p>Video başarıyla yüklendi!</p>";
                echo "<a href='posts.php'>Postlar sayfasına git</a>";
                exit();
            } else {
                $errors[] = "Video yüklenirken bir hata oluştu.";
            }
        } else {
            $errors[] = "Geçersiz dosya formatı. Yalnızca .mp4, .avi, .mov ve .mkv formatları kabul edilir.";
        }
    } else {
        $errors[] = "Video yüklenirken bir hata oluştu.";
    }

    // Hata mesajlarını görüntüle
    if (!empty($errors)) {
        echo "<ul style='color:red;'>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Yükle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Video Yükle</h2>

    <form action="upload_video.php" method="POST" enctype="multipart/form-data">
        <label for="video_file">Video Seç:</label>
        <input type="file" name="video_file" id="video_file" required><br><br>
        
        <button type="submit">Videoyu Yükle</button>
    </form>
</body>
</html>