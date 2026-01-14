<?php
// --------------------------------------------------------------------------
// register.php: KULLANICI KAYIT SAYFASI
// Bu sayfa, yeni kullanıcıların sisteme kayıt olmasını sağlar.
// --------------------------------------------------------------------------

// 1. ADIM: Gerekli yardımcı dosyayı çağır
// __DIR__ . '/../app/auth.php' yoluyla auth.php dosyasına ulaşılır.
// Bu dosyanın içinde veritabanı bağlantısı ve 'register()' fonksiyonu bulunur.
require_once __DIR__ . '/../app/auth.php';

// Ekrana basılacak mesajları tutmak için değişkenleri boş olarak tanımla
$message = '';
$msgClass = '';

// 2. ADIM: Form gönderildi mi kontrol et (Butona basıldı mı?)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Formdan gelen verileri al ve kenarlarındaki gereksiz boşlukları sil (trim)
    // ?? '' ifadesi: Eğer veri gelmezse hata vermesin, boş kabul etsin demektir.
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? ''); // E-posta alanını formdan al
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['password_confirm'] ?? '');

    // 3. ADIM: Şifreler uyuşuyor mu kontrol et
    if ($password !== $confirm) {
        $message = 'Şifreler eşleşmiyor!'; // Hata mesajını ayarla
        $msgClass = 'error';               // CSS sınıfını kırmızı yap
    } else {
        // 4. ADIM: Kayıt Fonksiyonunu Çağır
        // auth.php içindeki register() fonksiyonuna verileri gönderiyoruz.
        // Bu fonksiyon veritabanına INSERT işlemi yapar.
        $result = register($username, $email, $password);

        // Fonksiyondan dönen sonucu (mesaj ve başarı durumu) değişkenlere ata
        $message = $result['message'];
        
        // İşlem başarılıysa 'success' (yeşil), değilse 'error' (kırmızı) sınıfını kullan
        $msgClass = $result['success'] ? 'success' : 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - StokIQ</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link rel="stylesheet" href="assets.css">
    
    <style>.alert.success { background: #e7fff1; color: #138a49; border: 1px solid #b9f1cf; }</style>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-logo">StokIQ</div>
        <h2>Kayıt Ol</h2>
        <p class="subtitle">Yeni hesap oluşturun</p>

        <?php if ($message): ?>
            <div class="alert <?php echo $msgClass; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post" class="form">
            
            <label>Kullanıcı Adı *</label>
            <div class="input">
                <i class="fa fa-user"></i> <input type="text" name="username" required placeholder="Kullanıcı adınız">
            </div>

            <label>E-posta Adresi</label>
            <div class="input">
                <i class="fa fa-envelope"></i> <input type="email" name="email" placeholder="ornek@stokiq.com">
            </div>
            
            <label>Şifre *</label>
            <div class="input">
                <i class="fa fa-lock"></i> <input type="password" name="password" required placeholder="******">
            </div>

            <label>Şifre Tekrar *</label>
            <div class="input">
                <i class="fa fa-lock"></i>
                <input type="password" name="password_confirm" required placeholder="******">
            </div>
            
            <button type="submit" class="btn primary full" style="margin-top: 10px;">Kayıt Ol</button>
        </form>

        <div style="margin-top: 20px; font-size: 14px; color: #6b7280;">
            Zaten hesabınız var mı? <a href="index.php" style="color: #4f8bff; font-weight: 600;">Giriş Yap</a>
        </div>
    </div>
</body>
</html>