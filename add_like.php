<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Kullanıcının daha önce bu postu beğenip beğenmediğini kontrol et
    $check_like = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_like);
    $check_stmt->execute([$post_id, $user_id]);

    try {
        if ($check_stmt->rowCount() > 0) {
            // Beğeniyi kaldır
            $delete_like = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($delete_like);
            $stmt->execute([$post_id, $user_id]);
            $liked = false;
        } else {
            // Beğeni ekle
            $add_like = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($add_like);
            $stmt->execute([$post_id, $user_id]);
            $liked = true;
        }

        // Toplam beğeni sayısını al
        $count_likes = "SELECT COUNT(*) as count FROM likes WHERE post_id = ?";
        $count_stmt = $pdo->prepare($count_likes);
        $count_stmt->execute([$post_id]);
        $likes_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likes_count
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Bir hata oluştu: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek metodu'
    ]);
}
?>