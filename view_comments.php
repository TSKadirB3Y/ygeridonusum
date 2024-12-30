<?php
// Oturum başlatılır
session_start();

// Veritabanı bağlantısı için db.php dosyasını dahil eder
require_once 'db.php';

// Eğer kullanıcı giriş yapmamışsa (user_id veya role oturumda yoksa), login sayfasına yönlendirilir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcının rolü alınır
$user_role = $_SESSION['role'];

// Yalnızca 'admin' veya 'batman' rollerine sahip kullanıcılar bu sayfayı görebilir
if ($user_role != 'admin' && $user_role != 'batman') {
    echo "Bu sayfayı görüntüleme izniniz yok!";
    exit;
}

// Veritabanından yorumları almak için SQL sorgusu yazılır
$sql = "SELECT * FROM comments";
$stmt = $pdo->prepare($sql); // PDO ile SQL sorgusu hazırlanır
$stmt->execute(); // Sorgu çalıştırılır
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC); // Sorgu sonucu alınır ve $comments dizisine atanır

// Yorum silme işlemi: Eğer URL'de 'delete_id' parametresi varsa, o yorum silinir
if (isset($_GET['delete_id'])) {
    $comment_id = $_GET['delete_id']; // Silinecek yorumun ID'si alınır

    // Yorumun veritabanından silinmesi için SQL sorgusu yazılır
    $sql_delete = "DELETE FROM comments WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete); // PDO ile parametrik sorgu hazırlanır
    $stmt_delete->execute([$comment_id]); // Parametrik sorgu ile yorum silinir

    // Silme işleminden sonra sayfa yeniden yüklenir (view_comments.php'ye yönlendirilir)
    header("Location: view_comments.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Yorumları Görüntüle ve Sil">
    <meta name="keywords" content="Admin, Yorum, Görüntüle, Sil">
    <title>Yorumları Görüntüle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
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

        .delete-btn {
            color: white;
            background-color: #f44336; /* Red button */
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .view-btn {
            color: white;
            background-color: #2196F3; /* Blue button */
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .view-btn:hover {
            background-color: #1976D2;
        }

        .post-link {
            display: inline-block;
            background-color: #9C27B0; /* Purple link */
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .post-link:hover {
            background-color: #7B1FA2;
        }

        .view-comment-link {
            display: inline-block;
            background-color: #FF9800; /* Orange button */
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .view-comment-link:hover {
            background-color: #F57C00;
        }

        .button-container {
            text-align: center;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-buttons {
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            margin: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .confirm-btn {
            background-color: #f44336;
            color: white;
        }

        .cancel-btn {
            background-color: #2196F3;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Yorumları Görüntüle</h1>

    <table>
        <tr>
            <th>Yorum ID</th>
            <th>Yorum İçeriği</th>
            <th>Yorum Yapan</th>
            <th>Post ID</th>
            <th>Posta Yönlendir</th>
            <th>Yorumu İncele</th>
            <th>İşlem</th>
        </tr>
        
        <?php if (count($comments) > 0): ?>
            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= htmlspecialchars($comment['id']) ?></td>
                    <td><?= htmlspecialchars($comment['content']) ?></td>
                    <td><?= htmlspecialchars($comment['user_id']) ?></td>
                    <td><?= htmlspecialchars($comment['post_id']) ?></td>
                    <td>
                        <a href="posts.php#post<?= $comment['post_id'] ?>" class="post-link">Posta Git</a>
                    </td>
                    <td>
                        <a href="comment_detail.php?id=<?= $comment['id'] ?>" class="view-comment-link">Yorumu İncele</a>
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="delete-btn" onclick="openModal(<?= $comment['id'] ?>)">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Hiç yorum bulunmamaktadır.</td> <!-- 7 sütun olduğu için colspan="7" -->
            </tr>
        <?php endif; ?>
    </table>

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

<!-- Modal for confirmation -->
<div id="confirmationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Yorumu silmek istediğinizden emin misiniz?</h2>
        <div class="modal-buttons">
            <button class="modal-btn confirm-btn" id="confirmDeleteBtn">Evet, Sil</button>
            <button class="modal-btn cancel-btn" onclick="closeModal()">Hayır, İptal Et</button>
        </div>
    </div>
</div>

<script>
    let commentIdToDelete = null;

    // Modal açma
    function openModal(commentId) {
        commentIdToDelete = commentId;
        document.getElementById('confirmationModal').style.display = "block";
    }

    // Modal kapama
    function closeModal() {
        document.getElementById('confirmationModal').style.display = "none";
    }

    // Silme onayı
    document.getElementById('confirmDeleteBtn').onclick = function() {
        if (commentIdToDelete !== null) {
            window.location.href = "?delete_id=" + commentIdToDelete; // Yorumun silinmesi için yönlendirme yapılır
        }
    }
</script>

</body>
</html>