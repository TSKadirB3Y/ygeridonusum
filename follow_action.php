<?php
session_start();
require_once 'db.php';

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

// POST verilerini kontrol et
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$follower_id = $_SESSION['user_id'];
$following_id = (int)$_POST['user_id'];

// Kendini takip etmeye çalışıyorsa hata döndür
if ($follower_id === $following_id) {
    echo json_encode(['success' => false, 'message' => 'Kendinizi takip edemezsiniz']);
    exit();
}

try {
    // Takip durumunu kontrol et
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->execute([$follower_id, $following_id]);
    $is_following = $check_stmt->fetchColumn() > 0;

    if ($is_following) {
        // Takibi bırak
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        $action = 'unfollow';
    } else {
        // Takip et
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        $action = 'follow';
    }

    // Yeni takipçi sayısını al
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $count_stmt->execute([$following_id]);
    $followers_count = $count_stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'followers_count' => $followers_count
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?> 