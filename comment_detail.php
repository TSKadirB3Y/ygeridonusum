<?php
require_once 'admin_check.php';
requireAdmin();

// Yorum ID'sini URL parametresinden alırız
if (!isset($_GET['id'])) {
    header('Location: view_comments.php');
    exit;
}

$comment_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.content as post_content, p.image as post_image,
        u.first_name, u.last_name, u.profile_picture
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        header('Location: view_comments.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Yorum detayları alınırken bir hata oluştu: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Yorum Detayları">
    <meta name="keywords" content="Admin, Yorum, Detay, Görüntüle">
    <title>Yorum Detayı - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .comment-container {
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
        .post-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .post-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        .comment-content {
            font-size: 1.1em;
            line-height: 1.6;
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
    <div class="container comment-container">
        <div class="card p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <div class="user-info">
                    <img src="profilep/<?php echo htmlspecialchars($comment['profile_picture']); ?>" alt="Profil Resmi">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></h5>
                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                    </div>
                </div>

                <div class="post-preview">
                    <h6 class="text-muted mb-2">Yorum Yapılan Post:</h6>
                    <p><?php echo nl2br(htmlspecialchars($comment['post_content'])); ?></p>
                    <?php if (isset($comment['post_image']) && !empty($comment['post_image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($comment['post_image']); ?>" alt="Post Görseli" class="post-image">
                    <?php endif; ?>
                </div>

                <div class="comment-content">
                    <h6 class="text-muted mb-2">Yorum:</h6>
                    <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                </div>

                <div class="mt-4">
                    <a href="view_comments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
