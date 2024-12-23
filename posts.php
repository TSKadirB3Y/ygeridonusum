<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı arama
$search_query = $_POST['search_user'] ?? '';
$users = [];

if (!empty($search_query)) {
    $users_stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture FROM users WHERE id != ? AND (first_name LIKE ? OR last_name LIKE ?)");
    $users_stmt->execute([$_SESSION['user_id'], "%$search_query%", "%$search_query%"]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Beğenme işlemi
if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Kullanıcının daha önce bu postu beğenip beğenmediğini kontrol et
    $check_like = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_like);
    $check_stmt->execute([$post_id, $user_id]);

    if ($check_stmt->rowCount() > 0) {
        // Beğeniyi kaldırma
        $delete_like_query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
        $delete_stmt = $pdo->prepare($delete_like_query);
        $delete_stmt->execute([$post_id, $user_id]);
    } else {
        // Beğenme ekleme
        $like_query = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $like_stmt = $pdo->prepare($like_query);
        $like_stmt->execute([$post_id, $user_id]);
    }

    // İşlem sonrası sayfayı aynı post'a yönlendir
    header("Location: posts.php#post$post_id");
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Yorum ekleme işlemi
if (isset($_POST['add_comment'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $content = $_POST['comment_content'];

    // Yorum ekleme sorgusu
    $comment_query = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
    $comment_stmt = $pdo->prepare($comment_query);
    $comment_stmt->execute([$post_id, $user_id, $content]);

    // İşlem sonrası sayfayı aynı post'a yönlendir
    header("Location: posts.php#post$post_id");
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Yorum silme işlemi
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];
    $user_id = $_SESSION['user_id'];
    $post_id = $_GET['post_id'];

    // Yorumun sahibi olup olmadığını kontrol et
    $check_comment_owner = "SELECT * FROM comments WHERE id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_comment_owner);
    $check_stmt->execute([$comment_id, $user_id]);

    if ($check_stmt->rowCount() > 0) {
        // Yorum silme işlemi
        $delete_comment_query = "DELETE FROM comments WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_comment_query);
        $delete_stmt->execute([$comment_id]);
    }

    // Yorum silindikten sonra aynı post'a yönlendirme
    header("Location: posts.php#post" . $post_id);
    exit(); // Bu komut ile işlem bitirilir ve yönlendirilir
}

// Postları çekme
$sql = "SELECT posts.*, users.username, users.profile_picture FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Beğeni sayısını almak için
function get_like_count($post_id) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM likes WHERE post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    return $stmt->fetchColumn();
}

// Kullanıcı beğenisini kontrol etme
function has_user_liked($post_id, $user_id) {
    global $pdo;
    $sql = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id, $user_id]);
    return $stmt->rowCount() > 0;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .search-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1001;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            padding: 5px 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .search-container input {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 200px;
        }

        .search-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #45a049;
        }

        .comment-form {
            display: none;
        }

        .comments {
            display: none;
        }

        .profile-picture {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .comment-user {
            display: flex;
            align-items: center;
        }

        .comment-user img {
            margin-right: 8px;
        }

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

        .action-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .action-button:hover {
            background-color: #45a049;
        }

        img, video {
            max-width: 500px;
            height: auto;
            border-radius: 8px;
        }

        @media (max-width: 600px) {
            .bottom-nav ul {
                flex-direction: column;
            }

            .bottom-nav ul li {
                margin: 10px 0;
            }

            .bottom-nav ul li a {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Posts</h1>
        <?php foreach ($posts as $post): ?>
            <div class="post" id="post<?php echo $post['id']; ?>">
                <div class="header">
                    <img class="profile-picture" src="profilep/<?php echo $post['profile_picture'] ?: 'default-profile.jpg'; ?>" alt="Profile Picture">
                    <div class="user-info"><?php echo $post['username']; ?></div>
                </div>
                <div class="content">
                    <p><?php echo $post['content']; ?></p>
                    <?php if ($post['image']): ?>
                        <img src="uploads/<?php echo $post['image']; ?>" alt="Post Image">
                    <?php endif; ?>
                    <?php if ($post['video']): ?>
                        <video controls>
                            <source src="uploads/<?php echo $post['video']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <form action="posts.php#post<?php echo $post['id']; ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="like_post" class="like-button">
                            <?php echo has_user_liked($post['id'], $_SESSION['user_id']) ? 'Beğenmekten Vazgeç' : 'Beğen'; ?>
                        </button>
                    </form>

                    <form action="posts.php#post<?php echo $post['id']; ?>" method="POST" class="comment-form" id="comment-form-<?php echo $post['id']; ?>">
                        <textarea name="comment_content" placeholder="Yorum yap..." required></textarea>
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="add_comment">Yorum Yap</button>
                    </form>
                    <button class="action-button" onclick="toggleCommentForm(<?php echo $post['id']; ?>)">Yorum Yap</button>
                    <button class="action-button" onclick="toggleComments(<?php echo $post['id']; ?>)">Yorumlar</button>
                    <span class="like-count"><?php echo get_like_count($post['id']); ?> Beğeni</span>
                </div>

                <div class="comments" id="comments-<?php echo $post['id']; ?>">
                    <?php
                    $comments = $pdo->prepare("SELECT comments.*, users.username, users.profile_picture FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at DESC");
                    $comments->execute([$post['id']]);
                    $comments_data = $comments->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($comments_data as $comment): ?>
                        <div class="comment">
                            <div class="comment-user">
                                <img class="profile-picture" src="profilep/<?php echo $comment['profile_picture'] ?: 'default-profile.jpg'; ?>" alt="Profile Picture">
                                <div><?php echo $comment['username']; ?></div>
                            </div>
                            <div class="comment-content"><?php echo $comment['content']; ?></div>

                            <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                <a href="posts.php?delete_comment=<?php echo $comment['id']; ?>&post_id=<?php echo $post['id']; ?>">Sil</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <nav class="bottom-nav">
        <ul>
            <li><a href="posts.php"><i class="fas fa-home"></i></a></li>
            <li><a href="posts.php"><i class="fa-solid fa-compass"></i></a></li>
            <li><a href="profile.php"><i class="fa-solid fa-gear"></i></a></li>
            <li><a href="messages.php"><i class="fa-solid fa-comment"></i></a></li>
        </ul>
    </nav>

    <script>
        function toggleComments(postId) {
            var commentsSection = document.getElementById('comments-' + postId);
            if (commentsSection.style.display === 'none' || commentsSection.style.display === '') {
                commentsSection.style.display = 'block';
            } else {
                commentsSection.style.display = 'none';
            }
        }

        function toggleCommentForm(postId) {
            var commentForm = document.getElementById('comment-form-' + postId);
            if (commentForm.style.display === 'none' || commentForm.style.display === '') {
                commentForm.style.display = 'block';
            } else {
                commentForm.style.display = 'none';
            }
        }
    </script>
    


</body>
</html>