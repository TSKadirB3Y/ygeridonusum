<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    echo json_encode(['error' => 'Post ID gerekli']);
    exit;
}

$post_id = $_GET['post_id'];

try {
    $comments = $pdo->prepare("SELECT 
        comments.*, 
        users.username, 
        CONCAT('profilep/', users.profile_picture) as profile_picture 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE post_id = ? 
    ORDER BY comments.created_at DESC");
    
    $comments->execute([$post_id]);
    $comments_data = $comments->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($comments_data);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Yorumlar yüklenirken bir hata oluştu']);
} 