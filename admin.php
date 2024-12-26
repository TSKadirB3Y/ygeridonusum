<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Kullanıcı rolünü kontrol et
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'batman')) {
    echo "Bu sayfaya erişim yetkiniz yok.";
    exit();
}

// Banlama işlemi
if (isset($_POST['ban_user_id'])) {
    $ban_user_id = (int)$_POST['ban_user_id'];

    // Adminlerin Batman kullanıcılarını banlamaması için kontrol
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ban_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $_SESSION['role'] == 'admin' && $user['role'] == 'batman') {
        $error_message = "Adminler Batman kullanıcılarını banlayamaz.";
    } else {
        $sql = "UPDATE users SET banned = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ban_user_id]);
        $success_message = "Kullanıcı başarıyla banlandı.";
    }
}

// Banı kaldırma işlemi
if (isset($_POST['unban_user_id'])) {
    $unban_user_id = (int)$_POST['unban_user_id'];
    $sql = "UPDATE users SET banned = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$unban_user_id]);
    $success_message = "Kullanıcının yasağı kaldırıldı.";
}

// Kullanıcıları listele
$sql = "SELECT id, first_name, last_name, email, role, banned FROM users";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı rolü değiştirme işlemi
if (isset($_POST['change_role_user_id']) && isset($_POST['new_role'])) {
    $change_role_user_id = (int)$_POST['change_role_user_id'];
    $new_role = $_POST['new_role'];

    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_role, $change_role_user_id]);
    $success_message = "Kullanıcının rolü başarıyla değiştirildi.";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        table th {
            background-color: #f4f4f4;
        }
        .button {
            padding: 8px 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
        select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            margin: 10px 0;
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
        <h1>Admin Paneli</h1>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Ad</th>
                    <th>Soyad</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Ban Durumu</th>
                    <th>Banla</th>
                    <th>Banı Kaldır</th>
                    <th>Rol Değiştir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['first_name']) ?></td>
                        <td><?= htmlspecialchars($user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $user['banned'] ? 'Banlı' : 'Banlı Değil' ?></td>
                        <td>
                            <?php if (!$user['banned']): ?>
                                <form method="POST">
                                    <input type="hidden" name="ban_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="button">Banla</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['banned']): ?>
                                <form method="POST">
                                    <input type="hidden" name="unban_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="button">Banı Kaldır</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="change_role_user_id" value="<?= $user['id'] ?>">
                                <select name="new_role">
                                    <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="batman" <?= $user['role'] == 'batman' ? 'selected' : '' ?>>Batman</option>
                                </select>
                                <button type="submit" class="button">Değiştir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Sabit Menü -->
    <nav class="bottom-nav">
        <ul>
            <li><a href="posts.php"><i class="fas fa-home"></i></a></li>
            <li><a href="posts.php"><i class="fa-solid fa-compass"></i></a></li>
            <li><a href="profile.php"><i class="fa-solid fa-gear"></i></a></li>
            <li><a href="messages.php"><i class="fa-solid fa-comment"></i></a></li>
        </ul>
    </nav>
</body>
</html>