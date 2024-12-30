<?php
session_start();
require_once 'db.php';

// Eğer oturum açılmamışsa veya kullanıcı admin/batman değilse, login.php'ye yönlendir
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'batman'])) {
    header('Location: login.php');
    exit();
}

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
        $success_message = "Meta verileri başarıyla güncellendi.";
    } else {
        $error_message = "Bir hata oluştu, lütfen tekrar deneyin.";
    }

    // Sayfa dosyasına da meta verilerini yazalım
    $file_path = __DIR__ . '/' . $page_url;  // Sayfa URL'si (örneğin home.php)

    // Eğer dosya mevcut değilse oluştur
    if (!file_exists($file_path)) {
        $file_content = "<!-- Yeni Sayfa Başlatıldı -->\n";
        file_put_contents($file_path, $file_content);
    }

    // Meta verilerini dosyaya yaz
    $meta_content = "<!-- Meta Tagları -->\n";
    $meta_content .= "<meta name='title' content='$title'>\n";
    $meta_content .= "<meta name='description' content='$description'>\n";
    $meta_content .= "<meta name='keywords' content='$keywords'>\n";

    // Dosyaya meta verilerini ekle
    file_put_contents($file_path, $meta_content, FILE_APPEND);
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
    <title>Meta Verilerini Düzenle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea, select {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            color: #fff;
        }
        .success {
            background-color: #4caf50;
        }
        .error {
            background-color: #f44336;
        }

        /* Sabit Menü CSS */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #0066cc, #003d99);
            display: flex;
            justify-content: space-evenly;
            align-items: center;
            padding: 15px 0;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .bottom-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        .bottom-nav ul li {
            margin: 0 15px;
        }

        .bottom-nav ul li a {
            text-decoration: none;
            color: white;
            font-size: 24px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Meta Verilerini Düzenle</h1>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="page_url">Sayfa URL'si</label>
            <select name="page_url" id="page_url" required>
                <?php foreach ($pages as $page): ?>
                    <option value="<?= htmlspecialchars($page['page_url']) ?>" <?= (isset($page_url) && $page_url == $page['page_url']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($page['page_url']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="title">Sayfa Başlığı</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($page_meta['title'] ?? '') ?>" required>

            <label for="description">Sayfa Açıklaması</label>
            <textarea name="description" id="description" required><?= htmlspecialchars($page_meta['description'] ?? '') ?></textarea>

            <label for="keywords">Anahtar Kelimeler</label>
            <input type="text" name="keywords" id="keywords" value="<?= htmlspecialchars($page_meta['keywords'] ?? '') ?>">

            <button type="submit">Meta Verilerini Güncelle</button>
        </form>
    </div>

    <!-- Sabit Menü -->
    <nav class="bottom-nav">
        <ul>
            <li><a href="view_meta.php"><i class="fas fa-home"></i></a></li>
            <li><a href="view_users.php"><i class="fa-solid fa-user"></i></a></li>
            <li><a href="view_posts.php"><i class="fa-solid fa-square-plus"></i></a></li>
            <li><a href="view_comments.php"><i class="fa-solid fa-comment"></i></a></li>
        </ul>
    </nav>
</body>
</html>
