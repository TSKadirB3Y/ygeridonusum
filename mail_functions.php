<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // SMTP sunucusu
        $mail->SMTPAuth = true;
        $mail->Username = 'ogyellowt@gmail.com'; // SMTP kullanıcı adı
        $mail->Password = 'rojhvhvvusxzywqf'; // SMTP şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Alıcılar
        $mail->setFrom('ogyellowt@gmail.com', 'Yaratıcı Geri Dönüşüm');
        $mail->addAddress($email);

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = 'E-posta Adresinizi Doğrulayın';
        $mail->Body = "
            <h2>E-posta Doğrulama</h2>
            <p>Hesabınızı doğrulamak için aşağıdaki bağlantıya tıklayın:</p>
            <p><a href='http://localhost/geridonusum/verify_email.php?token={$token}'>E-posta Adresimi Doğrula</a></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("E-posta gönderimi başarısız: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ogyellowt@gmail.com';
        $mail->Password = 'rojhvhvvusxzywqf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Alıcılar
        $mail->setFrom('ogyellowt@gmail.com', 'Yaratıcı Geri Dönüşüm');
        $mail->addAddress($email);

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = 'Şifre Sıfırlama';
        $mail->Body = "
            <h2>Şifre Sıfırlama</h2>
            <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
            <p><a href='http://localhost/geridonusum/reset_password.php?token={$token}'>Şifremi Sıfırla</a></p>
            <p>Bu bağlantı 1 saat süreyle geçerlidir.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("E-posta gönderimi başarısız: " . $mail->ErrorInfo);
        return false;
    }
}
?> 