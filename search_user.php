<?php
require_once 'db.php';

$search_query = $_GET['query'] ?? '';

if (!empty($search_query)) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE first_name LIKE ? OR last_name LIKE ?");
    $stmt->execute(["%$search_query%", "%$search_query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
}
?>