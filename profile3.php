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

    <!-- Modal -->
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
            fetch(`fetch_post_details.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('likeCount').innerText = `${data.like_count} Beğeni`;

                    const commentsList = document.getElementById('commentsList');
                    commentsList.innerHTML = '';
                    data.comments.forEach(comment => {
                        const li = document.createElement('li');
                        li.innerHTML = `<strong>${comment.username}:</strong> ${comment.comment_text}`;
                        commentsList.appendChild(li);
                    });
                })
                .catch(error => console.error('Error fetching post details:', error));
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