<?php
require_once 'admin_check.php';
requireAdmin();

// İstatistikleri al
try {
    // Toplam kullanıcı sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Toplam gönderi sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
    $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Toplam yorum sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comments");
    $total_comments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Son kayıt olan kullanıcılar
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Son eklenen gönderiler
    $stmt = $pdo->query("SELECT p.*, u.first_name, u.last_name, u.profile_picture FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Yaratıcı Geri Dönüşüm</title>
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

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: var(--text-color);
        }

        .stat-card p {
            margin: 0;
            color: #666;
        }

        .recent-list {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .recent-list h4 {
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .recent-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--secondary-color);
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .recent-item-info {
            flex: 1;
        }

        .recent-item-info h5 {
            margin: 0;
            font-size: 0.9rem;
        }

        .recent-item-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }

        .recent-item-time {
            font-size: 0.8rem;
            color: #666;
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
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        body.menu-open {
            overflow: hidden;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                background: white;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }

            .menu-toggle {
                display: block;
            }

            body.menu-open {
                overflow: hidden;
            }
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

        .menu-toggle .burger-icon {
            display: block;
            width: 20px;
            height: 2px;
            background: var(--text-color);
            position: relative;
            margin: 10px auto;
        }

        .menu-toggle .burger-icon::before,
        .menu-toggle .burger-icon::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 2px;
            background: var(--text-color);
            transition: all 0.3s ease;
        }

        .menu-toggle .burger-icon::before {
            top: -6px;
        }

        .menu-toggle .burger-icon::after {
            bottom: -6px;
        }

        .menu-toggle.active .burger-icon {
            background: transparent;
        }

        .menu-toggle.active .burger-icon::before {
            transform: rotate(45deg);
            top: 0;
        }

        .menu-toggle.active .burger-icon::after {
            transform: rotate(-45deg);
            bottom: 0;
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
            <h1>Admin Panel</h1>
        </div>
        
        <nav>
            <a href="admin_panel.php" class="nav-link active">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="view_users.php" class="nav-link">
                <i class="fas fa-users"></i> Kullanıcılar
            </a>
            <a href="view_posts.php" class="nav-link">
                <i class="fas fa-file-alt"></i> Gönderiler
            </a>
            <a href="view_comments.php" class="nav-link">
                <i class="fas fa-comments"></i> Yorumlar
            </a>
            <a href="view_meta.php" class="nav-link">
                <i class="fas fa-cog"></i> Site Ayarları
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Genel Bakış</h2>
            
            <!-- İstatistik Kartları -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-file-alt"></i>
                        <h3><?php echo $total_posts; ?></h3>
                        <p>Toplam Gönderi</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-comments"></i>
                        <h3><?php echo $total_comments; ?></h3>
                        <p>Toplam Yorum</p>
                    </div>
                </div>
            </div>

            <!-- Son Kayıt Olan Kullanıcılar -->
            <div class="row">
                <div class="col-md-6">
                    <div class="recent-list">
                        <h4>Son Kayıt Olan Kullanıcılar</h4>
                        <?php foreach ($recent_users as $user): ?>
                        <div class="recent-item">
                            <img src="profilep/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Avatar">
                            <div class="recent-item-info">
                                <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="recent-item-time">
                                <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Son Eklenen Gönderiler -->
                <div class="col-md-6">
                    <div class="recent-list">
                        <h4>Son Eklenen Gönderiler</h4>
                        <?php foreach ($recent_posts as $post): ?>
                        <div class="recent-item">
                            <img src="profilep/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="Avatar">
                            <div class="recent-item-info">
                                <h5><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h5>
                                <p><?php echo htmlspecialchars(substr($post['content'], 0, 50)) . '...'; ?></p>
                            </div>
                            <div class="recent-item-time">
                                <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Menü için JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                menuToggle.classList.toggle('active');
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