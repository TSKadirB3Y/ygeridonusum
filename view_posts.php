<?php
require_once 'admin_check.php';
requireAdmin();

// Gönderi silme işlemi
if (isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
    } catch (PDOException $e) {
        $error = "Gönderi silinirken bir hata oluştu: " . $e->getMessage();
    }
}

// Gönderileri listele
try {
    $stmt = $pdo->query("
        SELECT p.*, u.first_name, u.last_name, u.profile_picture,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gönderiler listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gönderileri Yönet - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background: var(--secondary-color);
            color: var(--primary-color);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        .table th {
            border-top: none;
            background: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 0.9em;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .post-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .stats {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .stat-item i {
            color: #666;
        }
        .stat-count {
            color: #666;
            font-size: 0.9em;
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
        <a href="admin_panel.php" class="nav-link">
            <i class="fas fa-home"></i> Ana Sayfa
        </a>
        <a href="view_users.php" class="nav-link">
            <i class="fas fa-users"></i> Kullanıcılar
        </a>
        <a href="view_posts.php" class="nav-link active">
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

<div class="main-content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gönderileri Yönet</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Yazar</th>
                                <th>İçerik</th>
                                <th>İstatistikler</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($posts) > 0): ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['id']) ?></td>
                                        <td>
                                            <div class="user-info">
                                                <img src="profilep/<?= htmlspecialchars($post['profile_picture']) ?>" alt="Profil Resmi">
                                                <span><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="post-content" title="<?= htmlspecialchars($post['content']) ?>">
                                                <?= htmlspecialchars($post['content']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="stats">
                                                <div class="stat-item">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="stat-count"><?= $post['comment_count'] ?></span>
                                                </div>
                                                <div class="stat-item">
                                                    <i class="fas fa-heart"></i>
                                                    <span class="stat-count"><?= $post['like_count'] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="post_detail.php?id=<?= $post['id'] ?>" class="btn btn-primary btn-action">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-danger btn-action" onclick="openModal(<?= $post['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Hiç gönderi bulunmamaktadır.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gönderiyi Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bu gönderiyi silmek istediğinizden emin misiniz?
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" id="delete_post">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="delete_post" class="btn btn-danger">Sil</button>
                </form>
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

    // Modal işlemleri
    function openModal(postId) {
        document.getElementById('delete_post').value = postId;
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }
</script>

</body>
</html>
