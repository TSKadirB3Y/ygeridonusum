<?php
session_start();
require_once 'db.php';

// Form gönderildiyse giriş işlemi yapalım
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // E-posta kontrolü
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    // Kullanıcı varsa
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Banned kullanıcı kontrolü
        if ($user['banned'] == 1) {
            $error_message = "Hesabınız banlanmıştır. Lütfen destek ile iletişime geçin.";
        } else {
            // Şifreyi doğrula
            if (password_verify($password, $user['password'])) {
                // Oturum güvenliği için ID yenile
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];  // Kullanıcı rolünü al
                
                // Yönlendirme
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Geçersiz giriş bilgileri!";
            }
        }
    } else {
        $error_message = "Geçersiz giriş bilgileri!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
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
    </style>
</head>
<body>

<div class="card">
    <h2>Giriş Yap</h2>

    <?php if (isset($error_message)): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="email">E-posta:</label>
        <input type="email" name="email" id="email" required><br><br>
        
        <label for="password">Şifre:</label>
        <input type="password" name="password" id="password" required><br><br>
        
        <button type="submit">Giriş Yap</button>
    </form>
</div>


</body>
</html>