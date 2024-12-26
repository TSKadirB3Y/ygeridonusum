<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    $sql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$post_id, $user_id]);
        echo "Gönderi beğenildi!";
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}
?>