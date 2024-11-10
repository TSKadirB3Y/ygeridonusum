<?php
session_start();
include 'register/config.php';

// Kullanıcı oturum bilgilerini kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Giriş yapmamışsa login sayfasına yönlendir
    exit;
}

// Kullanıcının admin olup olmadığını kontrol et
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM uyeler WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer kullanıcı admin değilse, login sayfasına yönlendir
if (!$user || $user['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Tüm kullanıcıları çek (Sadece admin kullanıcılar için)
$stmt = $conn->prepare("SELECT id, ad, soyad, email, status, role FROM uyeler");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conn = null;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Sayfası</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Basit CSS stili */
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        button {
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Tüm Kullanıcılar</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Ad</th>
            <th>Soyad</th>
            <th>Email</th>
            <th>Durum</th>
            <th>Yetki</th>
            <th>İşlemler</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr id="row-<?php echo $user['id']; ?>">
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['ad']); ?></td>
                <td><?php echo htmlspecialchars($user['soyad']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td id="status-<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['status']); ?></td>
                <td id="role-<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['role']); ?></td>
                <td>
                    <button id="button-<?php echo $user['id']; ?>" 
                            onclick="updateUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status'] == 'active' ? 'ban' : 'unban'; ?>')">
                        <?php echo $user['status'] == 'active' ? 'Banla' : 'Banı Kaldır'; ?>
                    </button>
                    <button onclick="updateUserRole(<?php echo $user['id']; ?>)">
                        Yetki Değiştir
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <script>
        // Kullanıcı durumunu güncelle
        function updateUserStatus(userId, action) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "updateUserStatus.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            var newStatus = response.new_status;
                            document.getElementById("status-" + userId).innerText = newStatus;
                            document.getElementById("button-" + userId).innerText = newStatus === "active" ? "Banla" : "Banı Kaldır";
                            document.getElementById("button-" + userId).setAttribute("onclick", "updateUserStatus(" + userId + ", '" + (newStatus === "active" ? "ban" : "unban") + "')");
                        } else {
                            alert(response.message || "Bir hata oluştu.");
                        }
                    } else {
                        alert("Sunucu hatası oluştu.");
                    }
                }
            };

            xhr.send("user_id=" + userId + "&action=" + action);
        }

        // Kullanıcı yetkisini güncelle
        function updateUserRole(userId) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "updateUserRole.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            var newRole = response.new_role;
                            document.getElementById("role-" + userId).innerText = newRole;
                        } else {
                            alert(response.message || "Bir hata oluştu.");
                        }
                    } else {
                        alert("Sunucu hatası oluştu.");
                    }
                }
            };

            xhr.send("user_id=" + userId);
        }
    </script>
</body>
</html>
