<?php
require_once 'db.php';

try {
    // SQL dosyasını oku
    $sql = file_get_contents('add_shared_post_column.sql');
    
    // SQL sorgusunu çalıştır
    $pdo->exec($sql);
    
    echo "Messages tablosu başarıyla güncellendi!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?> 