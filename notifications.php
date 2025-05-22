<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Bildirimleri veritabanından çek
$sql = "SELECT 
    notifications.*,
    users.username,
    CONCAT('profilep/', users.profile_picture) as profile_picture,
    posts.content as post_content
FROM notifications 
JOIN users ON notifications.from_user_id = users.id
LEFT JOIN posts ON notifications.post_id = posts.id
WHERE notifications.to_user_id = ?
ORDER BY notifications.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış bildirimleri okundu olarak işaretle
$update_sql = "UPDATE notifications SET is_read = 1 WHERE to_user_id = ? AND is_read = 0";
$update_stmt = $pdo->prepare($update_sql);
$update_stmt->execute([$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimler - Yaratıcı Geri Dönüşüm</title>
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

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .notification-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .notification-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-username {
            font-weight: 600;
            color: var(--primary-color);
            text-decoration: none;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #666;
        }

        .notification-message {
            margin: 5px 0;
        }

        .notification-unread {
            background-color: #f0f7ff;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .notification-card {
                flex-direction: column;
                text-align: center;
            }

            .notification-avatar {
                width: 60px;
                height: 60px;
            }
        }

        /* Menü için stil eklemeleri */
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

        @media (max-width: 992px) {
            .mobile-nav {
                display: flex;
            }
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
        
        <nav>
            <a href="posts.php" class="nav-link">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profilim
            </a>
            <a href="messages.php" class="nav-link">
                <i class="fas fa-envelope"></i> Mesajlar
            </a>
            <a href="notifications.php" class="nav-link active">
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
        <h2 class="mb-4">Bildirimler</h2>

        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">
                Henüz hiç bildiriminiz yok.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo !$notification['is_read'] ? 'notification-unread' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($notification['profile_picture']); ?>" 
                         alt="Profil fotoğrafı" 
                         class="notification-avatar">
                    
                    <div class="notification-content">
                        <a href="profile.php?username=<?php echo htmlspecialchars($notification['username']); ?>" 
                           class="notification-username">
                            <?php echo htmlspecialchars($notification['username']); ?>
                        </a>

                        <div class="notification-message">
                            <?php
                            switch($notification['type']) {
                                case 'like':
                                    echo 'gönderinizi beğendi';
                                    break;
                                case 'comment':
                                    echo 'gönderinize yorum yaptı: ' . htmlspecialchars($notification['content']);
                                    break;
                                case 'follow':
                                    echo 'sizi takip etmeye başladı';
                                    break;
                                default:
                                    echo htmlspecialchars($notification['content']);
                            }
                            ?>
                        </div>

                        <?php if ($notification['post_id'] && $notification['post_content']): ?>
                            <div class="notification-post-preview">
                                "<?php echo htmlspecialchars(substr($notification['post_content'], 0, 50)) . '...'; ?>"
                            </div>
                        <?php endif; ?>

                        <div class="notification-time">
                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Mobile Navigation -->
    <!-- <div class="mobile-nav">
        <a href="posts.php">
            <i class="fas fa-home"></i>
        </a>
        <a href="search.php">
            <i class="fas fa-search"></i>
        </a>
        <a href="create_post.php">
            <i class="fas fa-plus-square"></i>
        </a>
        <a href="notifications.php" class="active">
            <i class="fas fa-bell"></i>
        </a>
        <a href="profile.php">
            <i class="fas fa-user"></i>
        </a>
    </div> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        });
    </script>
</body>
</html> 