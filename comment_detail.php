<?php
// Oturum başlatılır
session_start();

// Veritabanı bağlantısı için db.php dosyasını dahil eder
require_once 'db.php';

// Yorum ID'sini URL parametresinden alırız
if (!isset($_GET['id'])) {
    echo "Yorum bulunamadı.";
    exit;
}

$comment_id = $_GET['id'];

// Yorum detaylarını almak için veritabanından sorgu yapılır
$sql = "SELECT * FROM comments WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer yorum bulunamazsa, hata mesajı gösterilir
if (!$comment) {
    echo "Yorum bulunamadı.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Yorum Detayları">
    <meta name="keywords" content="Admin, Yorum, Detay, Görüntüle">
    <title>Yorum Detayı</title>
</head>
<body>
    <div class="container">
        <h1>Yorum Detayı</h1>
        <p><strong>Yorum ID:</strong> <?= htmlspecialchars($comment['id']) ?></p>
        <p><strong>Yorum İçeriği:</strong> <?= htmlspecialchars($comment['content']) ?></p>
        <p><strong>Yorum Yapan Kullanıcı ID:</strong> <?= htmlspecialchars($comment['user_id']) ?></p>
        <p><strong>Oluşturulma Tarihi:</strong> <?= htmlspecialchars($comment['created_at']) ?></p>
    </div>
</body>
</html>
