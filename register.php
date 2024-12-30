<?php
// Veritabanı bağlantısını dahil edelim
require_once 'db.php';

// Eğer form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kullanıcıdan gelen verileri alalım
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];  // Kullanıcı adı
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];  // Şifre doğrulama

    // Hataları tutacak bir dizi
    $errors = [];

    // Şifrelerin eşleşip eşleşmediğini kontrol et
    if ($password !== $confirm_password) {
        $errors[] = "Şifreler eşleşmiyor!";
    }

    // Kullanıcı adının eşsiz olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Bu kullanıcı adı zaten alınmış!";
    }

    // Şifreyi güvenli bir şekilde hashleyelim
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // SQL sorgusunu yazalım
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        // E-posta daha önce kayıtlı mı diye kontrol et
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu e-posta zaten kayıtlı!";
        } else {
            // E-posta ve kullanıcı adı benzersizse, kullanıcıyı ekleyelim
            $sql = "INSERT INTO users (first_name, last_name, email, username, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$first_name, $last_name, $email, $username, $hashed_password])) {
                echo "<p>Kullanıcı başarıyla kaydedildi!</p>";
                echo "<a href='login.php'>Giriş yapmak için tıklayın</a>";
                exit();
            } else {
                $errors[] = "Bir hata oluştu, lütfen tekrar deneyin.";
            }
        }
    }
}

$page_name = 'register';
$sql = "SELECT * FROM page_meta WHERE page_name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$page_name]);
$page_meta = $stmt->fetch(PDO::FETCH_ASSOC);

// Eğer meta verileri varsa, sayfa başlığını ve meta açıklamasını kullan
$title = $page_meta['title'] ?? 'Varsayılan Başlık';
$description = $page_meta['description'] ?? 'Varsayılan açıklama';
$keywords = $page_meta['keywords'] ?? 'Varsayılan, Anahtar, Kelimeler';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        .card h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .card label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        .card input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .card button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .card button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        .card p {
            text-align: center;
        }
        .card a {
            color: #4CAF50;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Kullanıcı Kaydı</h2>

    <?php
    // Hata mesajlarını görüntüle
    if (!empty($errors)) {
        echo "<ul style='color:red; text-align:center;'>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    ?>

    <!-- Kayıt formu -->
    <form action="register.php" method="POST">
        <label for="first_name">Ad:</label>
        <input type="text" name="first_name" id="first_name" required><br><br>

        <label for="last_name">Soyad:</label>
        <input type="text" name="last_name" id="last_name" required><br><br>

        <label for="email">E-posta:</label>
        <input type="email" name="email" id="email" required><br><br>

        <label for="username">Kullanıcı Adı:</label>
        <input type="text" name="username" id="username" required><br><br>

        <label for="password">Şifre:</label>
        <input type="password" name="password" id="password" required><br><br>

        <label for="confirm_password">Şifreyi Tekrarla:</label>
        <input type="password" name="confirm_password" id="confirm_password" required><br><br>

        <button type="submit">Kayıt Ol</button>
    </form>

    <p>Hesabınız zaten var mı? <a href="login.php">Giriş Yapın</a></p>
</div>


</body>
</html><!-- Meta Tagları -->
<meta name='title' content='Kayıt Ol'>
<meta name='description' content='Kayıt Ol'>
<meta name='keywords' content='Kayıt Ol'>
