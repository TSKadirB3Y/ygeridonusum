<?php
session_start();
session_unset(); // Session verilerini temizle
session_destroy(); // Session'ı sona erdir

// Çıkış yaptıktan sonra login sayfasına yönlendir
header("Location: login.php");
exit();
?>
