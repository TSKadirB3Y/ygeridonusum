<?php
session_start();

// Bildirim mesajları için session kontrolü
$notification = '';
$notification_type = '';

if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    $notification_type = $_SESSION['notification_type'];
    unset($_SESSION['notification']);
    unset($_SESSION['notification_type']);
}

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$page_name = 'posts';
$sql = "SELECT * FROM page_meta WHERE page_name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$page_name]);
$page_meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer meta verileri varsa, sayfa başlığını ve meta açıklamasını kullan
$title = $page_meta['title'] ?? 'Varsayılan Başlık';
$description = $page_meta['description'] ?? 'Varsayılan açıklama';
$keywords = $page_meta['keywords'] ?? 'Varsayılan, Anahtar, Kelimeler';

// Kullanıcı arama
$search_query = $_POST['search_user'] ?? '';
$users = [];

if (!empty($search_query)) {
    $users_stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture FROM users WHERE id != ? AND (first_name LIKE ? OR last_name LIKE ?)");
    $users_stmt->execute([$_SESSION['user_id'], "%$search_query%", "%$search_query%"]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Beğenme işlemi
if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Kullanıcının daha önce bu postu beğenip beğenmediğini kontrol et
    $check_like = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_like);
    $check_stmt->execute([$post_id, $user_id]);

    if ($check_stmt->rowCount() > 0) {
        // Beğeniyi kaldırma
        $delete_like_query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
        $delete_stmt = $pdo->prepare($delete_like_query);
        $delete_stmt->execute([$post_id, $user_id]);
    } else {
        // Beğenme ekleme
        $like_query = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $like_stmt = $pdo->prepare($like_query);
        $like_stmt->execute([$post_id, $user_id]);
    }

    // İşlem sonrası sayfayı aynı post'a yönlendir
    header("Location: posts.php#post$post_id");
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Yorum ekleme işlemi
if (isset($_POST['add_comment'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $content = $_POST['comment_content'];

    // Yorum ekleme sorgusu
    $comment_query = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
    $comment_stmt = $pdo->prepare($comment_query);
    $comment_stmt->execute([$post_id, $user_id, $content]);

    // İşlem sonrası sayfayı aynı post'a yönlendir
    header("Location: posts.php#post$post_id");
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Yorum silme işlemi
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];
    $user_id = $_SESSION['user_id'];
    $post_id = $_GET['post_id'];

    // Yorumun sahibi olup olmadığını kontrol et
    $check_comment_owner = "SELECT * FROM comments WHERE id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_comment_owner);
    $check_stmt->execute([$comment_id, $user_id]);

    if ($check_stmt->rowCount() > 0) {
        // Yorum silme işlemi
        $delete_comment_query = "DELETE FROM comments WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_comment_query);
        $delete_stmt->execute([$comment_id]);
    }

    // Yorum silindikten sonra aynı post'a yönlendirme
    header("Location: posts.php#post" . $post_id);
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Postları çekme
$sql = "SELECT 
    posts.*,
    users.username,
    CONCAT('profilep/', users.profile_picture) as profile_picture,
    CONCAT('uploads/', posts.image) as image_path,
    CONCAT('uploads/', posts.video) as video_path,
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comments_count,
    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as is_liked
FROM posts 
JOIN users ON posts.user_id = users.id
ORDER BY posts.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Beğeni sayısını almak için
function get_like_count($post_id) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM likes WHERE post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    return $stmt->fetchColumn();
}

// Kullanıcı beğenisini kontrol etme
function has_user_liked($post_id, $user_id) {
    global $pdo;
    $sql = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id, $user_id]);
    return $stmt->rowCount() > 0;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f3f6f9;
            --text-color: #2c3e50;
            --border-radius: 15px;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--text-color);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            z-index: 1000;
            overflow: hidden;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar nav {
            height: calc(100% - 100px);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding-right: 10px;
            margin-right: -10px;
        }

        /* Webkit (Chrome, Safari, Edge) için scrollbar stilini özelleştir */
        .sidebar nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar nav::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        /* Firefox için scrollbar stilini özelleştir */
        .sidebar nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 1px solid var(--secondary-color);
        }

        .brand-logo h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .nav-link.logout {
            margin-top: auto;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            margin-top: 20px;
        }

        .nav-link.logout:hover {
            background-color: #e74c3c;
            color: white;
        }

        .post-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .post-user-info {
            flex-grow: 1;
        }

        .post-username {
            font-weight: 600;
            margin: 0;
            color: var(--text-color);
            text-decoration: none;
        }

        .post-username:hover {
            color: var(--primary-color);
        }

        .post-time {
            font-size: 0.85rem;
            color: #666;
        }

        .post-content {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .post-image {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .post-video {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .post-actions {
            display: flex;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--secondary-color);
        }

        .post-action {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .post-action:hover {
            color: var(--primary-color);
        }

        .post-action.liked {
            color: #e74c3c;
        }

        .post-comments {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--secondary-color);
        }

        .comment {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-content {
            flex-grow: 1;
            background-color: var(--secondary-color);
            padding: 10px;
            border-radius: 10px;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .delete-comment-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0;
            font-size: 0.9rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .delete-comment-btn:hover {
            opacity: 1;
        }

        .comment-username {
            font-weight: 600;
            margin-bottom: 0;
        }

        .create-post {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .create-post textarea {
            width: 100%;
            border: none;
            resize: none;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 10px;
            background-color: var(--secondary-color);
        }

        .create-post textarea:focus {
            outline: none;
            background-color: #fff;
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .create-post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .media-upload {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .media-upload-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .media-upload-btn:hover {
            color: var(--primary-color);
        }

        .selected-media-name {
            color: var(--text-color);
            font-size: 0.9rem;
            margin-left: 10px;
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay.active {
                display: block;
            }

            body.menu-open {
                overflow: hidden;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 85%;
                max-width: 320px;
            }

            .menu-toggle {
                top: 10px;
                left: 10px;
                width: 35px;
                height: 35px;
            }

            .brand-logo h1 {
                font-size: 1.5rem;
            }

            .nav-link {
                padding: 15px;
                font-size: 1.1rem;
            }
        }

        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 10px;
            justify-content: space-around;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .mobile-nav a {
            color: var(--text-color);
            text-decoration: none;
            padding: 10px;
        }

        .mobile-nav a.active {
            color: var(--primary-color);
        }

        .load-more {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .load-more button {
            background-color: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .load-more button:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Burger menü ve overlay için yeni stiller */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1001;
            border: none;
            cursor: pointer;
        }

        .burger-icon {
            width: 20px;
            height: 2px;
            background: var(--text-color);
            position: relative;
            display: block;
            margin: auto;
        }

        .burger-icon:before,
        .burger-icon:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: var(--text-color);
            transition: 0.3s;
        }

        .burger-icon:before {
            top: -6px;
        }

        .burger-icon:after {
            top: 6px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay.active {
                display: block;
            }

            body.menu-open {
                overflow: hidden;
            }
        }

        .media-upload-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .selected-file-name {
            color: var(--text-color);
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .users-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .user-item:hover {
            background-color: #f8f9fa;
        }

        .user-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            margin: 0;
        }

        .share-success {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 1050;
            animation: fadeInOut 3s ease;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { opacity: 0; }
        }

        .post-actions button:hover {
            background-color: var(--secondary-color);
        }

        .notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            z-index: 1050;
            display: none;
            animation: fadeInOut 3s ease;
            min-width: 300px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translate(-50%, -60%); }
            15% { opacity: 1; transform: translate(-50%, -50%); }
            85% { opacity: 1; transform: translate(-50%, -50%); }
            100% { opacity: 0; transform: translate(-50%, -40%); }
        }
    </style>
</head>
<body>
    <!-- Burger Menü Butonu -->
    <button class="menu-toggle">
        <span class="burger-icon"></span>
    </button>

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-logo">
            <h1>Yaratıcı Geri Dönüşüm</h1>
        </div>
        
        <nav style="height: calc(100% - 100px); display: flex; flex-direction: column;">
            <a href="posts.php" class="nav-link">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profilim
            </a>
            <a href="messages.php" class="nav-link">
                <i class="fas fa-envelope"></i> Mesajlar
            </a>
            <a href="notifications.php" class="nav-link">
                <i class="fas fa-bell"></i> Bildirimler
            </a>
            <a href="posts.php" class="nav-link">
                <i class="fas fa-search"></i> Keşfet
            </a>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'batman')): ?>
            <a href="admin_panel.php" class="nav-link">
                <i class="fas fa-cog"></i> Admin Paneli
            </a>
            <?php endif; ?>
            
            <a href="logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Create Post -->
        <div class="create-post">
            <form action="create_post.php" method="post" enctype="multipart/form-data">
                <textarea name="content" placeholder="Ne düşünüyorsun?" rows="3" required></textarea>
                <div class="create-post-actions">
                    <div class="media-upload">
                        <label for="media-upload" class="media-upload-btn">
                            <i class="fas fa-image"></i>
                        </label>
                        <input type="file" id="media-upload" name="media" accept="image/*,video/*" style="display: none;">
                        <span class="selected-media-name"></span>
                    </div>
                    <button type="submit" class="btn btn-primary">Paylaş</button>
                </div>
            </form>
        </div>

        <!-- Posts -->
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
            <div class="post-card" id="post-<?php echo $post['id']; ?>">
                <div class="post-header">
                    <img src="<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="Avatar" class="post-avatar">
                    <div class="post-user-info">
                        <a href="profile.php?username=<?php echo htmlspecialchars($post['username']); ?>" class="post-username">
                            <?php echo htmlspecialchars($post['username']); ?>
                        </a>
                        <span class="post-time"><?php echo htmlspecialchars($post['created_at']); ?></span>
                    </div>
                </div>
                <div class="post-content">
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                    <?php if (!empty($post['video'])): ?>
                        <video controls class="post-video">
                            <source src="<?php echo htmlspecialchars($post['video_path']); ?>" type="video/mp4">
                            Tarayıcınız video oynatmayı desteklemiyor.
                        </video>
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <form action="posts.php" method="POST" style="display: inline;">
                        <input type="hidden" name="like_post" value="1">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" class="post-action <?php echo $post['is_liked'] ? 'liked' : ''; ?>" style="background: none; border: none; padding: 0; cursor: pointer;">
                            <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                            <span><?php echo $post['likes_count']; ?></span>
                        </button>
                    </form>
                    <button class="post-action" onclick="showComments(<?php echo $post['id']; ?>)" style="background: none; border: none; padding: 0; cursor: pointer;">
                        <i class="far fa-comment"></i>
                        <span><?php echo $post['comments_count']; ?></span>
                    </button>
                    <button class="post-action" onclick="openShareModal(<?php echo $post['id']; ?>)" style="background: none; border: none; padding: 0; cursor: pointer;">
                        <i class="far fa-share-square"></i>
                    </button>
                </div>

                <!-- Comments Section -->
                <div class="post-comments" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                    <?php
                    $comments = $pdo->prepare("SELECT 
                        comments.*, 
                        users.username, 
                        CONCAT('profilep/', users.profile_picture) as profile_picture 
                    FROM comments 
                    JOIN users ON comments.user_id = users.id 
                    WHERE post_id = ? 
                    ORDER BY comments.created_at DESC");
                    $comments->execute([$post['id']]);
                    $comments_data = $comments->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($comments_data as $comment): ?>
                    <div class="comment" data-comment-id="<?php echo $comment['id']; ?>">
                        <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" alt="Avatar" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-header">
                                <div class="comment-username"><?php echo htmlspecialchars($comment['username']); ?></div>
                                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                    <button class="delete-comment-btn" onclick="deleteComment(<?php echo $comment['id']; ?>, <?php echo $post['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <p class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Add Comment Form -->
                    <form action="add_comment.php" method="POST" class="mt-3 comment-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="comment_content" placeholder="Yorum yaz...">
                            <button type="submit" class="btn btn-primary">Gönder</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="dashboard.php">
            <i class="fas fa-home"></i>
        </a>
        <a href="search.php">
            <i class="fas fa-search"></i>
        </a>
        <a href="create_post.php">
            <i class="fas fa-plus-square"></i>
        </a>
        <a href="notifications.php">
            <i class="fas fa-bell"></i>
        </a>
        <a href="profile.php">
            <i class="fas fa-user"></i>
        </a>
    </div>

    <!-- Paylaşım Modalı -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Gönderiyi Paylaş</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <div class="search-container mb-3">
                        <input type="text" id="userSearchInput" class="form-control" placeholder="Kullanıcı ara..." onkeyup="searchUsers()">
                    </div>
                    <div id="usersList" class="users-list" style="max-height: 300px; overflow-y: auto;">
                        <!-- Kullanıcılar buraya dinamik olarak eklenecek -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="shareSuccess" class="share-success">
        Gönderi başarıyla paylaşıldı!
    </div>

    <!-- Bildirim div'ini body içine ekleyelim -->
    <div id="notification" class="notification"></div>

    <!-- Onay Modalı -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Yorum Silme Onayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    Bu yorumu silmek istediğinize emin misiniz?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Sil</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function likePost(postId, button) {
            const form = button.closest('form');
            form.submit();
        }

        function showComments(postId) {
            const commentsSection = document.getElementById('comments-' + postId);
            if (!commentsSection) return;
            
            const isHidden = commentsSection.style.display === 'none';
            commentsSection.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                setTimeout(() => {
                    commentsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }

        // Yorum gönderme işlemi
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const postId = this.querySelector('input[name="post_id"]').value;
                const commentInput = this.querySelector('input[name="comment_content"]');
                const comment = commentInput.value;
                
                if (!comment.trim()) return;

                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('comment_content', comment);

                fetch('add_comment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        showNotification(data.message || 'Yorum eklenirken bir hata oluştu.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Yorum hatası:', error);
                    showNotification('Yorum eklenirken bir hata oluştu.', 'error');
                });
            });
        });

        // Menü için JavaScript fonksiyonları
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                body.classList.toggle('menu-open');
            }

            menuToggle.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);

            // ESC tuşu ile menüyü kapatma
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });

            // Ekran boyutu değiştiğinde kontrol
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992 && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });

            const mediaInput = document.getElementById('media-upload');
            const selectedMediaName = document.querySelector('.selected-media-name');

            mediaInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    selectedMediaName.textContent = this.files[0].name;
                } else {
                    selectedMediaName.textContent = '';
                }
            });
        });

        // Paylaşım için gerekli değişkenler
        let currentPostId = null;

        // Paylaşım modalını açma fonksiyonu
        function openShareModal(postId) {
            currentPostId = postId;
            const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
            shareModal.show();
            
            // Kullanıcıları yükle
            fetch('get_followed_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const usersList = document.getElementById('usersList');
                        usersList.innerHTML = '';
                        data.users.forEach(user => {
                            const userItem = document.createElement('div');
                            userItem.className = 'user-item';
                            userItem.innerHTML = `
                                <img src="profilep/${user.profile_picture || 'default-profile.jpg'}" alt="${user.first_name}">
                                <div class="user-info">
                                    <h6 class="user-name">${user.first_name} ${user.last_name}</h6>
                                </div>
                            `;
                            userItem.addEventListener('click', () => sharePost(user.id));
                            usersList.appendChild(userItem);
                        });
                    }
                })
                .catch(error => console.error('Kullanıcılar yüklenirken hata:', error));
        }

        // Kullanıcı arama fonksiyonu
        function searchUsers() {
            const searchText = document.getElementById('userSearchInput').value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');

            userItems.forEach(item => {
                const userName = item.querySelector('.user-name').textContent.toLowerCase();
                if (userName.includes(searchText)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Gönderiyi paylaşma fonksiyonu
        function sharePost(userId) {
            if (!currentPostId || !userId) return;

            const formData = new FormData();
            formData.append('post_id', currentPostId);
            formData.append('user_id', userId);

            fetch('share_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const shareModal = bootstrap.Modal.getInstance(document.getElementById('shareModal'));
                    shareModal.hide();
                    showNotification('Gönderi başarıyla paylaşıldı.', 'success');
                } else {
                    showNotification(data.message || 'Paylaşım yapılırken bir hata oluştu.', 'error');
                }
            })
            .catch(error => {
                console.error('Paylaşım hatası:', error);
                showNotification('Paylaşım yapılırken bir hata oluştu.', 'error');
            });
        }

        function deleteComment(commentId, postId) {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            const confirmButton = document.getElementById('confirmDelete');
            
            // Onay butonuna tıklandığında yapılacak işlem
            const handleConfirm = () => {
                confirmModal.hide();
                
                fetch('delete_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `comment_id=${commentId}&post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                        if (commentElement) {
                            commentElement.remove();
                        }
                        
                        const commentCountElement = document.querySelector(`#post-${postId} .post-action i.fa-comment`).nextElementSibling;
                        const currentCount = parseInt(commentCountElement.textContent);
                        commentCountElement.textContent = currentCount - 1;
                        
                        showNotification('Yorum başarıyla silindi.', 'success');
                    } else {
                        showNotification(data.message || 'Yorum silme işlemi sırasında bir hata oluştu.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Yorum silme hatası:', error);
                    showNotification('Yorum silme işlemi sırasında bir hata oluştu.', 'error');
                });
                
                // Event listener'ı temizle
                confirmButton.removeEventListener('click', handleConfirm);
            };
            
            // Onay butonuna click event listener ekle
            confirmButton.addEventListener('click', handleConfirm);
            
            // Modalı göster
            confirmModal.show();
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // PHP ile gelen bildirimi göster
        <?php if ($notification): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('<?php echo addslashes($notification); ?>', '<?php echo $notification_type; ?>');
            });
        <?php endif; ?>
    </script>
</body>
</html>