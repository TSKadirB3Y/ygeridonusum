<?php
require_once 'admin_check.php';
requireAdmin();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı rolünü kontrol et
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'batman')) {
    echo "Bu sayfaya erişim yetkiniz yok.";
    exit();
}

// Banlama işlemi
if (isset($_POST['ban_user_id'])) {
    $ban_user_id = (int)$_POST['ban_user_id'];

    // Adminlerin Batman kullanıcılarını banlamaması için kontrol
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ban_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $_SESSION['role'] == 'admin' && $user['role'] == 'batman') {
        $error_message = "Adminler Batman kullanıcılarını banlayamaz.";
    } else {
        $sql = "UPDATE users SET banned = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ban_user_id]);
        $success_message = "Kullanıcı başarıyla banlandı.";
    }
}

// Banı kaldırma işlemi
if (isset($_POST['unban_user_id'])) {
    $unban_user_id = (int)$_POST['unban_user_id'];
    $sql = "UPDATE users SET banned = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$unban_user_id]);
    $success_message = "Kullanıcının yasağı kaldırıldı.";
}

// Kullanıcıları listele
$sql = "SELECT id, first_name, last_name, email, role, banned FROM users";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı rolü değiştirme işlemi
if (isset($_POST['change_role_user_id']) && isset($_POST['new_role'])) {
    // Sadece batman rolüne sahip kullanıcılar rol değiştirebilir
    if ($_SESSION['role'] !== 'batman') {
        $error = "Bu işlem için yetkiniz bulunmamaktadır.";
    } else {
        $change_role_user_id = (int)$_POST['change_role_user_id'];
        $new_role = $_POST['new_role'];

        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_role, $change_role_user_id]);
        $success_message = "Kullanıcının rolü başarıyla değiştirildi.";
    }
}

// Kullanıcı silme işlemi
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        $error = "Kullanıcı silinirken bir hata oluştu: " . $e->getMessage();
    }
}

// Kullanıcı admin yapma işlemi
if (isset($_POST['make_admin'])) {
    // Sadece batman rolüne sahip kullanıcılar admin yapabilir
    if ($_SESSION['role'] !== 'batman') {
        $error = "Bu işlem için yetkiniz bulunmamaktadır.";
    } else {
        $user_id = $_POST['user_id'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            $error = "Kullanıcı admin yapılırken bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Kullanıcıları listele
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kullanıcılar listelenirken bir hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
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

        .user-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .user-card img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .user-info {
            flex: 1;
        }

        .user-info h5 {
            margin: 0;
            font-size: 1.1rem;
        }

        .user-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .user-actions {
            display: flex;
            gap: 10px;
        }

        .btn-admin {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-admin:hover {
            background-color: #357abd;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .admin-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 10px;
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
            <a href="view_users.php" class="nav-link active">
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
            <h2 class="mb-4">Kullanıcı Yönetimi</h2>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="d-flex align-items-center">
                    <img src="profilep/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Avatar">
                    <div class="user-info">
                        <h5>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            <?php if ($user['role'] == 'admin' || $user['role'] == 'batman'): ?>
                            <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                        </h5>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <small class="text-muted">Kayıt: <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></small>
                    </div>
                    <div class="user-actions">
                        <?php if ($user['role'] != 'admin' && $user['role'] != 'batman' && $_SESSION['role'] == 'batman'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="make_admin" class="btn-admin">
                                    <i class="fas fa-user-shield"></i> Admin Yap
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] == 'batman'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="change_role_user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>Kullanıcı</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="batman" <?php echo ($user['role'] == 'batman') ? 'selected' : ''; ?>>Batman</option>
                                </select>
                            </form>
                        <?php endif; ?>

                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn-delete">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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

        document.addEventListener('DOMContentLoaded', function() {
            const profileImage = document.querySelector('.profile-image');
            const fileInput = document.querySelector('input[type="file"]');
            
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const formData = new FormData();
                        formData.append('profile_image', file);
                        formData.append('user_id', this.dataset.userId);

                        fetch('update_profile_image.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                profileImage.src = data.image_url + '?t=' + new Date().getTime();
                            } else {
                                alert('Profil fotoğrafı yüklenirken bir hata oluştu: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Hata:', error);
                            alert('Profil fotoğrafı yüklenirken bir hata oluştu.');
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>