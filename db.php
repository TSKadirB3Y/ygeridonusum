<?php
$host = 'localhost';      // Veritabanı sunucusu
$dbname = 'social_media2'; // Veritabanı adı
$username = 'root';       // Veritabanı kullanıcı adı
$password = '';           // Veritabanı şifresi

try {
    // PDO bağlantısı
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>