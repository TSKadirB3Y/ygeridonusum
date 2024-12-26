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
        users.first_name, users.last_name, users.profile_picture
    FROM messages
    JOIN users ON users.id = CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
    WHERE (sender_id = ? OR receiver_id = ?)
    AND users.id != ?
");
$chat_partners_stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
$chat_partners = $chat_partners_stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktif sohbet
$chat_user_id = $_GET['chat'] ?? null;
$messages = [];

if ($chat_user_id) {
    $messages_stmt = $pdo->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $messages_stmt->execute([$current_user_id, $chat_user_id, $chat_user_id, $current_user_id]);
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

    $chat_user_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $chat_user_stmt->execute([$chat_user_id]);
    $chat_user = $chat_user_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlaşma</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 25%;
            background-color: #f4f4f4;
            padding: 15px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .sidebar form {
            margin-bottom: 20px;
        }
        .sidebar form input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
        }
        .sidebar ul li a:hover {
            color: #007bff;
        }
        .sidebar ul li img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat {
            width: 75%;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .chat-messages .message {
            margin-bottom: 10px;
        }
        .chat-messages .message.sent {
            text-align: right;
        }
        .chat-messages .message .content {
            display: inline-block;
            padding: 10px;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
        }
        .chat-messages .message.received .content {
            background-color: #f4f4f4;
            color: #333;
        }
        .chat-messages .message .delete-button {
            display: inline-block;
            margin-left: 10px;
            color: red;
            cursor: pointer;
        }
        form {
            display: flex;
        }
        form textarea {
            flex: 1;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        form button {
            padding: 10px 15px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
            .chat {
                width: 100%;
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .sidebar ul li, .sidebar form input {
                font-size: 14px;
            }
            .chat-messages .message .content {
                font-size: 14px;
            }
            form textarea {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Kullanıcı Ara</h2>
        <form action="messages.php" method="POST" id="search-form">
            <input type="text" name="search_user" id="search_user" placeholder="Kullanıcı adı ara..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">Ara</button>
        </form>
        <h2>Mesajlaşan Kişiler</h2>
        <ul id="user-list">
            <?php foreach ($chat_partners as $partner): ?>
                <li>
                    <a href="messages.php?chat=<?php echo $partner['user_id']; ?>">
                        <img src="profilep/<?php echo htmlspecialchars($partner['profile_picture']); ?>" alt="Profil Resmi">
                        <?php echo htmlspecialchars($partner['first_name'] . ' ' . $partner['last_name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2>Yeni Mesaj Gönder</h2>
        <ul id="new-user-list">
            <?php foreach ($users as $user): ?>
                <li>
                    <a href="messages.php?chat=<?php echo $user['id']; ?>">
                        <img src="profilep/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profil Resmi">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chat">
        <?php if ($chat_user_id && $chat_user): ?>
            <h2><?php echo htmlspecialchars($chat_user['first_name'] . ' ' . $chat_user['last_name']); ?></h2>
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_id'] == $current_user_id ? 'sent' : 'received'; ?>">
                        <div class="content">
                            <?php echo htmlspecialchars($message['content']); ?>
                        </div>
                        <?php if ($message['sender_id'] == $current_user_id): ?>
                            <a href="messages.php?chat=<?php echo $chat_user_id; ?>&delete_message_id=<?php echo $message['id']; ?>" class="delete-button">Sil</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <form id="message-form" method="POST">
                <textarea name="message_content" id="message_content" placeholder="Mesajınızı yazın..." required></textarea>
                <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                <button type="submit">Gönder</button>
            </form>
        <?php else: ?>
            <p>Mesajlaşmak için bir kullanıcı seçin veya ara.</p>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Gerçek zamanlı arama işlemi
            $('#search_user').on('keyup', function() {
                var searchQuery = $(this).val();

                $.ajax({
                    url: 'messages.php',
                    type: 'POST',
                    data: {
                        search_user: searchQuery
                    },
                    success: function(response) {
                        var users = $(response).find('#new-user-list').html();
                        $('#new-user-list').html(users);
                    }
                });
            });

            // Mesaj gönderme AJAX işlemi
            $('#message-form').submit(function(e) {
                e.preventDefault(); // Formun normal submit işlemini engelle

                var messageContent = $('#message_content').val();
                var receiverId = $('input[name="receiver_id"]').val();

                // Mesaj boşsa işlem yapma
                if (messageContent.trim() === "") {
                    return;
                }

                $.ajax({
                    url: 'messages.php', // Mesaj gönderme işlemi aynı dosyada yapılacak
                    type: 'POST',
                    data: {
                        receiver_id: receiverId,
                        message_content: messageContent
                    },
                    success: function(response) {
                        $('#message_content').val(''); // Mesaj kutusunu temizle
                        loadMessages(); // Mesajları yeniden yükle
                    },
                    error: function(xhr, status, error) {
                        console.error("Mesaj gönderme hatası:", error);
                    }
                });
            });

            // Anlık mesajları güncellemek için
            function loadMessages() {
                var chatUserId = "<?php echo $chat_user_id; ?>";

                $.ajax({
                    url: 'messages.php', // Mesajları almak için aynı dosyayı kullanıyoruz
                    type: 'GET',
                    data: {
                        chat: chatUserId
                    },
                    success: function(response) {
                        var messages = $(response).find('#chat-messages').html();
                        $('#chat-messages').html(messages); // Mesajları güncelle
                    },
                    error: function(xhr, status, error) {
                        console.error("Mesajları yüklerken hata oluştu:", error);
                    }
                });
            }

            // Mesajları her 3 saniyede bir güncelle
            setInterval(loadMessages, 3000); // 3 saniyede bir mesajları yenile
        });
    </script>
</body>
</html>