<?php
require_once 'admin_check.php';
requireAdmin();

// Sayfa URL'si veritabanından alınacak
$page_url = '';  // Başlangıçta boş, form gönderildiğinde veritabanından alacağız.

// Sayfa URL'ini seçildiğinde meta verilerini çekmek için
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $page_url = htmlspecialchars($_POST['page_url']);  // Formdan seçilen sayfa URL'sini alıyoruz
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $keywords = htmlspecialchars($_POST['keywords']);

    // Veritabanındaki meta verilerini güncelle
    $sql = "UPDATE page_meta SET title = ?, description = ?, keywords = ? WHERE page_url = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$title, $description, $keywords, $page_url])) {
        $success = "Meta verileri başarıyla güncellendi.";
    } else {
        $error = "Bir hata oluştu, lütfen tekrar deneyin.";
    }
}

// Sayfa URL'lerini veritabanından alalım
$sql = "SELECT DISTINCT page_url FROM page_meta";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçilen sayfaya ait meta verilerini alalım (Varsa)
if ($page_url) {
    $sql = "SELECT * FROM page_meta WHERE page_url = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$page_url]);
    $page_meta = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meta Verilerini Düzenle - Admin Panel</title>
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

        .settings-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .settings-card h3 {
            color: var(--text-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn-save {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: #357abd;
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
            <a href="admin_panel.php" class="nav-link">
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
            <a href="view_meta.php" class="nav-link active">
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
            <h2 class="mb-4">Meta Verilerini Düzenle</h2>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success" id="successAlert"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="settings-card">
                    <h3>Sayfa Meta Verileri</h3>
                    
                    <div class="form-group">
                        <label for="page_url">Sayfa URL'si</label>
                        <select name="page_url" id="page_url" class="form-control" required>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?= htmlspecialchars($page['page_url']) ?>" <?= (isset($page_url) && $page_url == $page['page_url']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($page['page_url']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Sayfa Başlığı</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?= htmlspecialchars($page_meta['title'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Sayfa Açıklaması</label>
                        <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($page_meta['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="keywords">Anahtar Kelimeler</label>
                        <input type="text" id="keywords" name="keywords" class="form-control" 
                               value="<?= htmlspecialchars($page_meta['keywords'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Meta Verilerini Güncelle
                    </button>
                </div>
            </form>
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
            const pageSelect = document.getElementById('page_url');
            const titleInput = document.getElementById('title');
            const descriptionInput = document.getElementById('description');
            const keywordsInput = document.getElementById('keywords');

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

            // Sayfa seçildiğinde meta bilgilerini getir
            pageSelect.addEventListener('change', function() {
                const selectedPage = this.value;
                
                // Debug için log
                console.log('Seçilen sayfa:', selectedPage);
                
                // AJAX isteği gönder
                fetch('get_page_meta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'page_url=' + encodeURIComponent(selectedPage)
                })
                .then(response => {
                    console.log('Sunucu yanıtı:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Alınan veri:', data);
                    if (data.success) {
                        titleInput.value = data.meta.title || '';
                        descriptionInput.value = data.meta.description || '';
                        keywordsInput.value = data.meta.keywords || '';
                    } else {
                        // Hata durumunda formu temizle
                        titleInput.value = '';
                        descriptionInput.value = '';
                        keywordsInput.value = '';
                        alert('Meta bilgileri alınamadı: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                });
            });
        });
    </script>
</body>
</html>
