<?php
require_once 'admin_check.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: view_posts.php');
    exit;
}

$post_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.profile_picture,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header('Location: view_posts.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Post detayları alınırken bir hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Detayı - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .post-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .user-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .post-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .stats {
            display: flex;
            gap: 20px;
            color: #666;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container post-container">
        <div class="card p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="user-info">
                    <img src="profilep/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="Profil Resmi">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h5>
                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></small>
                    </div>
                </div>

                <div class="post-content">
                    <p class="lead"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    <?php if (isset($post['image']) && !empty($post['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Medya" class="post-image mb-3">
                    <?php endif; ?>
                </div>

                <div class="stats">
                    <span><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> beğeni</span>
                    <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> yorum</span>
                </div>

                <div class="mt-4">
                    <a href="view_posts.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 