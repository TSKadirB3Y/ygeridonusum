<?php
$host = 'localhost'; // Veritabanı sunucusu
$dbname = 'uyeler'; // Veritabanı adı
$username = 'root'; // Veritabanı kullanıcı adı
$password = ''; // Veritabanı şifresi

try {
    // PDO ile veritabanı bağlantısını oluştur
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Hata raporlama modunu ayarla
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Bağlantı hatası durumunda hata mesajı göster
    echo "Veritabanı bağlantısı sağlanamadı: " . $e->getMessage();
}
?>
