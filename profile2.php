<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Profil bilgilerini almak için sorgu
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcıya ait gönderileri almak için sorgu
$sql_posts = "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt_posts = $pdo->prepare($sql_posts);
$stmt_posts->execute([$user_id]);
$posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .profile-container {
            text-align: center;
            margin-top: 50px;
            width: 80%;
            max-width: 1200px;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .user-info h2 {
            margin: 10px 0;
        }

        .user-info p {
            margin: 5px 0;
        }

        h3 {
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .posts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin: 0 auto;
        }

        .post {
            width: 30%;
            height: 310px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            box-sizing: border-box;
            margin-bottom: 20px;
            cursor: pointer;
            overflow: hidden;
        }

        .post img, .post video {
            width: 90%;
            height: 200px;
        
            border-radius: 8px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .post {
                width: 45%;
            }
        }

        @media (max-width: 480px) {
            .post {
                width: 100%;
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
        }

        .modal-main {
            display: flex;
            gap: 20px;
        }

        .media-container {
            flex: 1;
        }

        .details-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        #commentsSection, #likesSection {
            margin-top: 20px;
            width: 100%;
        }

        #commentsList {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        #commentsList li {
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .modal-footer {
            text-align: center;
            margin-top: 20px;
        }

        .modal-footer button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .modal-footer button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="profile-container">
        <h1>Profiliniz</h1>
        <img src="profilep/<?php echo $user['profile_picture'] ?: 'default-profile.jpg'; ?>" alt="Profil Fotoğrafı" class="profile-picture">
        
        <div class="user-info">
            <h2><?php echo $user['username']; ?></h2>
            <p>Email: <?php echo $user['email']; ?></p>
        </div>

        <h3>Paylaşımlar</h3>
        <div class="posts-container">
            <?php if (empty($posts)): ?>
                <p>Henüz paylaşımınız yok.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post" onclick="openModal('<?php echo $post['id']; ?>', '<?php echo addslashes($post['content']); ?>', '<?php echo $post['image']; ?>', '<?php echo $post['video']; ?>')">
                        <p><?php echo $post['content']; ?></p>
                        <?php if ($post['image']): ?>
                            <img src="uploads/<?php echo $post['image']; ?>" alt="Post Image">
                        <?php endif; ?>
                        <?php if ($post['video']): ?>
                            <video controls>
                                <source src="uploads/<?php echo $post['video']; ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="postModal" class="modal">
        <div class="modal-content">
            <div class="modal-main">
                <div class="media-container" id="modalMediaContainer"></div>
                <div class="details-container">
                    <h3 id="modalPostContent"></h3>
                    <div id="commentsSection">
                        <h4>Yorumlar</h4>
                        <ul id="commentsList"></ul>
                    </div>
                    <div id="likesSection">
                        <h4>Beğeniler</h4>
                        <p id="likeCount">0 Beğeni</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="likePost()">Beğen</button>
                <button onclick="commentPost()">Yorum Yap</button>
                <button onclick="closeModal()">Kapat</button>
            </div>
        </div>
    </div>
    

    <script>
        function openModal(postId, content, image, video) {
            document.getElementById('postModal').style.display = "flex";
            document.getElementById('modalPostContent').innerText = content;
            
            const mediaContainer = document.getElementById('modalMediaContainer');
            mediaContainer.innerHTML = ''; 

            if (image) {
                mediaContainer.innerHTML = `<img src="uploads/${image}" alt="Post Image">`;
            } else if (video) {
                mediaContainer.innerHTML = `<video controls><source src="uploads/${video}" type="video/mp4"></video>`;
            }

            fetchCommentsAndLikes(postId);
        }

        function fetchCommentsAndLikes(postId) {
            const comments = [
                { username: "user1", text: "Çok güzel bir paylaşım!" },
                { username: "user2", text: "Harika fikirler içeriyor." }
            ];
            const likeCount = 15;

            const commentsList = document.getElementById('commentsList');
            commentsList.innerHTML = '';
            comments.forEach(comment => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${comment.username}:</strong> ${comment.text}`;
                commentsList.appendChild(li);
            });

            document.getElementById('likeCount').innerText = `${likeCount} Beğeni`;
        }

        function closeModal() {
            document.getElementById('postModal').style.display = "none";
        }

        function likePost() {
            alert('Beğendiniz!');
        }

        function commentPost() {
            alert('Yorum yapma penceresi açılabilir!');
        }
    </script>

</body>
</html>
