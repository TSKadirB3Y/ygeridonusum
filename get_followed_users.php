<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.profile_picture
        FROM users u
        INNER JOIN follows f ON f.following_id = u.id
        WHERE f.follower_id = ?
        ORDER BY u.first_name, u.last_name
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Kullanıcılar yüklenirken bir hata oluştu'
    ]);
}
?> 