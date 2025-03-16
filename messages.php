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
        SELECT m.*, p.content as post_content, p.image as post_image, p.video as post_video 
        FROM messages m 
        LEFT JOIN posts p ON m.shared_post_id = p.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $messages_stmt->execute([$current_user_id, $chat_user_id, $chat_user_id, $current_user_id]);
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

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

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
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

        .main-content {
            margin-left: var(--sidebar-width);
            height: 100vh;
            display: flex;
        }

        .chat-list {
            width: 350px;
            background: white;
            border-right: 1px solid var(--secondary-color);
    overflow-y: auto;
}

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
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

        .chat-list-header {
            padding: 20px;
            border-bottom: 1px solid var(--secondary-color);
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
            padding: 20px;
    overflow-y: auto;
            background-color: #f8f9fa;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 60%;
            padding: 12px 15px;
            border-radius: 15px;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .message.received .message-content {
            background-color: white;
            margin-left: 10px;
        }

        .message.sent .message-content {
            background-color: var(--primary-color);
    color: white;
            margin-right: 10px;
        }

        .message-time {
            font-size: 0.75rem;
            color: #666;
            margin-top: 5px;
        }

        .chat-input {
            padding: 20px;
            border-top: 1px solid var(--secondary-color);
            background: white;
        }

        .chat-input form {
    display: flex;
            gap: 10px;
            position: relative;
            z-index: 1001;
}

        .chat-input input {
    flex: 1;
            padding: 12px 15px;
            border: none;
            background-color: var(--secondary-color);
            border-radius: 20px;
            font-size: 0.95rem;
        }

        .chat-input button {
            background-color: var(--primary-color);
    color: white;
    border: none;
            padding: 0 20px;
            border-radius: 20px;
    cursor: pointer;
            transition: all 0.3s ease;
            min-width: 50px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-input button:hover {
            background-color: #357abd;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
            }

            .chat-list {
                width: 100%;
            }

            .chat-area {
                display: none;
            }

            .chat-area.active {
                display: flex;
    position: fixed;
                top: 0;
                left: 0;
                right: 0;
    bottom: 0;
    z-index: 1000;
}

            .chat-messages {
                padding-bottom: 80px;
            }

            .chat-input {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 15px;
                background: white;
                box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
                z-index: 1002;
            }

            .chat-input form {
                max-width: 600px;
                margin: 0 auto;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay.active {
                display: block;
            }

            body.menu-open {
                overflow: hidden;
            }
        }

        @media (max-width: 576px) {
            .chat-input {
                padding: 10px;
            }

            .chat-messages {
                padding-bottom: 20px;
            }
        }

        .shared-post {
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin: 5px 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .shared-post:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .message.sent .shared-post {
            background: rgba(255, 255, 255, 0.1);
        }

        .message.sent .shared-post:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .shared-post-image {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .shared-post-video {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
            pointer-events: none;
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
            <a href="messages.php" class="nav-link active">
                <i class="fas fa-envelope"></i> Mesajlar
            </a>
            <a href="notifications.php" class="nav-link">
                <i class="fas fa-bell"></i> Bildirimler
            </a>
            <a href="posts.php" class="nav-link">
                <i class="fas fa-search"></i> Keşfet
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
                    <div class="message <?php echo $message['sender_id'] == $current_user_id ? 'sent' : 'received'; ?>">
                    <?php if ($message['sender_id'] != $current_user_id): ?>
                    <img src="profilep/<?php echo htmlspecialchars($chat_user['profile_picture'] ?? 'default-profile.jpg'); ?>" alt="Avatar" class="chat-avatar">
                        <?php endif; ?>
                    <div class="message-content">
                        <?php if ($message['shared_post_id']): ?>
                            <div class="message-content">
                                <a href="posts.php#post-<?php echo $message['shared_post_id']; ?>" class="shared-post" data-post-id="post-<?php echo $message['shared_post_id']; ?>" onclick="window.location.href='posts.php#post-<?php echo $message['shared_post_id']; ?>'">
                                    <p><strong>Paylaşılan Gönderi:</strong></p>
                                    <p><?php echo htmlspecialchars($message['post_content']); ?></p>
                                    <?php if ($message['post_image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($message['post_image']); ?>" alt="Paylaşılan görsel" class="shared-post-image">
                                    <?php endif; ?>
                                </a>
                                <div class="message-time"><?php echo htmlspecialchars($message['created_at']); ?></div>
                        </div>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($message['content']); ?></p>
                        <?php endif; ?>
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
                        data.messages.forEach(message => {
                            // Mesajın zaten var olup olmadığını kontrol et
                            const existingMessage = document.querySelector(`.message[data-id="${message.id}"]`);
                            if (!existingMessage && message.id) {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${message.sender_id == <?php echo $current_user_id; ?> ? 'sent' : 'received'}`;
                                messageDiv.setAttribute('data-id', message.id);
                                
                                let html = '';
                                if (message.sender_id != <?php echo $current_user_id; ?>) {
                                    html += `<img src="profilep/${message.profile_picture || 'default-profile.jpg'}" alt="Avatar" class="chat-avatar">`;
                                }

                                html += '<div class="message-content">';
                                
                                if (message.shared_post_id) {
                                    html += `
                                        <a href="posts.php#post-${message.shared_post_id}" class="shared-post" data-post-id="post-${message.shared_post_id}" onclick="window.location.href='posts.php#post-${message.shared_post_id}'">
                                            <p><strong>Paylaşılan Gönderi:</strong></p>
                                            <p>${escapeHtml(message.post_content || '')}</p>
                                            ${message.post_image ? `<img src="uploads/${message.post_image}" alt="Paylaşılan görsel" class="shared-post-image">` : ''}
                                        </a>
                                    `;
                                } else {
                                    html += `<p>${escapeHtml(message.content || '')}</p>`;
                                }
                                
                                html += `<div class="message-time">${message.created_at}</div></div>`;
                                
                                messageDiv.innerHTML = html;
                                messagesContainer.appendChild(messageDiv);
                                
                                // Son mesaj zamanını güncelle
                                if (!lastMessageTime || new Date(message.created_at) > new Date(lastMessageTime)) {
                                    lastMessageTime = message.created_at;
                                }
                            }
                        });
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Sohbet listesini güncelle
            updateChatList();
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

        // Mobil görünüm için geri butonu fonksiyonu
        function goBack() {
            window.location.href = 'messages.php';
        }

        // Sayfa yüklendiğinde çalışacak kodlar
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.querySelector('.chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                // Son mesaj zamanını başlangıçta ayarla
                const messages = messagesContainer.querySelectorAll('.message');
                if (messages.length > 0) {
                    const lastMessage = messages[messages.length - 1];
                    const timeElement = lastMessage.querySelector('.message-time');
                    if (timeElement) {
                        lastMessageTime = timeElement.textContent;
                    }
                }

                // Paylaşılan gönderilere tıklama olayını düzenle
                document.querySelectorAll('.shared-post').forEach(post => {
                    post.addEventListener('click', function(e) {
                        e.preventDefault();
                        const postId = this.getAttribute('data-post-id');
                        if (postId) {
                            window.location.href = 'posts.php#' + postId;
                        }
                    });
                });
            }

            // Mobil görünüm için chat alanını aktif et
            if (window.innerWidth <= 992 && <?php echo $chat_user_id ? 'true' : 'false'; ?>) {
                document.querySelector('.chat-list').style.display = 'none';
                document.querySelector('.chat-area').classList.add('active');
            }
        });

        // Mobil görünüm için geri butonu ekle
        if (window.innerWidth <= 992) {
            const chatHeader = document.querySelector('.chat-header');
            if (chatHeader) {
                const backButton = document.createElement('button');
                backButton.innerHTML = '<i class="fas fa-arrow-left"></i>';
                backButton.className = 'btn btn-link text-dark';
                backButton.onclick = goBack;
                chatHeader.insertBefore(backButton, chatHeader.firstChild);
            }
        }

        // Menü için yeni JavaScript fonksiyonları
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

        // Her 2 saniyede bir yeni mesajları kontrol et
        setInterval(checkNewMessages, 2000);
    </script>
</body>
</html>