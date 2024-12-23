<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$post_id, $user_id, $comment]);
        echo "Yorum başarıyla eklendi!";
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}
?>