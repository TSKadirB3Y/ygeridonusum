<?php
session_start(); // Oturumu başlat
session_unset(); // Tüm oturum değişkenlerini temizle
session_destroy(); // Oturumu sonlandır

// Kullanıcıyı login sayfasına yönlendir
header("Location: login.php");
exit;
?>
