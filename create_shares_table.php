<?php
require_once 'db.php';

try {
    // SQL dosyasını oku
    $sql = file_get_contents('add_shares_table.sql');
    
    // SQL sorgusunu çalıştır
    $pdo->exec($sql);
    
    echo "Shares tablosu başarıyla oluşturuldu!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?> 