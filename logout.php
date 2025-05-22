<?php
session_start();

// Tüm session değişkenlerini temizle
session_unset();

// Session'ı sonlandır
session_destroy();

// Kullanıcıyı login sayfasına yönlendir
header("Location: login.php");
exit();
?>
