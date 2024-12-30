<?php
session_start();
require_once 'db.php';

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendirir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı rolünü al
$user_role = $_SESSION['role'];

// Yalnızca 'admin' veya 'batman' rollerine sahip kullanıcılara izin verilir
if ($user_role != 'admin' && $user_role != 'batman') {
    echo "Bu sayfayı görüntüleme izniniz yok!";
    exit;
}

// Kullanıcı sayısını veritabanından çek
$sql = "SELECT COUNT(*) AS total_users FROM users";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Toplam post sayısını veritabanından çek
$sql_posts = "SELECT COUNT(*) AS total_posts FROM posts";
$stmt_posts = $pdo->prepare($sql_posts);
$stmt_posts->execute();
$post_count = $stmt_posts->fetch(PDO::FETCH_ASSOC)['total_posts'];

// Yorum sayısını veritabanından çek
$sql_comments = "SELECT COUNT(*) AS total_comments FROM comments";
$stmt_comments = $pdo->prepare($sql_comments);
$stmt_comments->execute();
$comment_count = $stmt_comments->fetch(PDO::FETCH_ASSOC)['total_comments'];

// Meta verilerini ayarla
$page_name = 'dashboard';
$sql = "SELECT * FROM page_meta WHERE page_name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$page_name]);
$page_meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Sayfa başlığı ve açıklamaları
$title = $page_meta['title'] ?? 'Dashboard';
$description = $page_meta['description'] ?? 'Admin Dashboard';
$keywords = $page_meta['keywords'] ?? 'Admin, Dashboard, Yönetim';

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        /* Stil ve Tasarım */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .dashboard-container {
            width: 80%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border-radius: 8px;
        }

        h1 {
            margin: 0;
        }

        .logout {
            background-color: #f44336;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .logout:hover {
            background-color: #e53935;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }

        .stat-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            width: 30%;
            text-align: center;
        }

        .stat-item h3 {
            margin: 0;
            font-size: 18px;
        }

        .stat-item p {
            font-size: 24px;
            margin-top: 10px;
            font-weight: bold;
        }

        .actions {
            margin-top: 30px;
        }

        .actions h2 {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .actions ul {
            list-style: none;
            padding: 0;
        }

        .actions ul li {
            margin: 10px 0;
        }

        .actions ul li a {
            text-decoration: none;
            color: #4CAF50;
            font-size: 18px;
        }

        .actions ul li a:hover {
            color: #388E3C;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <header>
        <h1>Admin Paneli</h1>
        <a href="logout.php" class="logout">Çıkış Yap</a>
    </header>

    <div class="stats">
        <div class="stat-item">
            <h3>Kullanıcılar</h3>
            <p><?= $user_count ?></p> <!-- Kullanıcı sayısı burada gösterilir -->
        </div>
        <div class="stat-item">
            <h3>Paylaşılmış Postlar</h3>
            <p><?= $post_count ?></p> <!-- Paylaşılmış post sayısı burada gösterilir -->
        </div>
        <div class="stat-item">
            <h3>Yorumlar</h3>
            <p><?= $comment_count ?></p> <!-- Yorum sayısı burada gösterilir -->
        </div>
    </div>

    <div class="actions">
        <h2>Yönetim Seçenekleri</h2>
        <ul>
            <li><a href="metaadmin.php">Meta Ayarlarını Yönet</a></li>
            <li><a href="admin.php">Üyeleri Yönet</a></li>
            <li><a href="view_posts.php">Postları Yönet</a></li> <!-- Postları Görüntüle Butonu -->
            <li><a href="view_comments.php">Yorumları Yönet</a></li>
        </ul>
    </div>
</div>

</body>
</html>