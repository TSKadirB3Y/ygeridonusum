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
        echo "<p>Adminler Batman kullanıcılarını banlayamaz.</p>";
    } else {
        $sql = "UPDATE users SET banned = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ban_user_id]);
        echo "<p>Kullanıcı başarıyla banlandı.</p>";
    }
}

// Banı kaldırma işlemi
if (isset($_POST['unban_user_id'])) {
    $unban_user_id = (int)$_POST['unban_user_id'];
    $sql = "UPDATE users SET banned = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$unban_user_id]);
    echo "<p>Kullanıcının yasağı kaldırıldı.</p>";
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

    // Adminlerin rol değiştirmesini engelle
    if ($_SESSION['role'] == 'admin') {
        echo "<p>Adminlerin rolü değiştirilemez.</p>";
        exit();
    }

    // Batman kullanıcısı sadece rolünü değiştirememesi için kontrol
    if ($_SESSION['role'] == 'batman') {
        // Batman'in kendi rolünü değiştirememesi için kontrol
        if ($change_role_user_id == $_SESSION['user_id']) {
            echo "<p>Batman rolü kendi rolünüzü değiştiremezsiniz.</p>";
        } else {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_role, $change_role_user_id]);
            echo "<p>Kullanıcının rolü başarıyla değiştirildi.</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sayfası</title>
</head>
<body>
    <h1>Admin Sayfasına Hoşgeldiniz</h1>

    <?php if ($_SESSION['role'] == 'batman'): ?>
        <p>Batman rolüne sahipsiniz, tüm kullanıcıları yönetebilirsiniz.</p>
    <?php endif; ?>

    <h2>Kullanıcıları Yönet</h2>
    <table border="1">
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
                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo $user['banned'] ? 'Banlı' : 'Banlı Değil'; ?></td>

                    <!-- Banlama ve Banı Kaldırma -->
                    <td>
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'batman'): ?>
                            <?php if ($user['banned'] == 0): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="ban_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit">Banla</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'batman'): ?>
                            <?php if ($user['banned'] == 1): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="unban_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit">Banı Kaldır</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <!-- Rol Değiştirme: Adminler rol değiştiremez -->
                    <td>
                        <?php if ($_SESSION['role'] == 'batman'): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="change_role_user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_role">
                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="batman" <?php echo $user['role'] == 'batman' ? 'selected' : ''; ?>>Batman</option>
                                </select>
                                <button type="submit">Rolü Değiştir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>