<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Kullanıcının daha önce bu postu beğenip beğenmediğini kontrol et
        $check_like = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
        $check_stmt = $pdo->prepare($check_like);
        $check_stmt->execute([$post_id, $user_id]);

        if ($check_stmt->rowCount() > 0) {
            // Beğeniyi kaldırma
            $delete_like_query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
            $delete_stmt = $pdo->prepare($delete_like_query);
            $delete_stmt->execute([$post_id, $user_id]);
            $is_liked = false;

            // Beğeni bildirimini sil
            $delete_notification_query = "DELETE FROM notifications WHERE from_user_id = ? AND post_id = ? AND type = 'like'";
            $delete_notification_stmt = $pdo->prepare($delete_notification_query);
            $delete_notification_stmt->execute([$user_id, $post_id]);
        } else {
            // Beğenme ekleme
            $like_query = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
            $like_stmt = $pdo->prepare($like_query);
            $like_stmt->execute([$post_id, $user_id]);
            $is_liked = true;

            // Post sahibinin ID'sini al
            $get_post_owner = "SELECT user_id FROM posts WHERE id = ?";
            $post_owner_stmt = $pdo->prepare($get_post_owner);
            $post_owner_stmt->execute([$post_id]);
            $post_owner_id = $post_owner_stmt->fetchColumn();

            // Eğer post sahibi kendisi değilse bildirim ekle
            if ($post_owner_id != $user_id) {
                $notification_query = "INSERT INTO notifications (from_user_id, to_user_id, type, post_id) VALUES (?, ?, 'like', ?)";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([$user_id, $post_owner_id, $post_id]);
            }
        }

        // Yeni beğeni sayısını al
        $count_query = "SELECT COUNT(*) FROM likes WHERE post_id = ?";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute([$post_id]);
        $likes_count = $count_stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'is_liked' => $is_liked,
            'likes_count' => $likes_count
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Post ID is required'
    ]);
}
?>