<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$current_user_id = $_SESSION['user_id'];

// Mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message_content'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = htmlspecialchars($_POST['message_content']);

    if (!empty($message_content)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $receiver_id, $message_content]);
    }
    echo "Mesaj gönderildi"; // AJAX için geri bildirim
    exit();
}

// Mesaj silme
if (isset($_GET['delete_message_id'])) {
    $message_id = $_GET['delete_message_id'];
    // Mesajı silme işlemi
    $delete_stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
    $delete_stmt->execute([$message_id, $current_user_id]);
    header("Location: messages.php?chat=" . $_GET['chat']);
    exit();
}

// Kullanıcı arama (gerçek zamanlı)
$search_query = $_POST['search_user'] ?? '';
$users = [];

if (!empty($search_query)) {
    $users_stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture FROM users WHERE id != ? AND (first_name LIKE ? OR last_name LIKE ?)");
    $users_stmt->execute([$current_user_id, "%$search_query%", "%$search_query%"]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mesajlaşmış kişileri listeleme
$chat_partners_stmt = $pdo->prepare("
    SELECT DISTINCT
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS user_id,
        users.first_name, users.last_name, users.profile_picture,
        (
            SELECT content 
            FROM messages m2 
            WHERE (
                (m2.sender_id = ? AND m2.receiver_id = users.id) OR 
                (m2.sender_id = users.id AND m2.receiver_id = ?)
            )
            ORDER BY m2.created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT created_at 
            FROM messages m2 
            WHERE (
                (m2.sender_id = ? AND m2.receiver_id = users.id) OR 
                (m2.sender_id = users.id AND m2.receiver_id = ?)
            )
            ORDER BY m2.created_at DESC 
            LIMIT 1
        ) as last_message_time,
        (
            SELECT COUNT(*)
            FROM messages m2
            WHERE m2.sender_id = users.id 
            AND m2.receiver_id = ? 
            AND m2.is_read = 0
        ) as unread_count
    FROM messages
    JOIN users ON users.id = CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
    WHERE (sender_id = ? OR receiver_id = ?)
    AND users.id != ?
    GROUP BY users.id
    ORDER BY last_message_time DESC
");
$chat_partners_stmt->execute([
    $current_user_id,  // CASE WHEN için
    $current_user_id,  // İlk alt sorgu için sender_id
    $current_user_id,  // İlk alt sorgu için receiver_id
    $current_user_id,  // İkinci alt sorgu için sender_id
    $current_user_id,  // İkinci alt sorgu için receiver_id
    $current_user_id,  // Üçüncü alt sorgu için receiver_id
    $current_user_id,  // JOIN için
    $current_user_id,  // WHERE sender_id için
    $current_user_id,  // WHERE receiver_id için
    $current_user_id   // AND users.id != ? için
]);
$chat_partners = $chat_partners_stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktif sohbet
$chat_user_id = $_GET['chat'] ?? null;
$messages = [];

if ($chat_user_id) {
    // Mesajları getir
    $messages_stmt = $pdo->prepare("
        SELECT 
            m.*,
            p.id as post_id,
            p.content as post_content,
            p.image as post_image,
            p.video as post_video,
            p.created_at as post_created_at,
            u.first_name as sender_name,
            u.profile_picture as sender_profile_picture
        FROM messages m 
        LEFT JOIN posts p ON m.shared_post_id = p.id 
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $messages_stmt->execute([$current_user_id, $chat_user_id, $chat_user_id, $current_user_id]);
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug için mesaj verilerini kontrol et
    error_log("Messages data: " . print_r($messages, true));

    // Okunmamış mesajları okundu olarak işaretle
    $update_stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = ? 
        AND receiver_id = ? 
        AND is_read = 0
    ");
    $update_stmt->execute([$chat_user_id, $current_user_id]);

    $chat_user_stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = ?");
    $chat_user_stmt->execute([$chat_user_id]);
    $chat_user = $chat_user_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaratıcı Geri Dönüşüm - Mesajlar</title>
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
            height: 100vh;
            overflow: hidden;
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

        /* Menu Toggle Button Styles */
        .menu-toggle {
            display: block !important;
            position: fixed !important;
            top: 15px !important;
            left: 15px !important;
            z-index: 1002 !important;
            background: white !important;
            border: none !important;
            border-radius: 50% !important;
            width: 40px !important;
            height: 40px !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            cursor: pointer !important;
            padding: 0 !important;
        }

        .burger-icon {
            position: relative !important;
            width: 20px !important;
            height: 16px !important;
            margin: 12px auto !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }

        .burger-icon span {
            display: block !important;
            width: 100% !important;
            height: 2px !important;
            background-color: #2c3e50 !important;
            border-radius: 2px !important;
            transition: all 0.3s ease !important;
            position: static !important;
        }

        .chat-area.active {
            display: flex;
        }

        body:has(.chat-area.active) .menu-toggle {
            display: none !important;
        }

        /* Fallback for browsers that don't support :has */
        .chat-area.active ~ .menu-toggle,
        .chat-area.active + .menu-toggle {
            display: none !important;
        }

        @media (max-width: 992px) {
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                height: 100vh;
                width: 100vw !important;
                max-width: 100vw !important;
                overflow-x: hidden;
                box-sizing: border-box;
                position: relative;
                left: 0;
                right: 0;
                display: flex;
                top: 0;
            }

            .chat-area {
                width: 100vw !important;
                max-width: 100vw !important;
                position: fixed;
                top: 0 !important;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 998;
                background: white;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
                overflow: hidden;
                display: none;
                flex-direction: column;
                height: 100vh;
            }

            .chat-area.active {
                display: flex;
            }

            .chat-header {
                width: 100%;
                max-width: 100%;
                padding: 15px;
                margin: 0;
                box-sizing: border-box;
                background: white;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                border-bottom: 1px solid #e9ecef;
                position: relative;
                top: 0;
            }

            .chat-messages {
                flex: 1;
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 15px;
                padding-bottom: 80px;
                box-sizing: border-box;
                overflow-y: auto;
                background: #f8f9fa;
                position: relative;
            }

            .chat-input {
                width: 100%;
                position: sticky;
                bottom: 0;
                padding: 15px;
                background: white;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 999;
                margin: 0;
                box-sizing: border-box;
                height: 65px;
            }

            .chat-input form {
                height: 100%;
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .chat-input input {
                flex: 1;
                height: 40px;
                padding: 8px 15px;
                border: 1px solid #e9ecef;
                border-radius: 20px;
                margin: 0;
                min-width: 0;
            }

            .chat-input button {
                height: 40px;
                width: 60px;
                padding: 8px;
                border-radius: 20px;
                margin: 0;
                white-space: nowrap;
                flex-shrink: 0;
            }

            /* Reset any potential margins or padding */
            .container-fluid,
            .container,
            .row,
            .col,
            .col-12,
            [class*="col-"] {
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
                max-width: 100%;
                position: relative;
                top: 0;
            }

            * {
                box-sizing: border-box !important;
                max-width: 100%;
            }

            .chat-list {
                width: 100vw !important;
                max-width: 100vw !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: white;
                z-index: 998;
                margin: 0 !important;
                padding: 0 !important;
                display: flex;
                flex-direction: column;
                overflow-x: hidden;
                box-sizing: border-box;
                flex: 1;
            }

            .chat-list-header {
                padding: 15px 15px 15px 70px !important;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                background: white;
                margin: 0;
                position: relative;
                z-index: 997;
            }

            .chat-list-items {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }

            .chat-item {
                width: 100%;
                max-width: 100%;
                padding: 15px;
                margin: 0;
            }

            .chat-area {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 998;
                background: white;
                margin: 0;
                padding: 0;
                width: 100vw;
                max-width: 100vw;
                overflow-x: hidden;
            }

            .chat-area.active {
                display: flex;
            }

            .chat-messages {
                padding-bottom: 90px;
            }

            .chat-input {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 15px;
                background: white;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 999;
            }

            .chat-input form {
                max-width: 600px;
                margin: 0 auto;
            }

            body.sidebar-active {
                overflow: hidden;
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
                display: block;
            }

            .sidebar.active {
                transform: translateX(0);
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
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .overlay.active {
                display: block;
                opacity: 1;
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

            .main-content {
                margin-top: 50px;
                height: calc(100vh - 50px);
            }

            .chat-list {
                height: calc(100vh - 50px);
            }

            .chat-area {
                top: 50px;
            }

            .chat-messages {
                padding: 15px;
                padding-bottom: 80px;
            }

            .chat-input {
                padding: 10px;
            }

            .chat-input input {
                padding: 8px 12px;
            }

            .chat-input button {
                padding: 8px 15px;
            }
        }

        .main-content {
            margin-left: var(--sidebar-width);
            height: 100vh;
            display: flex;
            padding: 0;
            overflow: hidden;
        }

        .chat-list {
            width: 350px;
            background: white;
            border-right: 1px solid var(--secondary-color);
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        .chat-list-items {
            flex: 1;
            overflow-y: auto;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            height: 100%;
            min-height: 0;
            position: relative;
        }

        .chat-list-header {
            padding: 20px;
            border-bottom: 1px solid var(--secondary-color);
            background: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            padding-left: 40px;
            border: none;
            background-color: var(--secondary-color);
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--secondary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chat-item:hover {
            background-color: var(--secondary-color);
        }

        .chat-item.active {
            background-color: var(--secondary-color);
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .chat-info {
            flex: 1;
        }

        .chat-name {
            font-weight: 600;
            margin: 0;
        }

        .chat-last-message {
            font-size: 0.85rem;
            color: #666;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-meta {
            text-align: right;
        }

        .chat-time {
            font-size: 0.75rem;
            color: #666;
        }

        .chat-unread {
            background-color: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-top: 5px;
            display: inline-block;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid var(--secondary-color);
            display: flex;
            align-items: center;
        }

        .chat-header .chat-avatar {
            margin-right: 15px;
        }

        .chat-header .chat-info {
            flex: 1;
        }

        .chat-header .chat-actions {
            display: flex;
            gap: 15px;
        }

        .chat-header .chat-actions a {
            color: #666;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .chat-messages {
            flex: 1;
            width: 100%;
            margin: 0;
            padding: 15px;
            padding-bottom: 80px;
            box-sizing: border-box;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            max-width: 70%;
            clear: both;
        }

        .message.sent {
            float: right;
        }

        .message.received {
            float: left;
        }

        .message p {
            margin: 0;
            padding: 10px 15px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .message.sent p {
            background: var(--primary-color);
            color: white;
        }

        .shared-post {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            width: 300px;
        }

        .shared-post-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .shared-post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .shared-post-date {
            font-size: 0.8em;
            color: #6c757d;
        }

        .shared-post-text {
            margin: 0;
            word-break: break-word;
        }

        .shared-post-media {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            border-radius: 8px;
        }

        .shared-post-image,
        .shared-post-video {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
        }

        .message.sent .shared-post {
            background: rgba(74, 144, 226, 0.1);
            border-color: rgba(74, 144, 226, 0.2);
        }

        .message-footer {
            margin-top: 5px;
            display: flex;
            justify-content: flex-end;
        }

        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .delete-message {
            margin-left: 5px;
            color: #dc3545;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .delete-message:hover {
            opacity: 1;
        }

        .chat-input {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 2;
            box-sizing: border-box;
            height: 65px;
            width: 100%;
        }

        .chat-input form {
            height: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .chat-input input {
            flex: 1;
            height: 40px;
            padding: 8px 15px;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            margin: 0;
            min-width: 0;
        }

        .chat-input button {
            height: 40px;
            padding: 8px 20px;
            border-radius: 20px;
            margin: 0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .chat-area.active {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .chat-header {
            width: 100%;
            max-width: 100%;
            padding: 15px;
            margin: 0;
            box-sizing: border-box;
            background: white;
            flex-shrink: 0;
        }

        .chat-messages {
            flex: 1;
            width: 100%;
            margin: 0;
            padding: 15px;
            padding-bottom: 80px;
            box-sizing: border-box;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            max-width: 70%;
            clear: both;
        }

        .message:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Burger Menü Butonu -->
    <button class="menu-toggle">
        <div class="burger-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
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
            <a href="messages.php" class="nav-link active">
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
        <!-- Chat List -->
        <div class="chat-list">
            <div class="chat-list-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Mesajlarda ara..." onkeyup="searchChats()">
                </div>
            </div>

            <div class="chat-list-items">
                <?php foreach ($chat_partners as $partner): ?>
                <div class="chat-item <?php echo $partner['user_id'] == $chat_user_id ? 'active' : ''; ?>"
                     onclick="window.location.href='messages.php?chat=<?php echo $partner['user_id']; ?>'"
                     data-user-id="<?php echo $partner['user_id']; ?>">
                    <img src="profilep/<?php echo htmlspecialchars($partner['profile_picture']); ?>" alt="Avatar" class="chat-avatar">
                    <div class="chat-info">
                        <h4 class="chat-name"><?php echo htmlspecialchars($partner['first_name'] . ' ' . $partner['last_name']); ?></h4>
                        <p class="chat-last-message"><?php echo htmlspecialchars($partner['last_message'] ?? ''); ?></p>
                    </div>
                    <div class="chat-meta">
                        <div class="chat-time"><?php echo htmlspecialchars($partner['last_message_time'] ?? ''); ?></div>
                        <?php if ($partner['unread_count'] > 0): ?>
                        <div class="chat-unread"><?php echo $partner['unread_count']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
        <?php if ($chat_user_id && $chat_user): ?>
            <div class="chat-header">
                <img src="profilep/<?php echo htmlspecialchars($chat_user['profile_picture'] ?? 'default-profile.jpg'); ?>" alt="Avatar" class="chat-avatar">
                <div class="chat-info">
                    <h4 class="chat-name"><?php echo htmlspecialchars($chat_user['first_name'] . ' ' . $chat_user['last_name']); ?></h4>
                    <p class="chat-status"><?php echo $messages[0]['sender_id'] == $current_user_id ? 'Çevrimiçi' : 'Son görülme ' . ($messages[0]['created_at'] ?? ''); ?></p>
                </div>
                <div class="chat-actions">
                    <a href="#" title="Sesli Arama"><i class="fas fa-phone"></i></a>
                    <a href="#" title="Görüntülü Arama"><i class="fas fa-video"></i></a>
                    <a href="#" title="Diğer"><i class="fas fa-ellipsis-v"></i></a>
                </div>
            </div>

            <div class="chat-messages">
                <?php foreach ($messages as $message): ?>
                    <?php
                    // Debug için mesaj verilerini kontrol et
                    error_log("Processing message: " . print_r($message, true));
                    ?>
                    <div class="message <?php echo $message['sender_id'] == $current_user_id ? 'sent' : 'received'; ?>" data-id="<?php echo $message['id']; ?>">
                        <?php if ($message['shared_post_id']): ?>
                            <div class="shared-post">
                                <div class="shared-post-content">
                                    <div class="shared-post-header">
                                        <a href="posts.php#post-<?php echo $message['shared_post_id']; ?>" style="text-decoration: none; color: inherit;">
                                            <strong>Paylaşılan Gönderi</strong>
                                            <span class="shared-post-date"><?php echo date('d.m.Y H:i', strtotime($message['post_created_at'])); ?></span>
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($message['post_content'])): ?>
                                        <a href="posts.php#post-<?php echo $message['shared_post_id']; ?>" style="text-decoration: none; color: inherit;">
                                            <p class="shared-post-text"><?php echo htmlspecialchars($message['post_content']); ?></p>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($message['post_image'])): ?>
                                        <a href="posts.php#post-<?php echo $message['shared_post_id']; ?>" style="text-decoration: none; color: inherit;">
                                            <div class="shared-post-media">
                                                <img src="uploads/<?php echo htmlspecialchars($message['post_image']); ?>" 
                                                     alt="Paylaşılan görsel" 
                                                     class="shared-post-image"
                                                     onerror="this.onerror=null; this.style.display='none';">
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($message['post_video'])): ?>
                                        <a href="posts.php#post-<?php echo $message['shared_post_id']; ?>" style="text-decoration: none; color: inherit;">
                                            <div class="shared-post-media">
                                                <video controls class="shared-post-video"
                                                       onerror="this.onerror=null; this.style.display='none';">
                                                    <source src="uploads/<?php echo htmlspecialchars($message['post_video']); ?>" type="video/mp4">
                                                    Tarayıcınız video oynatmayı desteklemiyor.
                                                </video>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="message-content"><?php echo htmlspecialchars($message['content']); ?></p>
                        <?php endif; ?>
                        <div class="message-footer">
                            <small class="message-time">
                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                <?php if ($message['sender_id'] == $current_user_id): ?>
                                    <a href="?delete_message_id=<?php echo $message['id']; ?>&chat=<?php echo $chat_user_id; ?>" 
                                       class="delete-message" 
                                       onclick="return confirm('Bu mesajı silmek istediğinize emin misiniz?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="chat-input">
                <form id="messageForm" onsubmit="return sendMessage(event)">
                <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                    <input type="text" name="message_content" id="messageInput" placeholder="Mesaj yaz..." required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
            </div>
        <?php else: ?>
            <div class="chat-placeholder">
                <div class="text-center p-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h3>Mesajlarınız</h3>
                    <p>Sohbet etmek için bir kişi seçin</p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;
            const chatArea = document.querySelector('.chat-area');

            // Chat area aktif olduğunda menu-toggle'ı gizle
            function updateMenuToggleVisibility() {
                if (chatArea && chatArea.classList.contains('active')) {
                    menuToggle.style.display = 'none';
                } else {
                    menuToggle.style.display = 'block';
                }
            }

            // Sayfa yüklendiğinde kontrol et
            updateMenuToggleVisibility();

            // Chat area sınıfı değiştiğinde kontrol et
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        updateMenuToggleVisibility();
                    }
                });
            });

            if (chatArea) {
                observer.observe(chatArea, { attributes: true });
            }

            // Menu toggle click event
            menuToggle?.addEventListener('click', function(e) {
                e.preventDefault();
                menuToggle.classList.toggle('active');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                body.classList.toggle('sidebar-active');
            });

            // Overlay click event
            overlay?.addEventListener('click', function(e) {
                e.preventDefault();
                menuToggle.classList.remove('active');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.classList.remove('sidebar-active');
            });

            // Mobil görünümde varsayılan durumu ayarla
            if (window.innerWidth <= 992) {
                const chatUserId = <?php echo $chat_user_id ? $chat_user_id : 'null'; ?>;
                const chatList = document.querySelector('.chat-list');

                if (chatUserId) {
                    // Chat ID varsa chat-area'yı göster
                    if (chatList) chatList.style.display = 'none';
                    if (chatArea) {
                        chatArea.style.display = 'flex';
                        chatArea.classList.add('active');
                        updateMenuToggleVisibility();
                    }
                } else {
                    // Chat ID yoksa chat-list'i göster
                    if (chatList) chatList.style.display = 'flex';
                    if (chatArea) {
                        chatArea.style.display = 'none';
                        chatArea.classList.remove('active');
                        updateMenuToggleVisibility();
                    }
                }
            }

            // Chat item'lara tıklama olayı ekle
            document.querySelectorAll('.chat-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (window.innerWidth <= 992) {
                        e.preventDefault();
                        const userId = this.getAttribute('data-user-id');
                        window.location.href = `messages.php?chat=${userId}`;
                    }
                });
            });

            // Geri butonuna tıklandığında
            const backButton = document.querySelector('.back-button');
            if (backButton) {
                backButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'messages.php';
                });
            }

            // ESC tuşu ile geri gitme
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth <= 992 && chatArea.classList.contains('active')) {
                    window.location.href = 'messages.php';
                }
            });

            // Ekran boyutu değiştiğinde kontrol
            window.addEventListener('resize', function() {
                const newIsMobile = window.innerWidth <= 992;
                
                if (!newIsMobile) {
                    // Desktop görünüme geçildiğinde
                    if (chatList) chatList.style.display = 'flex';
                    if (chatArea) chatArea.style.display = 'flex';
                    
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    body.classList.remove('sidebar-active');
                    menuToggle.classList.remove('active');
                } else {
                    // Mobil görünüme geçildiğinde
                    if (!chatUserId) {
                        if (chatList) chatList.style.display = 'flex';
                        if (chatArea) chatArea.classList.remove('active');
                    }
                }
            });
        });

        // Chat başlığına geri butonu ekle (mobil görünüm için)
        if (window.innerWidth <= 992) {
            const chatHeader = document.querySelector('.chat-header');
            if (chatHeader) {
                const backButton = document.createElement('button');
                backButton.className = 'back-button btn btn-link text-dark p-0 me-3';
                backButton.innerHTML = '<i class="fas fa-arrow-left"></i>';
                backButton.onclick = function(e) {
                    e.preventDefault();
                    window.location.href = 'messages.php';
                };
                chatHeader.insertBefore(backButton, chatHeader.firstChild);
            }
        }

        // Sohbet arama fonksiyonu
        function searchChats() {
            const searchInput = document.getElementById('searchInput');
            const searchText = searchInput.value.toLowerCase();
            const chatItems = document.querySelectorAll('.chat-item');

            chatItems.forEach(item => {
                const chatName = item.querySelector('.chat-name').textContent.toLowerCase();
                const lastMessage = item.querySelector('.chat-last-message').textContent.toLowerCase();
                
                if (chatName.includes(searchText) || lastMessage.includes(searchText)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Medya yükleme fonksiyonları
        function handleMediaError(element) {
            console.error('Media loading error:', element.src);
            element.style.display = 'none';
        }

        function handleMediaLoad(element) {
            element.style.display = 'block';
        }

        // Mesaj gönderme fonksiyonu
        function sendMessage(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value;

            if (!message.trim()) return false;

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mesaj başarıyla gönderildi
                    const messagesContainer = document.querySelector('.chat-messages');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message sent';
                    messageDiv.setAttribute('data-id', data.data.message_id);
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <p>${escapeHtml(data.data.message)}</p>
                            <div class="message-time">${data.data.created_at}</div>
                        </div>
                    `;
                    messagesContainer.appendChild(messageDiv);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    form.reset();

                    // Sohbet listesini güncelle
                    updateChatList();
                }
            })
            .catch(error => console.error('Error:', error));

            return false;
        }

        // HTML karakterlerini escape etme fonksiyonu
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Son mesaj zamanını takip etmek için global değişken
        let lastMessageTime = null;

        // Yeni mesajları kontrol etme fonksiyonu
        function checkNewMessages() {
            const chatUserId = <?php echo $chat_user_id ? $chat_user_id : 'null'; ?>;
            
            if (chatUserId) {
                fetch(`get_new_messages.php?chat_user_id=${chatUserId}&last_message_time=${encodeURIComponent(lastMessageTime || '')}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        const messagesContainer = document.querySelector('.chat-messages');
                        let newMessagesAdded = false;

                        data.messages.forEach(message => {
                            const existingMessage = document.querySelector(`.message[data-id="${message.id}"]`);
                            if (!existingMessage && message.id) {
                                newMessagesAdded = true;
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${message.sender_id == <?php echo $current_user_id; ?> ? 'sent' : 'received'}`;
                                messageDiv.setAttribute('data-id', message.id);
                                
                                let html = '';
                                if (message.shared_post_id) {
                                    html += `
                                        <div class="shared-post">
                                            <div class="shared-post-content">
                                                ${message.post_content ? `<p><strong>Paylaşılan Gönderi:</strong> ${escapeHtml(message.post_content)}</p>` : ''}
                                                ${message.post_image ? `
                                                    <img src="uploads/${escapeHtml(message.post_image)}" 
                                                        alt="Paylaşılan görsel" 
                                                        class="shared-post-image"
                                                        onerror="this.style.display='none'"
                                                        onload="this.style.display='block'">` : ''}
                                                ${message.post_video ? `
                                                    <video controls class="shared-post-video"
                                                        onloadedmetadata="this.style.display='block'"
                                                        onerror="this.style.display='none'">
                                                        <source src="uploads/${escapeHtml(message.post_video)}" type="video/mp4">
                                                        Tarayıcınız video oynatmayı desteklemiyor.
                                                    </video>` : ''}
                                            </div>
                                        </div>`;
                                } else {
                                    html += `<p>${escapeHtml(message.content)}</p>`;
                                }
                                
                                html += `<small class="message-time">${message.created_at}</small>`;
                                messageDiv.innerHTML = html;
                                messagesContainer.appendChild(messageDiv);
                                
                                if (!lastMessageTime || new Date(message.created_at) > new Date(lastMessageTime)) {
                                    lastMessageTime = message.created_at;
                                }
                            }
                        });

                        // Sadece yeni mesaj eklendiyse ve kullanıcı en alttaysa scroll yap
                        if (newMessagesAdded) {
                            scrollIfNeeded(messagesContainer);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // Mesajları okundu olarak işaretleme fonksiyonu
        function markMessagesAsRead(chatUserId) {
            fetch('mark_messages_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sender_id=${chatUserId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chatItem = document.querySelector(`.chat-item[data-user-id="${chatUserId}"]`);
                    if (chatItem) {
                        const unreadEl = chatItem.querySelector('.chat-unread');
                        if (unreadEl) {
                            unreadEl.remove();
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Aktif sohbet penceresini kontrol etme fonksiyonu
        function isActiveChatOpen(userId) {
            const currentChatId = <?php echo $chat_user_id ? $chat_user_id : 'null'; ?>;
            return currentChatId === parseInt(userId);
        }

        // Sohbet listesini güncelleme fonksiyonu
        function updateChatList() {
            fetch('update_chat_list.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chat_partners) {
                    data.chat_partners.forEach(partner => {
                        const chatItem = document.querySelector(`.chat-item[data-user-id="${partner.user_id}"]`);
                        if (chatItem) {
                            const lastMessageEl = chatItem.querySelector('.chat-last-message');
                            if (lastMessageEl) {
                                lastMessageEl.textContent = partner.last_message || '';
                            }

                            const timeEl = chatItem.querySelector('.chat-time');
                            if (timeEl) {
                                timeEl.textContent = partner.last_message_time || '';
                            }

                            let unreadEl = chatItem.querySelector('.chat-unread');
                            const currentChatId = <?php echo $chat_user_id ? $chat_user_id : 'null'; ?>;
                            const isActive = currentChatId === parseInt(partner.user_id);

                            if (partner.unread_count > 0 && !isActive) {
                                if (!unreadEl) {
                                    unreadEl = document.createElement('div');
                                    unreadEl.className = 'chat-unread';
                                    chatItem.querySelector('.chat-meta').appendChild(unreadEl);
                                }
                                unreadEl.textContent = partner.unread_count;
                            } else if (unreadEl) {
                                unreadEl.remove();
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Mobil görünüm için chat alanını yönetme
        function toggleChatArea(show) {
            const chatList = document.querySelector('.chat-list');
            const chatArea = document.querySelector('.chat-area');
            
            if (show && window.innerWidth <= 992) {
                chatList.style.display = 'none';
                chatArea.classList.add('active');
            } else {
                chatList.style.display = 'flex';
                chatArea.classList.remove('active');
            }
        }

        // Scroll pozisyonunu kontrol eden fonksiyon
        function checkScrollPosition() {
            const messagesContainer = document.querySelector('.chat-messages');
            if (!messagesContainer) return;

            const { scrollTop, scrollHeight, clientHeight } = messagesContainer;
            isNearBottom = (scrollHeight - (scrollTop + clientHeight)) < scrollThreshold;
        }

        // Scroll olayını dinle
        document.querySelector('.chat-messages')?.addEventListener('scroll', () => {
            userHasScrolled = true;
            checkScrollPosition();
        });

        // Yeni mesaj eklendiğinde scroll yapılıp yapılmayacağını kontrol et
        function scrollIfNeeded(messagesContainer) {
            if (!userHasScrolled || isNearBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Her 2 saniyede bir yeni mesajları kontrol et
        setInterval(checkNewMessages, 2000);
    </script>
</body>
</html>