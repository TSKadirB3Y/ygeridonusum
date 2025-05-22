<?php
require_once 'admin_check.php';
requireAdmin();
require_once 'db.php';

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Kullanıcılar listelenirken bir hata oluştu: " . $e->getMessage();
}

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
        header("Location: view_users.php");
        exit();
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
            header("Location: view_users.php");
            exit();
        } catch (PDOException $e) {
            $error = "Kullanıcı admin yapılırken bir hata oluştu: " . $e->getMessage();
        }
    }
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
            height: 100vh;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
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
            object-fit: cover;
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
            align-items: center;
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
                position: fixed;
                height: 100vh;
                z-index: 1000;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding-top: 80px;
                width: 100%;
            }

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
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

            .menu-toggle i {
                font-size: 1.2rem;
                color: var(--primary-color);
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
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay"></div>
    <div class="sidebar" id="sidebar">
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

            <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($users)): ?>
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
            <?php else: ?>
            <p>Kullanıcı bulunamadı.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const body = document.body;

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                body.classList.toggle('menu-open');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.classList.remove('menu-open');
            });
        });
    </script>
</body>
</html>