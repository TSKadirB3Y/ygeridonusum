<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı bilgilerini al
$username_param = isset($_GET['username']) ? $_GET['username'] : null;
$user_id = $_SESSION['user_id'];

if ($username_param) {
    $sql = "SELECT 
        users.*,
        (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as posts_count
    FROM users 
    WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username_param]);
} else {
    $sql = "SELECT 
        users.*,
        (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as posts_count
    FROM users 
    WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: 404.php");
    exit();
}

$user_id = $user['id'];

// Profil sahibi mi kontrolü
$is_own_profile = ($user_id == $_SESSION['user_id']);

// Kullanıcı bilgilerini değişkenlere ata
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$username = $user['username'] ?? '';
$bio = $user['bio'] ?? '';
$posts_count = $user['posts_count'] ?? 0;
$followers_count = 0; // Şimdilik 0 olarak ayarlıyoruz
$following_count = 0; // Şimdilik 0 olarak ayarlıyoruz
$profile_picture = 'profilep/' . ($user['profile_picture'] ?? 'default-profile.jpg');
$cover_image = 'uploads/' . ($user['cover_image'] ?? 'default-cover.jpg');
$is_following = false; // Şimdilik false olarak ayarlıyoruz

// Kullanıcının gönderilerini al
$posts_sql = "SELECT 
    posts.*,
    CONCAT('uploads/', posts.image) as image_path,
    CONCAT('uploads/', posts.video) as video_path,
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comments_count,
    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as is_liked
FROM posts 
WHERE user_id = ? 
ORDER BY created_at DESC";
$posts_stmt = $pdo->prepare($posts_sql);
$posts_stmt->execute([$_SESSION['user_id'], $user_id]);
$user_posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Profil fotoğrafı değiştirme işlemi
if (isset($_POST['change_profile_picture']) && isset($_FILES['profile_picture'])) {
    $profile_picture = $_FILES['profile_picture'];

    // Dosya geçerliliğini kontrol et
    if ($profile_picture['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);
        
        // Dosya uzantısını kontrol et
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Eski profil fotoğrafını sil
            if ($user['profile_picture'] && $user['profile_picture'] != 'default-profile.jpg') {
                $old_picture_path = 'profilep/' . $user['profile_picture'];
                if (file_exists($old_picture_path)) {
                    unlink($old_picture_path); // Eski fotoğrafı sil
                }
            }

            // Yeni profil fotoğrafını yükle
            $new_picture_name = uniqid() . '.' . $file_extension;
            $upload_path = 'profilep/' . $new_picture_name;

            // Fotoğrafı yükle
            if (move_uploaded_file($profile_picture['tmp_name'], $upload_path)) {
                // Veritabanında güncelle
                $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$new_picture_name, $user_id]);

                // Başarı mesajı
                $success_message = "Profil fotoğrafınız başarıyla güncellendi!";
                // Yeni fotoğrafı yükledikten sonra güncellenmiş fotoğrafı hemen göstermek için:
                $user['profile_picture'] = $new_picture_name;
            } else {
                $error_message = "Profil fotoğrafı yüklenirken bir hata oluştu.";
            }
        } else {
            $error_message = "Geçersiz dosya formatı. Yalnızca .jpg, .jpeg, .png, .gif formatları kabul edilir.";
        }
    } else {
        $error_message = "Fotoğraf yüklenirken bir hata oluştu.";
    }
}

// Şifre değiştirme işlemi
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Mevcut şifreyi doğrula
    if (password_verify($current_password, $user['password'])) {
        // Yeni şifrenin doğruluğunu kontrol et
        if ($new_password === $confirm_password) {
            // Yeni şifreyi güvenli bir şekilde hash'le
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Şifreyi güncelle
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$new_password_hash, $user_id]);

            $success_message = "Şifreniz başarıyla değiştirildi!";
        } else {
            $error_message = "Yeni şifreler eşleşmiyor!";
        }
    } else {
        $error_message = "Mevcut şifreniz yanlış!";
    }
}

$page_name = 'profile';
$sql = "SELECT * FROM page_meta WHERE page_name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$page_name]);
$page_meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer meta verileri varsa, sayfa başlığını ve meta açıklamasını kullan
$title = $page_meta['title'] ?? 'Varsayılan Başlık';
$description = $page_meta['description'] ?? 'Varsayılan açıklama';
$keywords = $page_meta['keywords'] ?? 'Varsayılan, Anahtar, Kelimeler';

// Takipçi ve takip edilen sayılarını al
$followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followers_stmt->execute([$user_id]);
$followers_count = $followers_stmt->fetchColumn();

$following_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_stmt->execute([$user_id]);
$following_count = $following_stmt->fetchColumn();

// Giriş yapmış kullanıcının bu profili takip edip etmediğini kontrol et
if ($user_id != $_SESSION['user_id']) {
    $follow_check = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $follow_check->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $follow_check->fetchColumn() > 0;
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

        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }

        .profile-cover {
            height: 200px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            background-size: cover;
            background-position: center;
            margin: -30px -30px 0;
        }

        .profile-info {
            display: flex;
            align-items: flex-end;
            margin-top: -60px;
            padding: 0 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background-size: cover;
            background-position: center;
            margin-right: 20px;
        }

        .profile-details {
            flex-grow: 1;
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }

        .profile-username {
            color: #666;
            margin: 5px 0;
        }

        .profile-bio {
            margin: 15px 0;
            line-height: 1.6;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
            margin: 20px 0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit-profile {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-edit-profile:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        .profile-tabs {
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            padding: 15px;
        }

        .nav-tabs {
            border: none;
            gap: 10px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: var(--secondary-color);
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .post-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .post-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .post-header {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--secondary-color);
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
            color: var(--text-color);
            text-decoration: none;
            display: block;
            margin-bottom: 2px;
        }

        .post-time {
            font-size: 0.85rem;
            color: #666;
        }

        .post-content {
            padding: 15px;
        }

        .post-content p {
            margin-bottom: 15px;
        }

        .post-image, .post-video {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background-color: #f8f9fa;
            margin-bottom: 15px;
            border-radius: 0;
        }

        .post-video {
            outline: none;
        }

        .post-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1050;
            overflow-y: auto;
        }

        .post-modal-content {
            display: flex;
            background: white;
            width: 90%;
            max-width: 1200px;
            margin: 50px auto;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .post-modal-left {
            flex: 1;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .post-modal-left .post-content {
width: 100%;
            height: 100%;
display: flex;
            flex-direction: column;
align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            background: #f8f9fa;
        }

        .post-modal-left img,
        .post-modal-left video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-height: calc(80vh - 60px);
            margin: 0;
            border-radius: 0;
        }

        .post-modal-right {
            width: 400px;
            display: flex;
            flex-direction: column;
            border-left: 1px solid var(--secondary-color);
        }

        .post-modal-comments {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .comment {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-content {
            flex: 1;
            background-color: var(--secondary-color);
            padding: 10px 15px;
            border-radius: 15px;
        }

        .comment-username {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .comment-text {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.4;
            color: var(--text-color);
        }

        .post-modal-actions {
            padding: 15px;
            border-top: 1px solid var(--secondary-color);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .post-modal-content {
                flex-direction: column;
                margin: 0;
                width: 100%;
                height: 100%;
            }

            .post-modal-right {
                width: 100%;
            }

            .post-modal-left {
                height: 50vh;
            }

            .post-modal-left img,
            .post-modal-left video {
                max-height: 50vh;
                border-radius: 0;
            }
        }

        @media (max-width: 768px) {
            .post-image, .post-video {
                height: 300px;
                border-radius: 0;
            }
        }

        .mobile-nav {
            display: none;
        }

        @media (max-width: 992px) {
            .mobile-nav {
                display: flex;
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
                padding: 15px;
            }

            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 0 10px;
            }

            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
                width: 100px;
                height: 100px;
            }

            .profile-stats {
                justify-content: center;
                flex-wrap: wrap;
                gap: 20px;
            }

            .profile-actions {
                justify-content: center;
            }

            .post-modal-content {
                flex-direction: column;
                margin: 0;
                width: 100%;
                height: 100%;
                border-radius: 0;
            }

            .post-modal-right {
                width: 100%;
            }

            .post-modal-left {
                height: 50vh;
            }

            .modal-close {
                top: 10px;
                right: 10px;
            }

            .post-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 20px;
                margin-bottom: 15px;
            }

            .profile-cover {
                height: 150px;
                margin: -20px -20px 0;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-bio {
                font-size: 0.9rem;
                margin: 10px 0;
            }

            .post-image, .post-video {
                height: 300px;
                border-radius: 0;
            }

            .post-card {
                margin-bottom: 15px;
            }

            .post-content {
                padding: 12px;
            }

            .post-content p {
                font-size: 0.95rem;
                margin-bottom: 12px;
            }

            .comment {
                margin-bottom: 12px;
            }

            .comment-content {
                padding: 8px 12px;
            }

            .comment-text {
                font-size: 0.9rem;
            }

            .mobile-nav {
                padding: 8px;
            }

            .mobile-nav a {
                padding: 8px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .profile-avatar {
                width: 80px;
                height: 80px;
            }

            .profile-cover {
                height: 120px;
            }

            .profile-name {
                font-size: 1.3rem;
            }

            .profile-username {
                font-size: 0.9rem;
            }

            .stat-value {
                font-size: 1rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .post-header {
                padding: 12px;
            }

            .post-avatar {
                width: 32px;
                height: 32px;
            }

            .post-username {
                font-size: 0.9rem;
            }

            .post-time {
                font-size: 0.8rem;
            }

            .post-image, .post-video {
                height: 250px;
                border-radius: 0;
            }

            .mobile-nav {
                padding: 5px;
            }

            .mobile-nav a {
                padding: 5px;
                font-size: 1.1rem;
            }
        }

        /* Burger menü için CSS kodları */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            padding: 0;
            transition: all 0.3s ease;
        }

        .burger-icon {
            position: relative;
            width: 20px;
            height: 16px;
            margin: auto;
        }

        .burger-icon span {
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: var(--text-color);
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .burger-icon span:nth-child(1) {
            top: 0;
        }

        .burger-icon span:nth-child(2) {
            top: 7px;
        }

        .burger-icon span:nth-child(3) {
            bottom: 0;
        }

        .menu-toggle.active .burger-icon span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active .burger-icon span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active .burger-icon span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                background: white;
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 1000;
                padding: 20px;
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                transition: transform 0.3s ease-in-out;
                display: block !important;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
                padding-top: 60px;
                padding-bottom: 70px;
            }

            .mobile-nav {
                display: flex;
            }

            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                backdrop-filter: blur(2px);
                transition: all 0.3s ease;
            }

            .overlay.active {
                display: block;
            }

            body.sidebar-active {
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

        .follow-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .follow-btn.following {
            background-color: var(--primary-color);
            color: white;
        }

        .follow-btn.not-following {
            background-color: #eee;
            color: var(--text-color);
        }

        .follow-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Menü toggle butonu -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <div class="burger-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>

    <!-- Overlay -->
    <div class="overlay" onclick="toggleSidebar()"></div>

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
            <a href="view_meta.php" class="nav-link">
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
        <div class="profile-header">
            <div class="profile-cover" style="background-image: url('<?php echo htmlspecialchars($cover_image); ?>')"></div>
            <div class="profile-info">
                <div class="profile-avatar" style="background-image: url('<?php echo htmlspecialchars($profile_picture); ?>')"></div>
                <div class="profile-details">
                    <h1 class="profile-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($username); ?></p>
                    <p class="profile-bio"><?php echo htmlspecialchars($bio); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $posts_count; ?></div>
                            <div class="stat-label">Gönderiler</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="followersCount"><?php echo $followers_count; ?></div>
                            <div class="stat-label">Takipçiler</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $following_count; ?></div>
                            <div class="stat-label">Takip Edilenler</div>
                        </div>
                    </div>

                    <?php if ($is_own_profile): ?>
                    <div class="profile-actions">
                        <a href="edit_profile.php" class="btn btn-edit-profile">
                            Profili Düzenle
                        </a>
                    </div>
            <?php else: ?>
                    <div class="profile-actions">
                        <button id="followButton" class="btn <?php echo $is_following ? 'btn-danger' : 'btn-primary'; ?> rounded-pill" onclick="toggleFollow(<?php echo $user_id; ?>)">
                            <?php echo $is_following ? 'Takibi Bırak' : 'Takip Et'; ?>
                        </button>
                        <button class="btn btn-outline-primary rounded-pill">Mesaj Gönder</button>
                    </div>
            <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="post-grid">
            <?php foreach ($user_posts as $post): ?>
            <div class="post-card" id="post<?php echo $post['id']; ?>" onclick="showPostModal(<?php echo $post['id']; ?>)">
                <div class="post-header">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Avatar" class="post-avatar">
                    <div class="post-user-info">
                        <a href="profile.php?username=<?php echo htmlspecialchars($username); ?>" class="post-username">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                        <span class="post-time"><?php echo htmlspecialchars($post['created_at']); ?></span>
                    </div>
            </div>
                <div class="post-content">
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                    <?php if (!empty($post['video_path'])): ?>
                        <video controls class="post-video">
                            <source src="<?php echo htmlspecialchars($post['video_path']); ?>" type="video/mp4">
                            Tarayıcınız video oynatmayı desteklemiyor.
                        </video>
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <div class="post-action">
                        <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span><?php echo $post['likes_count']; ?></span>
                    </div>
                    <div class="post-action">
                        <i class="far fa-comment"></i>
                        <span><?php echo $post['comments_count']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        </div>

    <!-- Post Modal -->
    <div class="post-modal" id="postModal">
        <span class="modal-close" onclick="closePostModal()">&times;</span>
        <div class="post-modal-content">
            <div class="post-modal-left" id="postModalMedia">
                <!-- Media content will be loaded here -->
            </div>
            <div class="post-modal-right">
                <div class="post-header" id="postModalHeader">
                    <!-- Post header will be loaded here -->
                </div>
                <div class="post-modal-comments" id="postModalComments">
                    <!-- Comments will be loaded here -->
                </div>
                <div class="post-modal-actions">
                    <form onsubmit="submitComment(event)" class="mt-3" id="commentForm">
                        <input type="hidden" name="post_id" id="modalPostId">
                        <div class="input-group">
                            <input type="text" class="form-control" name="comment" placeholder="Yorum yaz..." required>
                            <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPostModal(postId) {
            const modal = document.getElementById('postModal');
            const modalMedia = document.getElementById('postModalMedia');
            const modalComments = document.getElementById('postModalComments');
            const modalHeader = document.getElementById('postModalHeader');
            const post = document.getElementById('post' + postId);

            // Medya içeriğini kopyala
            const postContent = post.querySelector('.post-content').cloneNode(true);
            modalMedia.innerHTML = '';
            modalMedia.appendChild(postContent);

            // Header'ı kopyala
            const postHeader = post.querySelector('.post-header').cloneNode(true);
            modalHeader.innerHTML = '';
            modalHeader.appendChild(postHeader);

            // Post ID'sini form için ayarla
            document.getElementById('modalPostId').value = postId;

            // Yorumları yükle
            loadComments(postId);

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function loadComments(postId) {
            const modalComments = document.getElementById('postModalComments');
            
            fetch('get_comments.php?post_id=' + postId)
                .then(response => response.json())
                .then(comments => {
                    modalComments.innerHTML = comments.map(comment => `
                        <div class="comment">
                            <img src="${comment.profile_picture}" alt="Avatar" class="comment-avatar">
                            <div class="comment-content">
                                <div class="comment-username">${comment.username}</div>
                                <p class="comment-text">${comment.content}</p>
                            </div>
                        </div>
                    `).join('');
                });
        }

        function submitComment(event) {
            event.preventDefault();
            const form = event.target;
            const postId = form.querySelector('#modalPostId').value;
            const commentInput = form.querySelector('input[name="comment"]');
            const comment = commentInput.value.trim();

            if (!comment) return;

            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('comment', comment);

            fetch('add_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Yorumu başarıyla ekledikten sonra formu temizle
                    commentInput.value = '';
                    // Yorumları yeniden yükle
                    loadComments(postId);
                    // Yorum sayısını güncelle
                    updateCommentCount(postId);
                } else {
                    alert(data.error || 'Yorum eklenirken bir hata oluştu');
                }
            });
        }

        function updateCommentCount(postId) {
            const post = document.getElementById('post' + postId);
            const commentCountElement = post.querySelector('.post-action i.fa-comment').nextElementSibling;
            const currentCount = parseInt(commentCountElement.textContent);
            commentCountElement.textContent = currentCount + 1;
        }

        function closePostModal() {
            const modal = document.getElementById('postModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const modal = document.getElementById('postModal');
            if (event.target == modal) {
                closePostModal();
            }
        }

        // Sidebar toggle fonksiyonu
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;
            const menuToggle = document.querySelector('.menu-toggle');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            body.classList.toggle('sidebar-active');
            menuToggle.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const menuToggle = document.querySelector('.menu-toggle');

            // ESC tuşuna basıldığında menüyü kapat
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });

            // Ekran genişliği değiştiğinde kontrol et
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            });

            // Overlay'e tıklandığında menüyü kapat
            overlay.addEventListener('click', toggleSidebar);
        });

        function toggleFollow(userId) {
            const button = document.getElementById('followButton');
            const followersCountElement = document.getElementById('followersCount');
            
            const formData = new FormData();
            formData.append('user_id', userId);

            fetch('follow_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Buton metnini ve stilini güncelle
                    if (data.action === 'follow') {
                        button.textContent = 'Takibi Bırak';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-danger');
                        // Takipçi sayısını bir artır
                        followersCountElement.textContent = parseInt(followersCountElement.textContent) + 1;
                    } else {
                        button.textContent = 'Takip Et';
                        button.classList.remove('btn-danger');
                        button.classList.add('btn-primary');
                        // Takipçi sayısını bir azalt
                        followersCountElement.textContent = parseInt(followersCountElement.textContent) - 1;
                    }
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('İşlem sırasında bir hata oluştu');
            });
        }
    </script>
</body>
</html>