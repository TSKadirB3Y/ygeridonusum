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
            overflow-y: auto;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
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
        }

        .post-time {
            font-size: 0.85rem;
            color: #666;
        }

        .post-content {
            margin-bottom: 15px;
        }

        .post-image {
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
            transition: color 0.3s ease;
        }

        .post-action:hover {
            color: var(--primary-color);
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

        .user-profile {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-top: auto;
            border-top: 1px solid var(--secondary-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-info h4 {
            margin: 0;
            font-size: 1rem;
        }

        .user-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
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
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        @media (max-width: 992px) {
            .mobile-nav {
                display: flex;
                justify-content: space-around;
            }

            .mobile-nav a {
                color: var(--text-color);
                text-decoration: none;
                padding: 10px;
            }

            .mobile-nav a.active {
                color: var(--primary-color);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-logo">
            <h1>SocialConnect</h1>
        </div>
        
        <nav>
            <a href="dashboard.php" class="nav-link active">
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
            <a href="search.php" class="nav-link">
                <i class="fas fa-search"></i> Keşfet
            </a>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'batman')): ?>
            <a href="admin_panel.php" class="nav-link">
                <i class="fas fa-cog"></i> Admin Paneli
            </a>
            <?php endif; ?>
        </nav>

        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Profil" class="user-avatar">
            <div class="user-info">
                <h4><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h4>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Create Post -->
        <div class="create-post">
            <textarea placeholder="Ne düşünüyorsun?" rows="3"></textarea>
            <div class="create-post-actions">
                <div class="media-upload">
                    <button class="media-upload-btn">
                        <i class="fas fa-image"></i>
                    </button>
                    <button class="media-upload-btn">
                        <i class="fas fa-video"></i>
                    </button>
                </div>
                <button class="btn btn-primary">Paylaş</button>
            </div>
        </div>

        <!-- Posts -->
        <div class="posts">
            <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-header">
                    <img src="<?php echo htmlspecialchars($post['user_avatar']); ?>" alt="Avatar" class="post-avatar">
                    <div class="post-user-info">
                        <h5 class="post-username"><?php echo htmlspecialchars($post['username']); ?></h5>
                        <span class="post-time"><?php echo htmlspecialchars($post['created_at']); ?></span>
                    </div>
                </div>
                <div class="post-content">
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <?php if (!empty($post['image'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <a href="#" class="post-action">
                        <i class="far fa-heart"></i>
                        <span><?php echo htmlspecialchars($post['likes_count']); ?></span>
                    </a>
                    <a href="#" class="post-action">
                        <i class="far fa-comment"></i>
                        <span><?php echo htmlspecialchars($post['comments_count']); ?></span>
                    </a>
                    <a href="#" class="post-action">
                        <i class="far fa-share-square"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <a href="dashboard.php" class="active">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.querySelector('.mobile-nav').addEventListener('click', function(e) {
            if (e.target.classList.contains('fa-bars')) {
                document.querySelector('.sidebar').classList.toggle('active');
            }
        });
    </script>
</body>
</html>