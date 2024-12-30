<?php
session_start();
require_once 'db.php';

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendirir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı rolünü al
$user_role = $_SESSION['role'];

// Yalnızca 'admin' veya 'batman' rollerine sahip kullanıcılara izin verilir
if ($user_role != 'admin' && $user_role != 'batman') {
    echo "Bu sayfayı görüntüleme izniniz yok!";
    exit;
}

// Postları veritabanından çek
$sql = "SELECT * FROM posts";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Post silme işlemi
if (isset($_GET['delete_id'])) {
    $post_id = $_GET['delete_id'];

    // Silme işlemi
    $sql_delete = "DELETE FROM posts WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$post_id]);

    // Yönlendirme
    header("Location: view_posts.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Postları Görüntüle">
    <meta name="keywords" content="Admin, Post, Görüntüle, Sil">
    <title>Postları Görüntüle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Stil kodları */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        .delete-btn, .view-btn {
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .delete-btn {
            color: white;
            background-color: #f44336;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .view-btn {
            background-color: #1e90ff;
            color: white;
        }

        .view-btn:hover {
            background-color: #4682b4;
            transform: scale(1.05);
        }

        /* Modal (Onay penceresi) stil */
        .modal {
            display: none; /* Başlangıçta görünmez */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5); /* Siyah şeffaf arka plan */
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }

        .modal-content button {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            margin: 5px;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
        }

        .confirm-btn {
            background-color: #4CAF50;
            color: white;
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
    <h1>Postları Görüntüle</h1>

    <table>
        <tr>
            <th>Post ID</th>
            <th>Post Başlığı</th>
            <th>Yazar</th>
            <th>Görüntüle</th>
            <th>İşlem</th>
        </tr>
        
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td><?= htmlspecialchars($post['id']) ?></td>
                    <td><?= htmlspecialchars($post['content']) ?></td>
                    <td><?= htmlspecialchars($post['user_id']) ?></td>
                    <td>
                        <form action="posts.php#post<?= $post['id'] ?>" method="GET">
                            <button type="submit" class="view-btn">Görüntüle</button>
                        </form>
                    </td>
                    <td>
                        <button class="delete-btn" onclick="openModal(<?= $post['id'] ?>)">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Hiç post bulunmamaktadır.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<nav class="bottom-nav">
    <ul>
        <li><a href="view_meta.php"><i class="fas fa-home"></i></a></li>
        <li><a href="view_users.php"><i class="fa-solid fa-user"></i></a></li>
        <li><a href="view_posts.php"><i class="fa-solid fa-square-plus"></i></a></li>
        <li><a href="view_comments.php"><i class="fa-solid fa-comment"></i></a></li>
    </ul>
</nav>

<!-- Modal (Onay Penceresi) -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Bu postu silmek istediğinizden emin misiniz?</h3>
        <button class="confirm-btn" id="confirmBtn">Evet, Sil</button>
        <button class="cancel-btn" id="cancelBtn">Hayır, İptal</button>
    </div>
</div>

<script>
    // Modal'ı aç
    function openModal(postId) {
        // Modal'ı göster
        document.getElementById("confirmModal").style.display = "block";
        
        // Silme işlemi için onay
        document.getElementById("confirmBtn").onclick = function() {
            window.location.href = "?delete_id=" + postId;  // Silme işlemi
        };

        // Modal'dan çıkmak için iptal butonunu işlevlendir
        document.getElementById("cancelBtn").onclick = function() {
            document.getElementById("confirmModal").style.display = "none"; // Modal'ı kapat
        };
    }

    // Modal dışında tıklayınca kapanması
    window.onclick = function(event) {
        if (event.target == document.getElementById("confirmModal")) {
            document.getElementById("confirmModal").style.display = "none";
        }
    };
</script>

</body>
</html>
