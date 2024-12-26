<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $follower_id = $_POST['follower_id'];
    $following_id = $_POST['following_id'];

    $sql = "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$follower_id, $following_id]);
        echo "Kullanıcı takip edildi!";
    } catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
}
?>