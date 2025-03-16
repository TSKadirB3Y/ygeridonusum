<?php
require_once 'admin_check.php';
require_once 'db.php';  // Veritabanı bağlantısını ekle
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['page_url'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek'
    ]);
    exit;
}

$page_url = htmlspecialchars($_POST['page_url']);

try {
    // Debug için log ekle
    error_log("Aranan sayfa URL: " . $page_url);
    
    $sql = "SELECT title, description, keywords FROM page_meta WHERE page_url = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$page_url]);
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug için log ekle
    error_log("Bulunan meta veriler: " . print_r($meta, true));

    if ($meta) {
        echo json_encode([
            'success' => true,
            'meta' => $meta
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Bu sayfa için meta bilgisi bulunamadı'
        ]);
    }
} catch (PDOException $e) {
    // Hata durumunda log ekle
    error_log("Veritabanı hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} 